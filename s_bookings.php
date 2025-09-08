<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

check_user_role(['student']);
$user = get_current_user();

$page_title = "My Bookings";
require_once __DIR__ . '/header.php';

// Get student's bookings
$query = "SELECT b.*, h.name AS hostel_name, h.address AS hostel_address, 
                 u.name AS university_name, u.logo AS university_logo
          FROM bookings b
          JOIN hostels h ON b.hostel_id = h.hostel_id
          JOIN universities u ON h.university_id = u.university_id
          WHERE b.student_id = ?
          ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle booking cancellation
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $booking_id = (int)$_GET['id'];
    
    if ($action === 'cancel') {
        // Check if booking belongs to student
        $check_query = "SELECT * FROM bookings WHERE booking_id = ? AND student_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $update_query = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $booking_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Booking cancelled successfully";
                
                // Send notification to landlord
                $booking = $result->fetch_assoc();
                $message = "Booking #" . $booking_id . " has been cancelled by the student.";
                $notification_query = "INSERT INTO messages (sender_id, receiver_id, subject, message)
                                      VALUES (?, ?, ?, ?)";
                $notification_stmt = $conn->prepare($notification_query);
                $subject = "Booking Cancelled";
                $notification_stmt->bind_param("iiss", $_SESSION['user_id'], $booking['landlord_id'], 
                                              $subject, $message);
                $notification_stmt->execute();
            } else {
                $_SESSION['error'] = "Error cancelling booking: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Booking not found or you don't have permission to cancel it";
        }
        
        header("Location: s_bookings.php");
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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Bookings</h2>
    <a href="/search.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Book New Hostel
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">
                You haven't made any bookings yet. <a href="search.php">Find hostels</a> to get started.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hostel</th>
                            <th>University</th>
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
                                    <a href="hostel-details.php?id=<?php echo $booking['hostel_id']; ?>">
                                        <?php echo htmlspecialchars($booking['hostel_name']); ?>
                                    </a><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['hostel_address']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($booking['university_name']); ?></td>
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
                                        <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" 
                                           class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                            <a href="s_bookings.php?action=cancel&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to cancel this booking?');"
                                               title="Cancel">
                                                <i class="bi bi-x-circle"></i>
                                            </a>
                                        <?php endif; ?>
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