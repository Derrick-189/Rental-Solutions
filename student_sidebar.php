<?php
require_once __DIR__ . '/auth.php';
check_user_role(['student']);
?>

<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <img src="<?php echo get_user_avatar($_SESSION['user_id']); ?>" 
                 class="rounded-circle mb-2" width="80" height="80" alt="Profile">
            <h6><?php echo htmlspecialchars($_SESSION['full_name']); ?></h6>
            <small class="text-muted">Student</small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 's_dashboard.php' ? 'active' : ''; ?>" 
                   href="s_dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : ''; ?>" 
                   href="search.php">
                    <i class="bi bi-search me-2"></i>Find Hostels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 's_bookings.php' ? 'active' : ''; ?>" 
                   href="s_bookings.php">
                    <i class="bi bi-journal-bookmark me-2"></i>My Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'payment-history.php' ? 'active' : ''; ?>" 
                   href="payment-history.php">
                    <i class="bi bi-credit-card me-2"></i>Payment History
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 's_messages.php' ? 'active' : ''; ?>" 
                   href="s_messages.php">
                    <i class="bi bi-envelope me-2"></i>Messages
                    <span class="badge bg-primary rounded-pill float-end">
                        <?php echo get_unread_message_count($_SESSION['user_id']); ?>
                    </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>" 
                   href="profile.php">
                    <i class="bi bi-person me-2"></i>Profile
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>
</div>