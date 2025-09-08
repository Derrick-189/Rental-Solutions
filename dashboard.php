<?php
require_once __DIR__ . '/auth.php';

/*// Function to calculate time ago from a given timestamp
function time_ago($datetime) {
    $time = strtotime($datetime);
    $time_difference = time() - $time;

    if ($time_difference < 1) {
        return 'just now';
    }

    $condition = [
        12 * 30 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60 => 'month',
        24 * 60 * 60 => 'day',
        60 * 60 => 'hour',
        60 => 'minute',
        1 => 'second'
    ];

    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;

        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
        }
    }
}*/

// Only students can access this page
check_user_role(['student']);
$user = get_logged_in_user();

$page_title = "Student Dashboard";
require_once __DIR__ . '/header.php';

// Get student's bookings
global $conn;
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
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <img src="<?php echo !empty($user['profile_pic']) ? '/uploads/profile_pics/' . htmlspecialchars($user['profile_pic']) : '/assets/images/default-profile.png'; ?>" 
                     class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                <p class="text-muted">
                    <i class="bi bi-book"></i> <?php echo htmlspecialchars($user['university']); ?>
                </p>
                <p class="text-muted">
                    <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($user['phone']); ?>
                </p>
                <a href="profile.php" class="btn btn-outline-primary">Edit Profile</a>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="search.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-search me-2"></i> Find Hostels
                </a>
                <a href="messages.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-envelope me-2"></i> Messages
                </a>
                <a href="payment-history.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-credit-card me-2"></i> Payment History
                </a>
                <a href="terms.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-file-text me-2"></i> Terms & Conditions
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">My Bookings</h5>
                <a href="search.php" class="btn btn-sm btn-light">Book New Hostel</a>
            </div>
            <div class="card-body">
                <?php if (empty($bookings)): ?>
                    <div class="alert alert-info">
                        You haven't made any bookings yet. <a href="search.php">Find hostels</a> to get started.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Hostel</th>
                                    <th>University</th>
                                    <th>Dates</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['hostel_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['hostel_address']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['university_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($booking['start_date'])); ?> - 
                                            <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                                        </td>
                                        <td class="text-success">
                                            UGX <?php echo number_format($booking['total_amount']); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = [
                                                'pending' => 'bg-warning',
                                                'confirmed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                'completed' => 'bg-secondary'
                                            ][$booking['status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Recent Messages</h5>
            </div>
            <div class="card-body">
                <?php 
                // Get recent messages
                $query = "SELECT m.*, u.full_name AS sender_name 
                          FROM messages m 
                          JOIN users u ON m.sender_id = u.user_id 
                          WHERE m.receiver_id = ? 
                          ORDER BY m.created_at DESC 
                          LIMIT 3";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>
                
                <?php if (empty($messages)): ?>
                    <div class="alert alert-info">
                        You have no messages yet.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($messages as $message): ?>
                            <a href="messages.php?message_id=<?php echo $message['message_id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo !$message['is_read'] ? 'fw-bold' : ''; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                    <small><?php echo time_ago($message['created_at']); ?></small>
                                </div>
                                <p class="mb-1">From: <?php echo htmlspecialchars($message['sender_name']); ?></p>
                                <small class="text-muted"><?php echo substr($message['message'], 0, 100); ?>...</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="messages.php" class="btn btn-outline-primary">View All Messages</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>