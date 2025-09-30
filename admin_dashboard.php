<?php
require_once __DIR__ . '/auth.php';

// Only admins can access this page
check_user_role(['admin']);
$user = get_logged_in_user();

$page_title = "Admin Dashboard";
require_once __DIR__ . '/header.php';

global $conn;

// Get stats for dashboard
$users_query = "SELECT COUNT(*) as total_users FROM users";
$students_query = "SELECT COUNT(*) as total_students FROM users WHERE user_type = 'student'";
$landlords_query = "SELECT COUNT(*) as total_landlords FROM users WHERE user_type = 'landlord'";
$hostels_query = "SELECT COUNT(*) as total_hostels FROM hostels";
$bookings_query = "SELECT COUNT(*) as total_bookings FROM bookings";
$revenue_query = "SELECT SUM(platform_fee) as total_revenue FROM payments WHERE status = 'completed'";

$stats = [
    'total_users' => $conn->query($users_query)->fetch_assoc()['total_users'],
    'total_students' => $conn->query($students_query)->fetch_assoc()['total_students'],
    'total_landlords' => $conn->query($landlords_query)->fetch_assoc()['total_landlords'],
    'total_hostels' => $conn->query($hostels_query)->fetch_assoc()['total_hostels'],
    'total_bookings' => $conn->query($bookings_query)->fetch_assoc()['total_bookings'],
    'total_revenue' => $conn->query($revenue_query)->fetch_assoc()['total_revenue'] ?? 0,
];

// Get recent bookings
$recent_bookings_query = "SELECT b.*, h.name as hostel_name, u.name as university_name, 
                                 s.full_name as student_name, s.phone as student_phone
                          FROM bookings b
                          JOIN hostels h ON b.hostel_id = h.hostel_id
                          JOIN universities u ON h.university_id = u.university_id
                          JOIN users s ON b.student_id = s.user_id
                          ORDER BY b.created_at DESC
                          LIMIT 5";
$recent_bookings = $conn->query($recent_bookings_query)->fetch_all(MYSQLI_ASSOC);

// Get recent messages
$recent_messages_query = "SELECT m.*, s.full_name as sender_name, r.full_name as receiver_name
                          FROM messages m
                          JOIN users s ON m.sender_id = s.user_id
                          JOIN users r ON m.receiver_id = r.user_id
                          ORDER BY m.created_at DESC
                          LIMIT 5";
$recent_messages = $conn->query($recent_messages_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* modern-style.css */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
    overflow-x: hidden;
    background: #f8f9fa;
}

/* Header Styles */
header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1rem 0;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 2rem;
}

.logo {
    font-size: 2rem;
    font-weight: bold;
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

nav ul {
    display: flex;
    list-style: none;
    gap: 2rem;
}

nav a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
}

nav a:hover {
    color: #ffd700;
    transform: translateY(-2px);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-primary {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

/* Main Content */
.main-content {
    margin-top: 80px;
    padding: 2rem;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

/* Cards */
.card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 60px rgba(0,0,0,0.15);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
}

.card-body {
    padding: 2rem;
}

/* Tables */
.table {
    width: 100%;
    border-collapse: collapse;
}

.table th, .table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
}

/* Forms */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #667eea;
}

.form-control {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e1e8ed;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-success {
    background: linear-gradient(45deg, #25d366, #128C7E);
    color: white;
}

.btn-danger {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white;
}

.btn-info {
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-outline-primary {
    background: transparent;
    border: 2px solid #667eea;
    color: #667eea;
}

.btn-outline-primary:hover {
    background: #667eea;
    color: white;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 0.35rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 700;
    line-height: 1;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
}

.bg-primary {
    background-color: #667eea !important;
}

.bg-success {
    background-color: #25d366 !important;
}

.bg-warning {
    background-color: #ffc107 !important;
}

.bg-danger {
    background-color: #ee5a24 !important;
}

.bg-secondary {
    background-color: #6c757d !important;
}

/* Alerts */
.alert {
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
}

/* Responsive Design */
@media (max-width: 768px) {
    .header-container {
        flex-direction: column;
        padding: 1rem;
    }
    
    nav ul {
        flex-direction: column;
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .main-content {
        padding: 1rem;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
}

/* Animations */
.scroll-reveal {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.8s ease;
}

.scroll-reveal.revealed {
    opacity: 1;
    transform: translateY(0);
}
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Admin Dashboard</h2>
                    <div>
                        <a href="admin_users.php" class="btn btn-primary me-2">
                            <i class="bi bi-people"></i> Manage Users
                        </a>
                        <a href="admin_bookings.php" class="btn btn-secondary me-2">
                            <i class="bi bi-journal-text"></i> Bookings
                        </a>
                        <a href="admin_messages.php" class="btn btn-info">
                            <i class="bi bi-envelope"></i> Messages
                        </a>
                    </div>
                </div>

                <div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Total Users</h6>
                        <h3><?php echo $stats['total_users']; ?></h3>
                    </div>
                    <i class="bi bi-people fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Students</h6>
                        <h3><?php echo $stats['total_students']; ?></h3>
                    </div>
                    <i class="bi bi-person-video2 fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50">Landlords</h6>
                        <h3><?php echo $stats['total_landlords']; ?></h3>
                    </div>
                    <i class="bi bi-house-door fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-dark-50">Hostels</h6>
                        <h3><?php echo $stats['total_hostels']; ?></h3>
                    </div>
                    <i class="bi bi-building fs-1"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Recent Bookings</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_bookings)): ?>
                    <div class="alert alert-info">
                        No recent bookings found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Hostel</th>
                                    <th>Student</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <small><?php echo htmlspecialchars($booking['hostel_name']); ?></small><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['university_name']); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($booking['student_name']); ?></small><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['student_phone']); ?></small>
                                        </td>
                                        <td class="text-success">
                                            <small>UGX <?php echo number_format($booking['total_amount']); ?></small>
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
                                                <small><?php echo ucfirst($booking['status']); ?></small>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <a href="admin_bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Recent Messages</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_messages)): ?>
                    <div class="alert alert-info">
                        No recent messages found.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recent_messages as $message): ?>
                            <a href="admin_messages.php?id=<?php echo $message['message_id']; ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                    <small><?php echo time_ago($message['created_at']); ?></small>
                                </div>
                                <p class="mb-1">
                                    <small>
                                        From: <?php echo htmlspecialchars($message['sender_name']); ?> 
                                        to <?php echo htmlspecialchars($message['receiver_name']); ?>
                                    </small>
                                </p>
                                <small class="text-muted"><?php echo substr($message['message'], 0, 50); ?>...</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-end mt-3">
                        <a href="admin_messages.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Platform Revenue</h5>
                <h5 class="mb-0">UGX <?php echo number_format($stats['total_revenue']); ?></h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>Total Bookings:</strong> <?php echo $stats['total_bookings']; ?>
                        </div>
                        <div>
                            <strong>Total Revenue:</strong> UGX <?php echo number_format($stats['total_revenue']); ?>
                        </div>
                        <div>
                            <strong>Average per Booking:</strong> UGX <?php echo number_format($stats['total_bookings'] > 0 ? $stats['total_revenue'] / $stats['total_bookings'] : 0); ?>
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    <a href="admin_payments.php" class="btn btn-primary">View Payment Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>
                
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php require_once __DIR__ . '/footer.php'; ?>