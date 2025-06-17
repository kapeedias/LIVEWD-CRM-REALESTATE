<?php
session_start();
require_once __DIR__ . '/config/config.php';  // Should define SITE_URL, DB creds, RECAPTCHA_SITE_KEY, RECAPTCHA_SECRET_KEY, etc.
require_once __DIR__ . '/config/db.php';      // Your Database class
require_once __DIR__ . '/config/helpers.php'; // For validatePasswordComplexity(), generatePassword(), etc.

$pdo = Database::getInstance();

$error = '';
$success = '';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        // Validate Google reCAPTCHA
        $recaptchaSecret = GOOGLE_RECAPTCHA_SECRET_KEY; // from config.php
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
        if (empty($recaptchaResponse)) {
        $error = "Captcha verification failed. Please try again. - Empty";
        } else {
          // Make POST request to verify captcha
          $response = file_get_contents($recaptchaUrl . '?secret=' . urlencode($recaptchaSecret) . '&response=' . urlencode($recaptchaResponse) . '&remoteip=' . $_SERVER['REMOTE_ADDR']);
          $responseData = json_decode($response);
          $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
        if (!$responseData->success) {
            $error = "Captcha verification failed. Please try again.";
        }
        }
         
     
    }

    if (!$error) {
        // Sanitize and trim inputs
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['tel'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $zipcode = trim($_POST['zipcode'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $jobTitle = trim($_POST['job_title'] ?? '');
        $country = trim($_POST['country'] ?? DEFAULT_COUNTRY);
        $password = $_POST['password'] ?? '';

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (!empty($password) && ($msg = validatePasswordComplexity($password)) !== true) {
            $error = $msg;
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM general_info_users WHERE user_email = ?");
            $stmt->execute([$email]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $error = "Email is already registered.";
            } else {
                // Generate password if empty
                $password = empty($password) ? generatePassword() : $password;
                $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
                $ip = $_SERVER['REMOTE_ADDR'];
                $activationCode = random_int(100000, 999999);
                $ckey = bin2hex(random_bytes(16));
                $ctime = time();
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

                    $success = "✅ Registration successful.";
                    // Unset CSRF token to prevent resubmission
                    unset($_SESSION['csrf_token']);
                } catch (PDOException $e) {
                    $error = "Registration failed: " . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
    // Regenerate CSRF token after processing POST
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrf_token = $_SESSION['csrf_token'];
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= APP_NAME ?> - Register</title>
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
                      Members Registration
                      <hr />
                       <?php if ($error): ?>
                                <p style="color:red;"><?= htmlspecialchars($error) ?></p>
                                <hr />
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <p style="color:green;"><?= $success ?></p>
                                <hr />
                        <?php endif; ?>
                     <form class="forms-register" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" class="form-control" id="firstName" name="first_name" required>
                      </div>

                      <div class="form-group">
                        <label for="middleName">Middle Name</label>
                        <input type="text" class="form-control" id="middleName" name="middle_name">
                      </div>

                      <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" class="form-control" id="lastName" name="last_name" required>
                      </div>

                      <div class="form-group">
                        <label for="InputEmail1">Email address</label>
                        <input type="email" class="form-control" id="InputEmail1" name="email" placeholder="Email" required>
                      </div>

                      <div class="form-group">
                        <label for="tel">Phone (format: 123.456.7890)</label>
                        <input type="tel" pattern="\d{3}\.\d{3}\.\d{4}" class="form-control" id="tel" name="tel" placeholder="123.456.7890" required>
                      </div>

                      <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" class="form-control" id="city" name="city" required>
                      </div>

                      <div class="form-group">
                        <label for="zipcode">Zipcode</label>
                        <input type="text" class="form-control" id="zipcode" name="zipcode" required>
                      </div>

                      <div class="form-group">
                        <label for="province">Province</label>
                        <input type="text" class="form-control" id="province" name="province" required>
                      </div>

                      <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" class="form-control" id="country" name="country" value="Canada" required>
                      </div>

                      <div class="form-group">
                        <label for="jobTitle">Job Title</label>
                        <div class="g-recaptcha" name ="g-recaptcha-response" id = "g-recaptcha-response" data-sitekey="<?= GOOGLE_RECAPTCHA_SITE_KEY ?>"></div>
                        <input type="text" class="form-control" id="jobTitle" name="job_title">
                      </div>

                      <div class="form-group">
                        <label for="password">Password (optional)</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted">Leave blank to auto-generate a strong password.</small>
                      </div>
                        <?php if ($error): ?>
                                <p style="color:red;"><?= htmlspecialchars($error) ?></p>
                                <hr />
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <p style="color:green;"><?= $success ?></p>
                                <hr />
                        <?php endif; ?>
                      <div class="mt-3">
                        <button type="submit" class="btn btn-primary mr-2 mb-2 mb-md-0 text-white">Register</button>
                      </div>

                      <a href="login.php" class="d-block mt-3 text-right text-muted">Login</a>
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