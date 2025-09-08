<?php
require_once __DIR__ . '/auth.php';

// Only landlords can access this page
check_user_role(['landlord']);

$page_title = "Landlord Dashboard";
require_once __DIR__ . '/header.php';

global $conn;

// Fetch landlord stats
$landlord_id = $_SESSION['user_id'];
$hostels_query = "SELECT COUNT(*) as total_hostels FROM hostels WHERE landlord_id = ?";
$bookings_query = "SELECT COUNT(*) as total_bookings FROM bookings WHERE landlord_id = ?";
$revenue_query = "SELECT SUM(total_amount) as total_revenue FROM bookings WHERE landlord_id = ? AND status = 'completed'";

$stmt = $conn->prepare($hostels_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$total_hostels = $stmt->get_result()->fetch_assoc()['total_hostels'];

$stmt = $conn->prepare($bookings_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['total_bookings'];

$stmt = $conn->prepare($revenue_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$total_revenue = $stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
?>

<div class="container mt-5">
    <h2>Landlord Dashboard</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Total Hostels</h5>
                    <h3><?php echo $total_hostels; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Total Bookings</h5>
                    <h3><?php echo $total_bookings; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5>Total Revenue</h5>
                    <h3>UGX <?php echo number_format($total_revenue); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <a href="listings.php" class="btn btn-primary">Manage Hostels</a>
        <a href="l_messages.php" class="btn btn-secondary">View Messages</a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
