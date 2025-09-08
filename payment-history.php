<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Only logged in users can access this page
require_login();
$user = get_logged_in_user();

$page_title = "Payment History";
require_once __DIR__ . '/header.php';

// Get user's payment history
$query = "SELECT p.*, b.booking_id, h.name AS hostel_name, 
                 u.name AS university_name, b.start_date, b.end_date
          FROM payments p
          JOIN bookings b ON p.booking_id = b.booking_id
          JOIN hostels h ON b.hostel_id = h.hostel_id
          JOIN universities u ON h.university_id = u.university_id
          WHERE b.student_id = ?
          ORDER BY p.payment_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <?php if ($_SESSION['user_type'] === 'student'): ?>
            <?php require_once __DIR__ . '/student_sidebar.php'; ?>
        <?php elseif ($_SESSION['user_type'] === 'landlord'): ?>
            <?php require_once __DIR__ . '/landlord_sidebar.php'; ?>
        <?php endif; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Payment History</h1>
                <?php if ($_SESSION['user_type'] === 'student'): ?>
                    <a href="search.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Book New Hostel
                    </a>
                <?php endif; ?>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">All Payments</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="alert alert-info">
                            No payment records found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Booking ID</th>
                                        <th>Hostel</th>
                                        <th>University</th>
                                        <th>Dates</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?= $payment['payment_id'] ?></td>
                                            <td><?= $payment['booking_id'] ?></td>
                                            <td>
                                                <a href="hostel-details.php?id=<?= $payment['hostel_id'] ?>">
                                                    <?= htmlspecialchars($payment['hostel_name']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($payment['university_name']) ?></td>
                                            <td>
                                                <?= date('M j, Y', strtotime($payment['start_date'])) ?> -<br>
                                                <?= date('M j, Y', strtotime($payment['end_date'])) ?>
                                            </td>
                                            <td class="text-success">UGX <?= number_format($payment['amount']) ?></td>
                                            <td><?= ucfirst($payment['payment_method']) ?></td>
                                            <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                            <td>
                                                <span class="badge <?= $payment['status'] === 'completed' ? 'bg-success' : 'bg-warning' ?>">
                                                    <?= ucfirst($payment['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="payment-details.php?id=<?= $payment['payment_id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                                <?php if ($payment['status'] === 'completed'): ?>
                                                    <a href="generate-receipt.php?id=<?= $payment['payment_id'] ?>" 
                                                       class="btn btn-sm btn-outline-secondary mt-1">
                                                        Receipt
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>