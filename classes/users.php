<?php
class User {
    private $pdo;
    private $activityTable = 'general_info_useractivityaudit';
    private $userTable = 'general_info_users';

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        session_start();
    }

    private function logActivity($userId, $action) {
        $stmt = $this->pdo->prepare("INSERT INTO {$this->activityTable} (user_id, action, session_id, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $action, session_id()]);
    }

    public function register($data) {
        $data['first_name'] = $_POST['first_name'] ?? '';
        $data['last_name'] = $_POST['last_name'] ?? '';
        $data['user_email'] = $_POST['user_email'] ?? '';
        $data['pwd'] = generatePassword(); // plain password before hashing
        $data['user_name'] = $_POST['user_email'] ?? '';
        $data['users_ip'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Now hash password and generate md5_id
        $data['pwd'] = password_hash($data['pwd'], PASSWORD_BCRYPT);
        $data['md5_id'] = md5(uniqid(mt_rand(), true));

        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));

        $stmt = $this->pdo->prepare("INSERT INTO {$this->userTable} ($columns) VALUES ($placeholders)");
        $stmt->execute($data);

        $userId = $this->pdo->lastInsertId();
        $this->logActivity($userId, 'Registered');
    }

    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->userTable} WHERE user_name = :username OR user_email = :username LIMIT 1");
        $stmt->execute(['username' => $username]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['pwd'])) {
            $_SESSION['user'] = $user;
            $this->logActivity($user['id'], 'Logged In');
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

            $this->logActivity($user['id'], 'Requested Password Reset');
            return $token;
        }
        return false;
    }

    public function resetPassword($token, $newPassword) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->userTable} WHERE reset_token = :token AND reset_expires > NOW()");
        $stmt->execute(['token' => $token]);

        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $update = $this->pdo->prepare("UPDATE {$this->userTable} SET pwd = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $update->execute([$hashed, $user['id']]);

            $this->logActivity($user['id'], 'Password Reset');
            return true;
        }
        return false;
    }

    public function updateProfile($userId, $data) {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
        }
        $setQuery = implode(", ", $setParts);

        $data['id'] = $userId;
        $stmt = $this->pdo->prepare("UPDATE {$this->userTable} SET $setQuery WHERE id = :id");
        $stmt->execute($data);

        $this->logActivity($userId, 'Updated Profile');
    }

    public function track($userId, $action) {
        $this->logActivity($userId, $action);
    }
}
