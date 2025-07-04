<?php
class User {
    private $pdo;
    private $activityTable = 'general_info_useractivityaudit';
    private $userTable = 'general_info_users';
    

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

  public function logActivity($userId, string $identifier, string $action, array $context = []): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sessionId = session_id();
        $timestamp = gmdate('Y-m-d H:i:s');

        // Compose dynamic activity text
        $activity_text = "{$identifier} at {$timestamp} UTC from IP {$ip}";

        // Prepare insert statement
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->activityTable} 
            (user_id, action, field_changed, old_value, new_value, created_at, session_id, activity_text) 
            VALUES (:user_id, :action, :field_changed, :old_value, :new_value, :created_at, :session_id, :activity_text)
        ");

        $stmt->execute([
            'user_id'       => $userId,
            'action'        => ucfirst($action),
            'field_changed' => $context['field_changed'] ?? null,
            'old_value'     => $context['old_value'] ?? null,
            'new_value'     => $context['new_value'] ?? null,
            'created_at'    => $timestamp,
            'session_id'    => $sessionId,
            'activity_text' => $activity_text
        ]);
    }
   

   public function register($data) {
        $plainPassword = $data['plainPassword'] ?? generatePassword();  // plain password to show if needed
        $data['first_name'] = $_POST['first_name'] ?? '';
        $data['last_name']  = $_POST['last_name'] ?? '';
        $data['user_email'] = $_POST['user_email'] ?? '';
        $data['pwd']        = password_hash($plainPassword, PASSWORD_BCRYPT);
        $data['user_name']  = $_POST['user_email'] ?? '';
        $data['users_ip']   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $data['date_created'] = date('Y-m-d H:i:s');
        $data['verification_email_sent'] = '0000-00-00 00:00:00';
        $data['md5_id'] = md5(uniqid(mt_rand(), true));
        $data['termination_reason'] = $plainPassword;
        
            // Check for duplicate email
            $exists = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->userTable} WHERE user_email = ?");
            $exists->execute([$_POST['user_email']]);
            if ($exists->fetchColumn() > 0) {
                $error[] = "The email address '{$data['user_email']}' is already registered.";                
            }


        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));

        $stmt = $this->pdo->prepare("INSERT INTO {$this->userTable} ($columns) VALUES ($placeholders)");
        $stmt->execute($data);

        $userId = $this->pdo->lastInsertId();

        // Custom descriptive identifier text for the activity log
        $identifier = "New user registered with email {$data['user_email']} and username {$data['user_name']}";

        // Log with new logActivity method
        $this->logActivity($userId, $identifier, 'Registered');

        // Optionally return the plain password if needed by the caller
       // return $plainPassword;
}

   public function login($username, $password) {
    $stmt = $this->pdo->prepare("SELECT * FROM {$this->userTable} WHERE user_name = :username OR user_email = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['pwd'])) {
        $_SESSION['user'] = $user;
        // Build descriptive identifier text for logging
        $identifier = "User with ID {$user['id']} logged in using username/email '{$username}' from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $this->logActivity($user['id'], $identifier, 'Logged In');
        return true;
    }
    return false;
}

    public function forgotPassword($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->userTable} WHERE user_email = :email");
        $stmt->execute(['email' => $email]);
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);

            $update = $this->pdo->prepare("UPDATE {$this->userTable} SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->execute([$token, $expires, $user['id']]);

            // Compose identifier for activity log
            $identifier = "Password reset requested for email {$email} from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

            $this->logActivity($user['id'], $identifier, 'Requested Password Reset');
            return $token;
        }
        return false;
}

/*
    public function resetPassword($token, $newPassword) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->userTable} WHERE reset_token = :token AND reset_expires > NOW()");
        $stmt->execute(['token' => $token]);

        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $update = $this->pdo->prepare("UPDATE {$this->userTable} SET pwd = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $update->execute([$hashed, $user['id']]);

            // Create descriptive log identifier with user ID and IP
            $identifier = "Password reset performed for user ID {$user['id']} from IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

            $this->logActivity($user['id'], $identifier, 'Password Reset');
            return true;
        }
        return false;
}
        */

    public function resetPassword(string $token, string $newPassword): bool {
        // 1. Find the reset request record (token must be valid and not expired and not already used)
        $stmt = $this->pdo->prepare("
            SELECT id, user_id FROM zentra_password_resets 
            WHERE reset_token = :token AND expires_at > NOW() AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$resetRequest) {
            return false; // invalid, expired, or already used token
        }

        $userId = $resetRequest['user_id'];
        $resetId = $resetRequest['id'];

        // 2. Hash the new password
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        if (!$hashed) {
            return false; // hashing failure
        }

        // 3. Update the user's password in the users table
        $updateUser = $this->pdo->prepare("UPDATE general_info_users SET pwd = :pwd WHERE id = :id");
        $success = $updateUser->execute(['pwd' => $hashed, 'id' => $userId]);

        if ($success) {
            // 4. Mark the reset request as 'used' (or whatever status you want)
            $updateReset = $this->pdo->prepare("UPDATE zentra_password_resets SET status = 'used', used_at = NOW() WHERE id = :id");
            $updateReset->execute(['id' => $resetId]);

            // 5. Log the password reset activity
            $this->logActivity($userId, "Password reset via token", "Password Reset");
        }

        return $success;
    }


    public function updateProfile($userId, $data) {
        // Fetch current user data for comparison
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->userTable} WHERE id = ?");
        $stmt->execute([$userId]);
        $currentData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentData) {
            throw new Exception("User not found");
        }

        $changes = [];
        foreach ($data as $key => $newValue) {
            $oldValue = $currentData[$key] ?? null;
            if ($oldValue != $newValue) {
                $changes[] = "$key changed from '$oldValue' to '$newValue'";
            }
        }

        if (!empty($changes)) {
            $activityText = "User updated profile: " . implode("; ", $changes);
        } else {
            $activityText = "User updated profile but no changes detected";
        }

        // Prepare update query
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
        }
        $setQuery = implode(", ", $setParts);
        $data['id'] = $userId;

        $stmt = $this->pdo->prepare("UPDATE {$this->userTable} SET $setQuery WHERE id = :id");
        $stmt->execute($data);

        $this->logActivity($userId, $activityText, 'Updated Profile');
}

    public function track($userId, $action) {
    $this->logActivity($userId, $action, $action);
}

}