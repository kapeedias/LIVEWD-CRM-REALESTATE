<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/classes/User.php';

try {
    $pdo = Database::getInstance();
    $userObj = new User($pdo);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error = '';
// === RATE LIMITING CONFIG ===
$ip = $_SERVER['REMOTE_ADDR'];
$maxAttempts = 5;
$lockoutTime = 15 * 60; // 15 minutes

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}

// Remove old attempts outside the lockout window
foreach ($_SESSION['login_attempts'] as $time => $recordedIp) {
    if (time() - $time > $lockoutTime) {
        unset($_SESSION['login_attempts'][$time]);
    }
}

// Count recent attempts from this IP
$attempts = array_filter($_SESSION['login_attempts'], fn($v) => $v === $ip);

if (count($attempts) >= $maxAttempts) {
    $error='Too many login attempts. Please wait before trying again.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['useremail'] ?? '');
    $password = trim($_POST['userpassword'] ?? '');

    // === reCAPTCHA verification ===
    $recaptchaSecret = GOOGLE_RECAPTCHA_SECRET_KEY;  // Replace with your secret key
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptchaResponse)) {
        $error ='Please complete the reCAPTCHA.';
        exit;
    }

    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
    $captchaResult = json_decode($verify);

    if (!$captchaResult->success) {
        $error ='reCAPTCHA verification failed. Please try again.';
        exit;
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        try {
            // Get user by email
            $stmt = $pdo->prepare("SELECT id, first_name, pwd, approved, banned FROM general_info_users WHERE user_email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = 'Invalid email or password.';
            } elseif (!password_verify($password, $user['pwd'])) {
                $error = 'Invalid email or password.';
            } elseif ((int)$user['banned'] === 1) {
                $error = 'Your account has been banned.';
            } elseif ((int)$user['approved'] !== 1) {
                $error = 'Your account has not been approved yet.';
            } else {
                // Login success — set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'];
                $_SESSION['user_email'] = $email;

                // Redirect to user dashboard
                header("Location: myaccount.php");
                exit;
            }

        } catch (PDOException $e) {
            error_log("LOGIN ERROR: " . $e->getMessage());
            $error = 'Login failed. Please try again later.';
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
    <title><?= APP_NAME ?> - Login</title>
    <!-- core:css -->
    <link rel="stylesheet" href="assets/vendors/core/core.css">
    <!-- endinject -->
    <!-- plugin css for this page -->
    <!-- end plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="assets/fonts/feather-font/css/iconfont.css">
    <link rel="stylesheet"
      href="assets/vendors/flag-icon-css/css/flag-icon.min.css">
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="assets/css/demo_1/style.css">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="assets/images/favicon.png" />
  </head>
  <body class="sidebar-dark">
    <div class="main-wrapper">
      <div class="page-wrapper full-page">
        <div
          class="page-content d-flex align-items-center justify-content-center">
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
                      <?php if ($error): ?>
                      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                      <form class="forms-sample" method="POST" action="">
                        <div class="form-group">
                          <label for="InputEmail1">Email address</label>
                          <input type="email" class="form-control" id="useremail" name="useremail" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                          <label for="InputPassword1">Password</label>
                             <input type="password" class="form-control" id="userpassword" name="userpassword" placeholder="Password" required>
                        </div>
                        <div
                          class="form-check form-check-flat form-check-primary">
                          <label class="form-check-label">
                            <input type="checkbox" class="form-check-input">
                            Remember me
                          </label>
                        </div>
                        <div class="mt-3">
                          <button type="submit" class="btn btn-primary mr-2 mb-2 mb-md-0 text-white">Login</button>
                      
                        </div>
                        <a href="register.html"
                          class="d-block mt-3 text-right text-muted">Forgot
                          Password?
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
   <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="assets/vendors/feather-icons/feather.min.js"></script>
    <script src="assets/js/template.js"></script>
  </body>
</html>