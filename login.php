<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/classes/User.php';

try {
    $pdo = Database::getInstance();
    $user = new User($pdo);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$userObj = new User($pdo);
$error = '';
$maxAttempts = 5;
$lockoutTime = 15; // minutes

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
/*
function tooManyAttempts(PDO $pdo, string $ip, int $maxAttempts, int $lockoutTime): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->execute([$ip, $lockoutTime]);
    return $stmt->fetchColumn() >= $maxAttempts;
}

function logAttempt(PDO $pdo, string $ip, string $email): void {
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())");
    $stmt->execute([$ip, $email]);
}
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrUsername = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
  $message="test";
   // if (!filter_var($emailOrUsername, FILTER_VALIDATE_EMAIL) && empty($emailOrUsername)) {
//$error = 'Please enter a valid email or username.';
  //  } /*elseif (tooManyAttempts($pdo, $ip, $maxAttempts, $lockoutTime)) {
       // $error = "Too many failed attempts. Try again in {$lockoutTime} minutes.";
    
      //} //else {
      /*
        try {
            // Check if user exists and is allowed to login
            $stmt = $pdo->prepare("SELECT id, first_name, approved, banned FROM general_info_users WHERE user_email = ? OR user_name = ?");
            $stmt->execute([$emailOrUsername, $emailOrUsername]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                logAttempt($pdo, $ip, $emailOrUsername);
                $error = 'User not found.';
            } elseif ((int)$user['banned'] === 1) {
                $error = 'Your account has been banned.';
            } elseif ((int)$user['approved'] !== 1) {
                $error = 'Your account is not approved yet.';
            } elseif (!$userObj->login($emailOrUsername, $password)) {
                logAttempt($pdo, $ip, $emailOrUsername);
                $error = 'Incorrect username/email or password.';
            } else {
                // Login success - user info saved in $_SESSION['user'] by User::login()
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'];

                // Clear login attempts for this IP on success
                $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);

                header("Location: myaccount.php");
                exit;
            }
        } catch (PDOException $e) {
            error_log("LOGIN ERROR: " . $e->getMessage());
            $error = 'Login failed. Please try again later.';
        }*/
    //}


  //}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>NobleUI Responsive Bootstrap 4 Dashboard Template</title>
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
                      <form class="forms-sample">
                        <div class="form-group">
                          <label for="InputEmail1">Email address</label>
                          <input type="email" class="form-control"
                            id="InputEmail1" placeholder="Email">
                        </div>
                        <div class="form-group">
                          <label for="InputPassword1">Password</label>
                          <input type="password" class="form-control"
                            id="InputPassword1"
                            autocomplete="current-password"
                            placeholder="Password">
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
    <!-- endinject -->
    <!-- plugin js for this page -->
    <!-- end plugin js for this page -->
    <!-- inject:js -->
    <script src="assets/vendors/feather-icons/feather.min.js"></script>
    <script src="assets/js/template.js"></script>
    <!-- endinject -->
    <!-- custom js for this page -->
    <!-- end custom js for this page -->
  </body>
</html>