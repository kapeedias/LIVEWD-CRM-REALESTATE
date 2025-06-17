<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
$pdo = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['tel']);
    $city = trim($_POST['city']);
    $zipcode = trim($_POST['zipcode']);
    $province = trim($_POST['province']);
    $jobTitle = trim($_POST['job_title']);
    $country = trim($_POST['country'] ?? DEFAULT_COUNTRY);
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!empty($password) && ($msg = validatePasswordComplexity($password)) !== true) {
        $error = $msg;
    } else {
        $password = empty($password) ? generatePassword() : $password;
        $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
        $ip = $_SERVER['REMOTE_ADDR'];
        $activationCode = random_int(100000, 999999);
        $ckey = bin2hex(random_bytes(16));
        $ctime = time();
        $md5_id = md5(uniqid($email, true));
        $user_name = $email;

        try {
            $stmt = $pdo->prepare("INSERT INTO general_info_users (
                first_name, middle_name, last_name, user_email, user_name,
                pwd, tel, city, zipcode, province, job_title,
                country, ipaddress, activation_code, ckey, ctime, email_verify, date_created, user_level, approved
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1, 1
            )");

            $stmt->execute([
                $firstName, $middleName, $lastName, $email, $user_name,
                $hashedPwd, $phone, $city, $zipcode, $province, $jobTitle,
                $country, $ip, $activationCode, $ckey, $ctime, 'Sent'
            ]);

            // send activation email here if needed
            echo "<p style='color:green;'>✅ Registration successful. Please check your email for activation.</p>";
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
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
                          <a href="myaccount.html"
                            class="btn btn-primary mr-2 mb-2 mb-md-0 text-white">Login</a>
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