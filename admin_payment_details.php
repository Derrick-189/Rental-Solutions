<?php
require_once __DIR__ . '/auth.php';
check_user_role(['admin']);
$page_title = "Payment Details";
require_once __DIR__ . '/header.php';

if (!isset($_GET['id'])) {
    header('Location: admin_payments.php');
    exit();
}

global $conn;
$payment_id = (int)$_GET['id'];

$query = "SELECT p.*, 
          u.full_name AS student_name,
          u.email AS student_email,
          h.name AS hostel_name,
          b.start_date, b.end_date
          FROM payments p
          JOIN bookings b ON p.booking_id = b.booking_id
          JOIN users u ON b.student_id = u.user_id
          JOIN hostels h ON b.hostel_id = h.hostel_id
          WHERE p.payment_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    header('Location: admin_payments.php');
    exit();
}
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Payment Details</h1>
                <a href="admin_payments.php" class="btn btn-outline-secondary">Back to Payments</a>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Payment #<?= $payment['payment_id'] ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Payment Information</h6>
                            <p><strong>Amount:</strong> UGX <?= number_format($payment['amount']) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge <?= $payment['status'] === 'completed' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </p>
                            <p><strong>Method:</strong> <?= ucfirst($payment['payment_method']) ?></p>
                            <p><strong>Transaction ID:</strong> <?= $payment['transaction_id'] ?></p>
                            <p><strong>Date:</strong> <?= date('M j, Y h:i A', strtotime($payment['payment_date'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Booking Information</h6>
                            <p><strong>Booking ID:</strong> <?= $payment['booking_id'] ?></p>
                            <p><strong>Hostel:</strong> <?= htmlspecialchars($payment['hostel_name']) ?></p>
                            <p><strong>Dates:</strong> 
                                <?= date('M j, Y', strtotime($payment['start_date'])) ?> - 
                                <?= date('M j, Y', strtotime($payment['end_date'])) ?>
                            </p>
                            <p><strong>Student:</strong> <?= htmlspecialchars($payment['student_name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($payment['student_email']) ?></p>
                        </div>
                    </div>
                    
                    <?php if ($payment['notes']): ?>
                    <div class="border-top pt-3">
                        <h6>Notes:</h6>
                        <p><?= nl2br(htmlspecialchars($payment['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="admin_edit_payment.php?id=<?= $payment['payment_id'] ?>" class="btn btn-primary">Edit Payment</a>
                    <?php if ($payment['status'] !== 'completed'): ?>
                        <a href="admin_mark_paid.php?id=<?= $payment['payment_id'] ?>" class="btn btn-success">Mark as Paid</a>
                    <?php endif; ?>
                    <a href="admin_generate_receipt.php?id=<?= $payment['payment_id'] ?>" class="btn btn-outline-secondary">Generate Receipt</a>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>