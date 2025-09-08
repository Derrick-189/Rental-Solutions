<?php
require_once __DIR__ . '/auth.php';
check_user_role(['admin']);
$page_title = "Booking Details";
require_once __DIR__ . '/header.php';

if (!isset($_GET['id'])) {
    header('Location: admin_bookings.php');
    exit();
}

global $conn;
$booking_id = (int)$_GET['id'];

$query = "SELECT b.*, 
          u.full_name AS student_name,
          u.email AS student_email,
          u.phone AS student_phone,
          h.name AS hostel_name,
          h.address AS hostel_address,
          uni.name AS university_name
          FROM bookings b
          JOIN users u ON b.student_id = u.user_id
          JOIN hostels h ON b.hostel_id = h.hostel_id
          JOIN universities uni ON h.university_id = uni.university_id
          WHERE b.booking_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: admin_bookings.php');
    exit();
}

// Get payments for this booking
$payments_query = "SELECT * FROM payments WHERE booking_id = ? ORDER BY payment_date DESC";
$payments_stmt = $conn->prepare($payments_query);
$payments_stmt->bind_param("i", $booking_id);
$payments_stmt->execute();
$payments = $payments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Booking Details</h1>
                    <a href="admin_bookings.php" class="btn btn-outline-secondary">Back to Bookings</a>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Booking #<?= $booking['booking_id'] ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Booking Information</h6>
                                <p><strong>Status:</strong> 
                                    <?php 
                                    $badge_class = [
                                        'pending' => 'bg-warning',
                                        'confirmed' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        'completed' => 'bg-secondary'
                                    ][$booking['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badge_class ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </p>
                                <p><strong>Dates:</strong> 
                                    <?= date('M j, Y', strtotime($booking['start_date'])) ?> - 
                                    <?= date('M j, Y', strtotime($booking['end_date'])) ?>
                                </p>
                                <p><strong>Total Amount:</strong> UGX <?= number_format($booking['total_amount']) ?></p>
                                <p><strong>Created:</strong> <?= date('M j, Y h:i A', strtotime($booking['created_at'])) ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Student Information</h6>
                                <p><strong>Name:</strong> <?= htmlspecialchars($booking['student_name']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($booking['student_email']) ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($booking['student_phone']) ?></p>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6>Hostel Information</h6>
                                <p><strong>Hostel:</strong> <?= htmlspecialchars($booking['hostel_name']) ?></p>
                                <p><strong>Address:</strong> <?= htmlspecialchars($booking['hostel_address']) ?></p>
                                <p><strong>University:</strong> <?= htmlspecialchars($booking['university_name']) ?></p>
                            </div>
                        </div>
                        
                        <?php if ($booking['special_requests']): ?>
                        <div class="border-top pt-3 mb-4">
                            <h6>Special Requests:</h6>
                            <p><?= nl2br(htmlspecialchars($booking['special_requests'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="border-top pt-3">
                            <h5 class="mb-3">Payment History</h5>
                            <?php if (empty($payments)): ?>
                                <div class="alert alert-info">No payments recorded for this booking</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Payment ID</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><?= $payment['payment_id'] ?></td>
                                                <td>UGX <?= number_format($payment['amount']) ?></td>
                                                <td><?= ucfirst($payment['payment_method']) ?></td>
                                                <td>
                                                    <span class="badge <?= $payment['status'] === 'completed' ? 'bg-success' : 'bg-warning' ?>">
                                                        <?= ucfirst($payment['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                                <td>
                                                    <a href="admin_payment_details.php?id=<?= $payment['payment_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="admin_edit_booking.php?id=<?= $booking['booking_id'] ?>" class="btn btn-primary">Edit Booking</a>
                                <?php if ($booking['status'] === 'pending'): ?>
                                    <a href="admin_confirm_booking.php?id=<?= $booking['booking_id'] ?>" class="btn btn-success">Confirm Booking</a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="admin_add_payment.php?booking_id=<?= $booking['booking_id'] ?>" class="btn btn-outline-success">Add Payment</a>
                                <?php if ($booking['status'] !== 'cancelled'): ?>
                                    <a href="admin_cancel_booking.php?id=<?= $booking['booking_id'] ?>" class="btn btn-danger">Cancel Booking</a>
                                <?php endif; ?>
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