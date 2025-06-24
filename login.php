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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['useremail'] ?? '');
    $password = $_POST['userpassword'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, first_name, pwd, approved, banned FROM general_info_users WHERE user_email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

             if (!$user) {
                $error = 'User not found.';
            } elseif ((int)$user['banned'] === 1) {
                $error = 'Your account has been banned.';
            } elseif ((int)$user['approved'] !== 1) {
                $error = 'Your account has not been approved yet.';
            } else {
                // Now try login (which should verify password)
                if ($userObj->login($email, $password)) {
                    // Login success
                    header("Location: myaccount.php");
                    exit;
                } else {
                    $error = 'Incorrect password.';
                }
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