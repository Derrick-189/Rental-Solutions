<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Only students can view their bookings
check_user_role(['student']);
$user = get_logged_in_user();

$page_title = "Pending Bookings";
require_once __DIR__ . '/header.php';

// Get user's pending bookings
global $conn;
$query = "SELECT b.*, h.name AS hostel_name, h.address AS hostel_address, 
            u.name AS university_name
        FROM bookings b
        JOIN hostels h ON b.hostel_id = h.hostel_id
        JOIN universities u ON h.university_id = u.university_id
        WHERE b.student_id = ? AND b.status = 'pending' AND b.payment_status = 'pending'
        ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cancel_booking') {
        $booking_id = (int)$_POST['booking_id'];
        
        // Verify booking belongs to this student
        $verify_query = "SELECT booking_id FROM bookings WHERE booking_id = ? AND student_id = ? AND status = 'pending'";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
        $verify_stmt->execute();
        
        if ($verify_stmt->get_result()->num_rows > 0) {
            if (update_booking_status($booking_id, 'cancelled')) {
                $_SESSION['success'] = "Booking cancelled successfully!";
                header("Location: pending-bookings.php");
                exit();
            } else {
                $error = "Error cancelling booking. Please try again.";
            }
        } else {
            $error = "Booking not found or you don't have permission to cancel it.";
        }
    }
}

// Display success/error messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

if (isset($error)) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Pending Bookings</h2>
    <a href="s_bookings.php" class="btn btn-outline-primary">
        <i class="bi bi-list"></i> All Bookings
    </a>
</div>

<?php if (empty($pending_bookings)): ?>
    <div class="alert alert-info">
        <h4>No Pending Bookings</h4>
        <p>You don't have any pending bookings. <a href="search.php">Find hostels</a> to make a new booking.</p>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($pending_bookings as $booking): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge bg-warning">Pending Payment</span>
                        <small class="text-muted">#<?php echo $booking['booking_id']; ?></small>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($booking['hostel_name']); ?></h5>
                        <p class="card-text text-muted">
                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($booking['hostel_address']); ?>
                        </p>
                        <p class="card-text">
                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($booking['university_name']); ?>
                        </p>
                        
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Check-in:</small><br>
                                    <strong><?php echo date('M j, Y', strtotime($booking['start_date'])); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Check-out:</small><br>
                                    <strong><?php echo date('M j, Y', strtotime($booking['end_date'])); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Total Amount:</span>
                                <strong class="text-success">UGX <?php echo number_format($booking['total_amount']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Platform Fee:</span>
                                <span>UGX <?php echo number_format($booking['platform_fee']); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> Created <?php echo time_ago($booking['created_at']); ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-grid gap-2">
                            <a href="payment.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                               class="btn btn-success">
                                <i class="bi bi-credit-card"></i> Complete Payment
                            </a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                <input type="hidden" name="action" value="cancel_booking">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-x-circle"></i> Cancel Booking
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="alert alert-info mt-4">
    <h5><i class="bi bi-info-circle"></i> About Pending Bookings</h5>
    <ul class="mb-0">
        <li>Pending bookings are reserved for 24 hours</li>
        <li>Complete payment to confirm your booking</li>
        <li>You can cancel pending bookings at any time</li>
        <li>After 24 hours, pending bookings may be automatically cancelled</li>
    </ul>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
