<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';        // Your Database class file
require_once __DIR__ . '/classes/User.php';    // Your User class file

try {
    $pdo = Database::getInstance();
    $user = new User($pdo);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate a secure 8-character password (hex)
    $pwd = generatePassword();

    // Prepare data with minimal inputs and required defaults
    $formData = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name'  => $_POST['last_name'] ?? '',
        'user_email' => $_POST['user_email'] ?? '',
        'user_name'  => $_POST['user_email'] ?? '',
        'users_ip'   => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'date_created' => date('Y-m-d'),
        'email_verify' => "PENDING",
        'activation_code' => rand(1000000000, 9999999999),

    ];

   try {
          $user->register($formData);
          $success[] = "Registration successful! An email has been sent with the login credentials.";
      } catch (Exception $e) {
          logAppError($e); // Log actual error
          $error[] = "Registration failed. Please try again later or contact support."; // Generic message to user
      }

}
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
                                <?php if (!empty($error)): ?>
                                <?php foreach ($error as $msg): ?>
                                <p style="color:red;"><?= $msg ?></p>
                                <hr />
                                <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if (!empty($success)): ?>
                                <?php foreach ($success as $msg): ?>
                                <p style="color:green;"><?= $msg ?></p>
                                <hr />
                                <?php endforeach; ?>
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
                                    <div class="mt-3"><button type="submit"
                                            class="btn btn-primary text-white">Register</button></div>
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

    <!-- Google reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?= GOOGLE_RECAPTCHA_SITE_KEY ?>"></script>
    <script>
    grecaptcha.ready(function() {
        grecaptcha.execute('<?= GOOGLE_RECAPTCHA_SITE_KEY ?>', {
            action: 'register'
        }).then(function(token) {
            document.getElementById('recaptchaResponse').value = token;
        });
    });
    </script>
</body>

</html>