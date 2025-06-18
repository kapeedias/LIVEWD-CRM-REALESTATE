<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$pdo = Database::getInstance();
global $csrfToken;

// CSRF token generation and verification
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$error = '';
$success = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    // Google reCAPTCHA v3 validation
    $recaptchaResponse = $_POST['recaptcha_response'] ?? '';
    if (empty($recaptchaResponse)) {
        die('reCAPTCHA verification failed: no token received.');
    }

    $recaptchaSecret = GOOGLE_RECAPTCHA_SECRET_KEY;
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
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM general_info_users WHERE user_email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email is already registered.";
        }
    }

    if (empty($error)) {
        if (empty($password)) {
            $password = bin2hex(random_bytes(6));
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
                country, user_ip, activation_code, ckey, ctime, email_verify, date_created, user_level, approved
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1, 1
            )");

            $stmt->execute([
                $firstName, $middleName, $lastName, $email, $user_name,
                $hashedPwd, $phone, $city, $zipcode, $province, $jobTitle,
                $country, $ip, $activationCode, $ckey, $ctime, 'Sent'
            ]);

            $success = "✅ Registration successful.";
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

$csrfToken = generateCsrfToken();
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
                  <img src="assets/images/zentra-logo-dark.png" class="img-responsive-brand text-center">
                </a>
                <hr />
                Members Registration
                <hr />
                <?php if ($error): ?>
                  <p style="color:red;"><?= htmlspecialchars($error) ?></p><hr />
                <?php endif; ?>
                <?php if ($success): ?>
                  <p style="color:green;"><?= $success ?></p><hr />
                <?php endif; ?>

                <form id="forms-register" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                  <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                  <input type="hidden" name="recaptcha_response" id="recaptchaResponse">

                  <div class="form-group"><label>First Name</label><input type="text" name="first_name" class="form-control" required></div>
                  <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name" class="form-control"></div>
                  <div class="form-group"><label>Last Name</label><input type="text" name="last_name" class="form-control" required></div>
                  <div class="form-group"><label>Email address</label><input type="email" name="email" class="form-control" required></div>
                  <div class="form-group"><label>Phone (format: 123.456.7890)</label><input type="tel" name="tel" pattern="\d{3}\.\d{3}\.\d{4}" class="form-control" placeholder="123.456.7890" required></div>
                  <div class="form-group"><label>City</label><input type="text" name="city" class="form-control" required></div>
                  <div class="form-group"><label>Zipcode</label><input type="text" name="zipcode" class="form-control" required></div>
                  <div class="form-group"><label>Province</label><input type="text" name="province" class="form-control" required></div>
                  <div class="form-group"><label>Country</label><input type="text" name="country" class="form-control" value="Canada" required></div>
                  <div class="form-group"><label>Job Title</label><input type="text" name="job_title" class="form-control"></div>
                  <div class="form-group">
                    <label>Password (optional)</label>
                    <input type="password" name="password" class="form-control">
                    <small class="form-text text-muted">Leave blank to auto-generate a strong password.</small>
                  </div>
                  <div class="mt-3">
                    <button type="submit" class="btn btn-primary text-white">Register</button>
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

  <!-- JS includes -->
  <script src="assets/vendors/core/core.js"></script>
  <script src="assets/vendors/feather-icons/feather.min.js"></script>
  <script src="assets/js/template.js"></script>

  <!-- Google reCAPTCHA v3 -->
  <script src="https://www.google.com/recaptcha/api.js?render=<?= GOOGLE_RECAPTCHA_SITE_KEY ?>"></script>
  <script>
    grecaptcha.ready(function() {
      grecaptcha.execute('<?= GOOGLE_RECAPTCHA_SITE_KEY ?>', {action: 'register'}).then(function(token) {
        document.getElementById('recaptchaResponse').value = token;
      });
    });
  </script>
</body>
</html>
