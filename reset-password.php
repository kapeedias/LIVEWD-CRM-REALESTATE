<?php
// ==== CONFIG & DEPENDENCIES ====
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Mailer.php';

// ==== SECURE SESSION START ====
secureSessionStart();
$tokenValid = false;
$form_display = '';

try {
    $pdo = Database::getInstance();
    $userObj = new User($pdo);
    $mailer = new Mailer();
} catch (PDOException $e) {
    error_log("DB Init Error: " . $e->getMessage());
    $errors[] = "A database error occurred. Please try again later.";
}

// ==== RATE LIMITING CONFIG ====
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// ==== GET TOKEN FROM URL ====
$token = trim($_GET['token'] ?? '');
$user = null;

/* ==== Token Validation ==== */

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT pr.user_id, u.user_email, pr.expires_at
            FROM zentra_password_resets pr
            JOIN general_info_users u ON pr.user_id = u.id
            WHERE pr.reset_token = :token and pr.status='active' LIMIT 1");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && strtotime($user['expires_at']) > time()) {
            $tokenValid = true;
            $form_display=1;
        } else {
            $errors[] = "Reset failed. The token may have expired.";
            $form_display=0;
        }
    } catch (PDOException $e) {
        error_log("Token Validation Error: " . $e->getMessage());
        $errors[] = "An unexpected error occurred. Please try again later.";
        $form_display=0;
    }
} else {
    $errors[] = "Missing reset token.";
    $form_display=0;
}


// ==== PROCESS FORM SUBMISSION ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors) && $tokenValid) {
    $newPassword = trim($_POST['password'] ?? '');
    $confirm     = trim($_POST['confirm'] ?? '');

      // Validate password complexity
    $validationResult = validatePasswordComplexity($newPassword);
    if ($validationResult !== true) {
        $errors = array_merge($errors, $validationResult);
    }

    // Check password confirmation
    if ($newPassword !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // reCAPTCHA Verification
    $captcha = $_POST['g-recaptcha-response'] ?? '';
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?" . http_build_query([
        'secret'   => GOOGLE_RECAPTCHA_SECRET_KEY,
        'response' => $captcha,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ]));
    $captchaResult = json_decode($verify);
    if (!$captchaResult->success) {
        $errors[] = 'reCAPTCHA verification failed.';
    }

  // If no errors, reset password
    if (empty($errors)) {
        try {
            $resetSuccess = $userObj->resetPassword($token, $newPassword);
            if ($resetSuccess) {
                $pdo->prepare("UPDATE zentra_password_resets SET status = 'used' WHERE reset_token = :token")
                    ->execute(['token' => $token]);

                $userObj->logActivity($user['user_id'], "Password reset via token", "Password Reset");

                $success[] = "Your password has been reset successfully! You may now log in.";
                $tokenValid = false; // So form doesn't show again
                $form_display = 0;
            } else {
                $errors[] = 'Reset failed. The token may have expired. <a href="login.php" class="btn btn-sm btn-outline-primary mt-2">Go back to Login</a>';
                $form_display=0;
            }
        } catch (Exception $e) {
            error_log("Password Reset Error: " . $e->getMessage());
            $errors[] = "Reset failed: " . $e->getMessage()." <br />"; // TEMPORARY DEBUG
            $errors[] = "Unexpected error occurred. Please try again.";
            $form_display=0;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= APP_NAME ?> - Reset Password</title>
    <!-- core:css -->
    <link rel="stylesheet" href="assets/vendors/core/core.css">
    <!-- endinject -->
    <!-- plugin css for this page -->
    <!-- end plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="assets/fonts/feather-font/css/iconfont.css">
    <link rel="stylesheet" href="assets/vendors/flag-icon-css/css/flag-icon.min.css">
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="assets/images/favicon.png" />
</head>
<body class="sidebar-dark">
    <div class="main-wrapper">
        <div class="page-wrapper full-page">
            <div class="page-content d-flex align-items-center justify-content-center">
                <div class="row w-100 mx-0 auth-page">
                    <div class="col-lg-4 mx-auto">
                        <div class="card">
                            <div class="row">
                                <div class="col">
                                    <div class="auth-form-wrapper px-4 py-5">
                                        <a href="#" class="sidebar-brand">
                                            <img src="assets/images/zentra-logo-dark.png"
                                                class="img-responsive-brand text-center">
                                        </a>
                                        <hr />
                                        <h4 class="text-center">Reset Password</h4>
                                        <hr />
                                        <?php if (!empty($success)): ?>
                                        <div class="alert alert-success">
                                            <?= implode('<br>', array_map('htmlspecialchars', $success)) ?>
                                        </div>
                                        <a href="login.php" class="btn btn-primary text-white mt-3">Login Now</a>
                                        <?php endif; ?>

                                        <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger">
                                            <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
                                        </div>
                                        
                                        <?php endif; ?>

                                        <?php if (!$tokenValid && $form_display == 1): ?>
                                           <a href="forgot.php" class="btn btn-secondary mt-3">Try Forgot Password
                                            Again</a>
                                        <?php endif; ?>

                                        <?php if ($form_display == 1): ?>
                                        <form class="forms-sample" method="POST" action="">

                                            <div class="form-group">
                                                <label for="NewPassword">New Password</label>
                                                <input type="password" class="form-control" id="password"
                                                    name="password" placeholder="New Password" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="ConfirmNewPassword">Confirm New Password</label>
                                                <input type="password" class="form-control" id="confirm" name="confirm"
                                                    placeholder="Confirm New Password" required>
                                            </div>

                                            <div class="mt-3">
                                                <div class="g-recaptcha"
                                                    data-sitekey="<?= GOOGLE_RECAPTCHA_SITE_KEY; ?>"></div>
                                                <script src="https://www.google.com/recaptcha/api.js" async defer>
                                                </script>

                                                <button type="submit"
                                                    class="btn btn-primary mr-2 mb-2 mb-md-0 mt-3 text-white">Reset
                                                    Password</button>

                                            </div>
                                            <a href="login.php" class="d-block mt-3 text-right text-muted">Login Now
                                            </a>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- core:js -->
    <script src="assets/vendors/core/core.js"></script>
    <script src="assets/vendors/feather-icons/feather.min.js"></script>
    <script src="assets/js/template.js"></script>
</body>

</html>