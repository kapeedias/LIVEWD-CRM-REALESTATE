<?php
// ==== CONFIG & DEPENDENCIES ====
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/classes/User.php';

secureSessionStart();
enforceSessionSecurity();



?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('_include/head.php'); ?>

<body class="sidebar-dark">
    <div class="main-wrapper">

        <!-- Start Side Navigation -->
        <?php require_once('_include/nav_side.php'); ?>
        <!-- End Side Navigation -->

        <div class="page-wrapper">

            <!-- Start Top Navigation -->
            <?php require_once('_include/nav_top.php'); ?>
            <!-- End Top Navigation -->

            <div class="page-content">

                <?php
                      /* echo htmlspecialchars($_SESSION['user_id']) . "<br />"; -->
                      echo htmlspecialchars($_SESSION['user_name']) . "<br />";
                      echo htmlspecialchars($_SESSION['user_email']) . "<br />"; */ 
                ?>

            </div>
            <!-- Start Inner Footer -->
            <?php require_once('_include/inner-footer.php'); ?>
            <!-- End Inner Footer -->

        </div>
    </div>

    <!-- core:js -->
    <script src="assets/vendors/core/core.js"></script>
    <!-- endinject -->
    <!-- plugin js for this page -->
    <script src="assets/vendors/chartjs/Chart.min.js"></script>
    <script src="assets/vendors/jquery.flot/jquery.flot.js"></script>
    <script src="assets/vendors/jquery.flot/jquery.flot.resize.js"></script>
    <script src="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="assets/vendors/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendors/progressbar.js/progressbar.min.js"></script>
    <!-- end plugin js for this page -->
    <!-- inject:js -->
    <script src="assets/vendors/feather-icons/feather.min.js"></script>
    <script src="assets/js/template.js"></script>
    <!-- endinject -->
    <!-- custom js for this page -->
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/datepicker.js"></script>
    <!-- end custom js for this page -->
</body>

</html>