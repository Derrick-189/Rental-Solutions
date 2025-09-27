<?php
session_start();
require_once __DIR__ . '/db.php'; // Database connection

$page_title = "Welcome to Rental Solutions";
require_once __DIR__ . '/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container text-center">
        <h1 class="display-4">Find Your Perfect Student Hostel</h1>
        <p class="lead">Search for affordable hostels near your university campus</p>
        <a href="search.php" class="btn btn-light btn-lg mt-3">Browse Hostels</a>
    </div>
</section>

<!-- Content Section -->
<section class="container my-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-house-door fs-1"></i>
                    <h3 class="mt-3">Explore Hostels</h3>
                    <p>Discover comfortable accommodations near your university.</p>
                    <a href="search.php" class="btn btn-outline-primary">View Hostels</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-percent fs-1 text-primary"></i>
                    <h3 class="mt-3">Special Offers</h3>
                    <p>Check out discounted rates and special deals for students.</p>
                    <a href="search.php?discount=true" class="btn btn-outline-primary">See Offers</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
    <div class="card-body text-center">
        <i class="bi bi-journal-check fs-1 text-primary"></i>
        <h3 class="mt-3">Manage Booking</h3>
        <p>View or modify your existing hostel reservations.</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php
            // Get user type from session
            $user_type = $_SESSION['user_type'] ?? '';
            
            // Determine dashboard based on user type
            $dashboard_url = 'dashboard.php'; // Default to student dashboard
            if ($user_type === 'landlord') {
                $dashboard_url = 'l_dashboard.php';
            } elseif ($user_type === 'admin') {
                $dashboard_url = 'admin_dashboard.php';
            }
            ?>
            <a href="<?php echo $dashboard_url; ?>" class="btn btn-outline-primary">
                <?php echo ($user_type === 'student') ? 'My Bookings' : 'Go to Dashboard'; ?>
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline-primary">Login to View</a>
        <?php endif; ?>
    </div>
</div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>