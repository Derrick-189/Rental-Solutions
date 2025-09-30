<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

check_user_role(['landlord']);
$user = get_logged_in_user();

$page_title = "Manage Bookings";
require_once __DIR__ . '/header.php';

// Get bookings for landlord's hostels
$query = "SELECT b.*, h.name AS hostel_name, u.full_name AS student_name, 
                 u.phone AS student_phone, u.university AS student_university
          FROM bookings b
          JOIN hostels h ON b.hostel_id = h.hostel_id
          JOIN users u ON b.student_id = u.user_id
          WHERE b.landlord_id = ?
          ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle booking actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $booking_id = (int)$_GET['id'];
    
    if ($action === 'confirm') {
        $update_query = "UPDATE bookings SET status = 'confirmed' WHERE booking_id = ? AND landlord_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Booking confirmed successfully";
            
            // Send notification to student
            $booking = get_booking($booking_id);
            $message = "Your booking for " . $booking['hostel_name'] . " has been confirmed. Booking ID: " . $booking_id;
            $notification_query = "INSERT INTO messages (sender_id, receiver_id, subject, message)
                                  VALUES (?, ?, ?, ?)";
            $notification_stmt = $conn->prepare($notification_query);
            $subject = "Booking Confirmed";
            $notification_stmt->bind_param("iiss", $_SESSION['user_id'], $booking['student_id'], 
                                          $subject, $message);
            $notification_stmt->execute();
        } else {
            $_SESSION['error'] = "Error confirming booking: " . $conn->error;
        }
        
        header("Location: l_bookings.php");
        exit();
    } elseif ($action === 'cancel') {
        $update_query = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND landlord_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Booking cancelled successfully";
            
            // Send notification to student
            $booking = get_booking($booking_id);
            $message = "Your booking for " . $booking['hostel_name'] . " has been cancelled. Booking ID: " . $booking_id;
            $notification_query = "INSERT INTO messages (sender_id, receiver_id, subject, message)
                                  VALUES (?, ?, ?, ?)";
            $notification_stmt = $conn->prepare($notification_query);
            $subject = "Booking Cancelled";
            $notification_stmt->bind_param("iiss", $_SESSION['user_id'], $booking['student_id'], 
                                          $subject, $message);
            $notification_stmt->execute();
        } else {
            $_SESSION['error'] = "Error cancelling booking: " . $conn->error;
        }
        
        header("Location: l_bookings.php");
        exit();
    }
}

// Display messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

// Helper function to get booking details
function get_booking($booking_id) {
    global $conn;
    $query = "SELECT b.*, h.name AS hostel_name 
              FROM bookings b
              JOIN hostels h ON b.hostel_id = h.hostel_id
              WHERE b.booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Bookings</h2>
    <div>
        <a href="l_bookings.php?action=export" class="btn btn-outline-secondary">
            <i class="bi bi-download"></i> Export
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">
                You have no bookings yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hostel</th>
                            <th>Student</th>
                            <th>Dates</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['booking_id']; ?></td>
                                <td>
                                    <a href="hostel-details.php?id=<?php echo $booking['hostel_id']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($booking['hostel_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['student_name']); ?><br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($booking['student_phone']); ?><br>
                                        <?php echo htmlspecialchars($booking['student_university']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($booking['start_date'])); ?> -<br>
                                    <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                                </td>
                                <td class="text-success">UGX <?php echo number_format($booking['total_amount']); ?></td>
                                <td>
                                    <?php echo ucfirst($booking['payment_method']); ?><br>
                                    <span class="badge bg-<?php 
                                        echo $booking['payment_status'] === 'paid' ? 'success' : 
                                             ($booking['payment_status'] === 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $booking['status'] === 'confirmed' ? 'success' : 
                                             ($booking['status'] === 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <a href="l_bookings.php?action=confirm&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-outline-success" title="Confirm">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                            <a href="l_bookings.php?action=cancel&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to cancel this booking?');"
                                               title="Cancel">
                                                <i class="bi bi-x-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" 
                                           class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>