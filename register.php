<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$pdo = Database::getInstance();

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function generatePassword($length = 12) {
    return bin2hex(random_bytes($length / 2)); // 12 chars
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token check
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    // Google reCAPTCHA v3 validation
    $recaptchaResponse = $_POST['recaptcha_response'] ?? '';
    if (empty($recaptchaResponse)) {
        die('reCAPTCHA verification failed: no token received.');
    }

    $recaptchaSecret = GOOGLE_RECAPTCHA_SECRET_KEY; // from config.php
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $response = file_get_contents($verifyUrl . '?secret=' . urlencode($recaptchaSecret) . '&response=' . urlencode($recaptchaResponse));
    $responseData = json_decode($response, true);

    if (!$responseData['success'] || $responseData['score'] < 0.5 || $responseData['action'] !== 'register') {
        die('reCAPTCHA verification failed. Please try again.');
    }

    // Sanitize and validate inputs
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

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }

    if (empty($error)) {
        // Check if user with email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM general_info_users WHERE user_email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email is already registered.";
        }
    }

    if (empty($error)) {
        // Generate password if empty
        if (empty($password)) {
            $password = generatePassword();
        }

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
            // Optional: email user their password or activation link here
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

// Generate fresh CSRF token for form
$csrfToken = generateCsrfToken();

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
                       <input type="hidden" name="recaptcha_response" id="recaptchaResponse">
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
    <script src="https://www.google.com/recaptcha/api.js?render=<?= GOOGLE_RECAPTCHA_SITE_KEY ?>"></script>
<script>
  grecaptcha.ready(function() {
    grecaptcha.execute('<?= GOOGLE_RECAPTCHA_SITE_KEY ?>', {action: 'register'}).then(function(token) {
      // Add the token to a hidden input field inside your form
      var recaptchaResponse = document.getElementById('recaptchaResponse');
      if (recaptchaResponse) {
        recaptchaResponse.value = token;
      }
    });
  });
</script>
    <!-- core:js -->
    <script src="assets/vendors/core/core.js"></script>
    <!-- endinject -->
    <!-- plugin js for this page -->
    <!-- end plugin js for this page -->
    <!-- inject:js -->
    <script src="assets/vendors/feather-icons/feather.min.js"></script>
    <script src="assets/js/template.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- endinject -->
    <!-- custom js for this page -->
    <!-- end custom js for this page -->
  </body>
</html>