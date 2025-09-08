<?php
require_once __DIR__ . 'auth.php';
require_once __DIR__ . 'functions.php';

// Only students can view bookings
check_user_role(['student']);

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$booking_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Get booking details
$query = "SELECT b.*, h.name AS hostel_name, h.address AS hostel_address, 
                 u.name AS university_name, p.transaction_id
          FROM bookings b
          JOIN hostels h ON b.hostel_id = h.hostel_id
          JOIN universities u ON h.university_id = u.university_id
          LEFT JOIN payments p ON b.booking_id = p.booking_id
          WHERE b.booking_id = ? AND b.student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$booking = $result->fetch_assoc();

$page_title = "Booking Confirmation";
require_once __DIR__ . 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Booking Confirmed!</h4>
            </div>
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h2 class="mt-3">Thank You!</h2>
                    <p class="lead">Your booking has been confirmed successfully.</p>
                </div>
                
                <div class="mb-4 text-start">
                    <h5>Booking Details</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>Booking ID</th>
                            <td><?php echo $booking_id; ?></td>
                        </tr>
                        <tr>
                            <th>Hostel</th>
                            <td><?php echo htmlspecialchars($booking['hostel_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?php echo htmlspecialchars($booking['hostel_address']); ?></td>
                        </tr>
                        <tr>
                            <th>University</th>
                            <td><?php echo htmlspecialchars($booking['university_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Dates</th>
                            <td>
                                <?php echo date('M j, Y', strtotime($booking['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Total Amount</th>
                            <td>UGX <?php echo number_format($booking['total_amount']); ?></td>
                        </tr>
                        <tr>
                            <th>Transaction ID</th>
                            <td><?php echo htmlspecialchars($booking['transaction_id']); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-success">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="alert alert-info">
                    <h5><i class="bi bi-info-circle"></i> Next Steps</h5>
                    <ul class="text-start">
                        <li>You will receive a confirmation email with your booking details</li>
                        <li>Contact the landlord to arrange key pickup and move-in details</li>
                        <li>You can view and manage your booking in your <a href="dashboard.php">dashboard</a></li>
                    </ul>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <a href="dashboard.php" class="btn btn-primary me-md-2">
                        <i class="bi bi-house-door"></i> Go to Dashboard
                    </a>
                    <a href="search.php" class="btn btn-outline-secondary">
                        <i class="bi bi-search"></i> Find More Hostels
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . 'footer.php'; ?>