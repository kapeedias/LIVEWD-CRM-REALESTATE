<?php
// ==== CONFIG & DEPENDENCIES ====
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/classes/User.php';

// ==== SECURE SESSION START ====
secureSessionStart();

// ==== SESSION MESSAGE DISPLAY HANDLERS ====
$errors = $_SESSION['register_errors'] ?? [];
$success = $_SESSION['register_success'] ?? [];

unset($_SESSION['register_errors'], $_SESSION['register_success']);

try {
    $pdo = Database::getInstance();
    $user = new User($pdo);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ==== FORM SUBMISSION ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = [];
    $email = '';  // Initialize to avoid undefined variable warning
    
    // === INPUT SANITIZATION ===
    try {
        $allowedFields = [
            'first_name'  => 'text',
            'last_name'   => 'text',
            'user_email'  => 'email',
        ];
        $input = sanitizeInput($_POST, $allowedFields);

        $email      = $input['user_email'];
        $firstName  = $input['first_name'];
        $lastName   = $input['last_name'];

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    // === reCAPTCHA VERIFICATION ===
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
   
    $recaptchaSecret = GOOGLE_RECAPTCHA_SECRET_KEY;
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptchaResponse)) {
        $errors[] = 'Please complete the reCAPTCHA.';
    } else {
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" .
            urlencode($recaptchaSecret) .
            "&response=" . urlencode($recaptchaResponse) .
            "&remoteip=" . urlencode($ip));
        $captchaResult = json_decode($verify);
        if (!$captchaResult->success) {
            $errors[] = 'reCAPTCHA verification failed.';
        }
    }

    // === EMAIL UNIQUENESS CHECK ===
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM general_info_users WHERE user_email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "The email address '{$email}' is already registered.";
        }
    }

    // === CONTINUE IF NO ERRORS ===
    if (empty($errors)) {
        $plainPassword = generatePassword(); // secure random password
        $formData = [
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'user_email'    => $email,
            'user_name'     => $email,
            'users_ip'      => $ip,
            'date_created'  => date('Y-m-d H:i:s'),
            'verification_email_sent' => '0000-00-00 00:00:00',
            'md5_id'        => md5(uniqid(mt_rand(), true)),
            'termination_reason' => $plainPassword, // temporarily store plain pass
            'pwd'           => password_hash($plainPassword, PASSWORD_BCRYPT),
        ];

        try {
            $user->register($formData);

            // Log successful registration
            $userId = $pdo->lastInsertId();
            $identifier = "New registration: {$email}";
            $user->logActivity($userId, $identifier, 'Registered');

            $success[] = "Registration successful! An email has been sent with login credentials.";
        } catch (Exception $e) {
            error_log("REGISTRATION ERROR: " . $e->getMessage());
            $errors[] = "Registration failed. Please try again later.";

            // Log error safely with fallback email text
            $emailForLog = $email ?: 'unknown email';
            $user->logActivity(
                null,
                "Registration failed for {$emailForLog}",
                'Registration Error',
                [
                    'field_changed' => 'register',
                    'old_value' => null,
                    'new_value' => 'error',
                    'context_error' => $e->getMessage()
                ]
            );
        }
    }
    // === STORE MESSAGES IN SESSION AND REDIRECT (PRG) ===
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_success'] = $success;

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ==== ON GET, SHOW MESSAGES FROM SESSION ====
$errors = $_SESSION['register_errors'] ?? [];
$success = $_SESSION['register_success'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_success']);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME ?> - Register</title>
    <link rel="stylesheet" href="assets/vendors/core/core.css">
    <link rel="stylesheet" href="assets/fonts/feather-font/css/iconfont.css">
    <link rel="stylesheet" href="assets/vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="assets/css/demo_1/style.css">
    <link rel="shortcut icon" href="assets/images/favicon.png" />
</head>

<body class="sidebar-dark">
    <div class="main-wrapper">
        <div class="page-wrapper full-page">
            <div class="page-content d-flex align-items-center justify-content-center">
                <div class="row w-100 mx-0 auth-page">
                    <div class="col-lg-4 mx-auto">
                        <div class="card">
                            <div class="auth-form-wrapper px-4 py-5">
                                <a href="#" class="sidebar-brand">
                                    <img src="assets/images/zentra-logo-dark.png"
                                        class="img-responsive-brand text-center">
                                </a>
                                <hr />
                                Members Registration
                                <hr />
                                <?php if (!empty($success)): ?>
                                    <div class="alert alert-success">
                                        <?= implode('<br>', $success) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger">
                                        <?= implode('<br>', $errors) ?>
                                    </div>
                                <?php endif; ?>
                                <form id="forms-register" method="POST"
                                    action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                                    <input type="hidden" name="recaptcha_response" id="recaptchaResponse">
                                    <div class="form-group"><label>First Name</label><input type="text"
                                            name="first_name" class="form-control" required></div>
                                    <div class="form-group"><label>Last Name</label><input type="text" name="last_name"
                                            class="form-control" required></div>
                                    <div class="form-group"><label>Email address</label><input type="email"
                                            name="user_email" class="form-control" required></div>
                                    <div class="mt-3">
                                     <div class="g-recaptcha"
                          data-sitekey="<?= GOOGLE_RECAPTCHA_SITE_KEY; ?>"></div>
                        <script src="https://www.google.com/recaptcha/api.js" async defer>
                        </script>    
                                    <button type="submit"
                                            class="btn btn-primary text-white mt-3">Register</button></div>
                                    <a href="login.php" class="d-block mt-3 text-right text-muted">Login Now</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS includes -->
    <script src="assets/vendors/core/core.js"></script>
    <script src="assets/vendors/feather-icons/feather.min.js"></script>
    <script src="assets/js/template.js"></script>
</body>

</html>