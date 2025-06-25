<?php
// ==== CONFIG & DEPENDENCIES ====
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Mailer.php';

// ==== SECURE SESSION START ====
secureSessionStart();

// ==== INITIALIZE ====
$errors = $_SESSION['forgot_errors'] ?? [];
unset($_SESSION['forgot_errors']);
$success = $_SESSION['forgot_success'] ?? null;
unset($_SESSION['forgot_success']);

try {
    $pdo = Database::getInstance();
    $userObj = new User($pdo);
    $mailer = new Mailer();
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

// ==== RATE LIMITING CONFIG ====
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$maxAttempts = 5;
$lockoutTime = 15 * 60; // 15 minutes

if (!isset($_SESSION['forgot_attempts'])) {
    $_SESSION['forgot_attempts'] = [];
}

// Remove expired attempts
foreach ($_SESSION['forgot_attempts'] as $time => $recordedIp) {
    if (time() - $time > $lockoutTime) {
        unset($_SESSION['forgot_attempts'][$time]);
    }
}

// Count attempts from this IP
$attempts = array_filter($_SESSION['forgot_attempts'], fn($v) => $v === $ip);

// ==== FORM SUBMISSION HANDLER ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    if (count($attempts) >= $maxAttempts) {
        $errors[] = 'Too many password reset requests from your IP. Please wait and try again later.';
    }
   // Trim email before sanitization
    $_POST['useremail'] = trim($_POST['useremail'] ?? '');

    // Sanitize and validate input
    $allowedFields = ['useremail' => 'email'];
    try {
        $input = sanitizeInput($_POST, $allowedFields);
        $email = $input['useremail'];
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    // reCAPTCHA verification
    $recaptchaSecret = GOOGLE_RECAPTCHA_SECRET_KEY;
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptchaResponse)) {
        $errors[] = 'Please complete the reCAPTCHA.';
    } else {
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?" . http_build_query([
            'secret' => $recaptchaSecret,
            'response' => $recaptchaResponse,
            'remoteip' => $ip
        ]));
        $captchaResult = json_decode($verify);
        if (!$captchaResult->success) {
            $errors[] = 'reCAPTCHA verification failed.';
        }
    }

    if (empty($errors)) {
        try {
            // Check if user exists and is approved & not banned
            $stmt = $pdo->prepare("SELECT id, first_name, user_email, approved, banned FROM general_info_users WHERE user_email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // To prevent user enumeration, do NOT reveal this directly
                $errors[] = "If that email is registered, you will receive a reset link shortly.";
            } elseif ((int)$user['banned'] === 1) {
                $errors[] = 'This account has been banned.';
            } elseif ((int)$user['approved'] !== 1) {
                $errors[] = 'This account has not been approved yet.';
            } else {
                // Generate token and expiry (1 hour validity)
                $token = bin2hex(random_bytes(16));
                $expires = date('Y-m-d H:i:s', time() + 3600);

                // Store token and expiry in your password resets table
                $insert = $pdo->prepare("INSERT INTO zentra_password_resets (user_id, reset_token, expires_at) VALUES (:uid, :token, :expires)");
                $insert->execute([
                    'uid' => $user['id'],
                    'token' => $token,
                    'expires' => $expires
                ]);

                // Send reset email
                $mailSent = $mailer->sendResetPasswordEmail($user['user_email'], $user['first_name'], $token);

                if ($mailSent) {
                    $success = "A password reset link has been sent to your email.";
                    $identifier = "Password reset requested for user {$user['user_email']}";
                    $userObj->logActivity($user['id'], $identifier, 'Password Reset Requested');
                } else {
                    $errors[] = "Failed to send reset email. Please try again later.";
                }
            }
        } catch (PDOException $e) {
            error_log("FORGOT PASSWORD ERROR: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again later.';
        }
    }

    // Log attempts and errors
    if (!empty($errors)) {
        $_SESSION['forgot_attempts'][time()] = $ip;
        $identifier = "Failed password reset request for email: " . ($email ?? '[unknown]');
        $userId = $user['id'] ?? null;
        $userObj->logActivity(
            $userId,
            $identifier,
            'Password Reset Error',
            ['context_error' => implode(" | ", $errors)]
        );
    }

    // Save errors or success message for display and redirect to self
    $_SESSION['forgot_errors'] = $errors;
    $_SESSION['forgot_success'] = $success ?? null;
    header("Location: forgot.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title><?= APP_NAME ?> - Login</title>
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
  <link rel="stylesheet" href="assets/css/demo_1/style.css">
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
                    <?php if (!empty($errors)): ?>
                      <div class="alert alert-danger" role="alert">
                        <?php foreach ($errors as $err): ?>
                          <p><?= htmlspecialchars($err) ?></p>
                        <?php endforeach; ?>

                      </div>
                    <?php endif; ?>
                    <form class="forms-sample" method="POST" action="">
                      <div class="form-group">
                        <label for="InputEmail1">Email address</label>
                        <input type="email" class="form-control" id="useremail" name="useremail"
                          placeholder="Email" required>
                      </div>
                      <div class="mt-3">
                        <div class="g-recaptcha"
                          data-sitekey="<?= GOOGLE_RECAPTCHA_SITE_KEY; ?>"></div>
                        <script src="https://www.google.com/recaptcha/api.js" async defer>
                        </script>

                        <button type="submit"
                          class="btn btn-primary mr-2 mb-2 mb-md-0 mt-3 text-white">Reset</button>

                      </div>
                      <a href="login.php" class="d-block mt-3 text-right text-muted">Login Now
                      </a>
                    </form>
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