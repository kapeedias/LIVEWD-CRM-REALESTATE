<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/classes/User.php';

session_start();
$status = 'danger';

if (!isset($_GET['code']) || empty($_GET['code'])) {
    $errors[] = 'Invalid or missing activation code.';
} else {
    $code = $_GET['code'];

    try {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM general_info_users WHERE activation_code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errors[] = 'Invalid activation code or already activated.';
        } elseif ((int)$user['approved'] === 1) {
            $success[] = 'This account is already verified.';
            $status = 'info';
        } else {
            // Update user to mark as approved
            $update = $pdo->prepare("UPDATE general_info_users SET approved = 1, email_verify = 'VERIFIED', activation_code = NULL WHERE id = :id");
            $update->execute(['id' => $user['id']]);
            $success[] = 'Your email has been successfully verified! You can now log in.';
            $status = 'success';

            // Optional: Log the activity
            $userClass = new User($pdo);
            $userClass->logActivity($user['id'], "User verified their email address", "Email Verified");
        }
    } catch (PDOException $e) {
        error_log("Email Verification Error: " . $e->getMessage());
        $errors[] = 'An unexpected error occurred. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= APP_NAME ?> - Verify User</title>
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
                                        <?php if (!empty($success)): ?>
                                        <div class="alert alert-success">
                                            <?= implode('<br>', $success) ?>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger">
                                            <?= implode('<br>', $errors) ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($status === 'success'): ?>
                                        <a href="login.php" class="btn btn-primary text-white mt-3">Login Now</a>
                                        <?php endif; ?>

                                        <div class="mt-3">
                                            <a href="index.php" class="d-block text-muted">Return to Home</a>
                                        </div>
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