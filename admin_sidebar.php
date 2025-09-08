<?php
// Verify admin access
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}
?>
<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>" href="admin_dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admin_bookings.php' ? 'active' : '' ?>" href="admin_bookings.php">
                    <i class="bi bi-journal-check me-2"></i> Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admin_payments.php' ? 'active' : '' ?>" href="admin_payments.php">
                    <i class="bi bi-credit-card me-2"></i> Payments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admin_messages.php' ? 'active' : '' ?>" href="admin_messages.php">
                    <i class="bi bi-envelope me-2"></i> Messages
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admin_hostels.php' ? 'active' : '' ?>" href="admin_hostels.php">
                    <i class="bi bi-house me-2"></i> Hostels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admin_users.php' ? 'active' : '' ?>" href="admin_users.php">
                    <i class="bi bi-people me-2"></i> Users
                </a>
            </li>
        </ul>
        
        <hr>
        
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="admin_settings.php">
                    <i class="bi bi-gear me-2"></i> Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>