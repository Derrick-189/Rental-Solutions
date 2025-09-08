<?php
require_once __DIR__ . '/auth.php';
check_user_role(['admin']);
$page_title = "Admin - Payments";
require_once __DIR__ . '/header.php';

global $conn;
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Payment Management</h1>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">All Payments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Booking ID</th>
                                    <th>Student</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT p.*, u.full_name, b.booking_id
                                          FROM payments p
                                          JOIN bookings b ON p.booking_id = b.booking_id
                                          JOIN users u ON b.student_id = u.user_id
                                          ORDER BY p.payment_date DESC";
                                $result = $conn->query($query);
                                
                                while($payment = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $payment['payment_id'] ?></td>
                                    <td><?= $payment['booking_id'] ?></td>
                                    <td><?= htmlspecialchars($payment['full_name']) ?></td>
                                    <td>UGX <?= number_format($payment['amount']) ?></td>
                                    <td><?= ucfirst($payment['payment_method']) ?></td>
                                    <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td>
                                        <span class="badge <?= $payment['status'] === 'completed' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= ucfirst($payment['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="admin_payment_details.php?id=<?= $payment['payment_id'] ?>" class="btn btn-sm btn-primary">Details</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>