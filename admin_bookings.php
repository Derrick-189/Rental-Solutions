<?php
require_once __DIR__ . '/auth.php';
check_user_role(['admin']);
$page_title = "Admin - Bookings";
require_once __DIR__ . '/header.php';

global $conn;
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Booking Management</h1>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <h5 class="mb-0">All Bookings</h5>
                    <div>
                        <a href="admin_generate_report.php?type=bookings" class="btn btn-sm btn-light">Generate Report</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Student</th>
                                    <th>Hostel</th>
                                    <th>Dates</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT b.*, 
                                          u.full_name, 
                                          h.name AS hostel_name
                                          FROM bookings b
                                          JOIN users u ON b.student_id = u.user_id
                                          JOIN hostels h ON b.hostel_id = h.hostel_id
                                          ORDER BY b.created_at DESC";
                                $result = $conn->query($query);
                                
                                while($booking = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $booking['booking_id'] ?></td>
                                    <td><?= htmlspecialchars($booking['full_name']) ?></td>
                                    <td><?= htmlspecialchars($booking['hostel_name']) ?></td>
                                    <td>
                                        <?= date('M j, Y', strtotime($booking['start_date'])) ?> - 
                                        <?= date('M j, Y', strtotime($booking['end_date'])) ?>
                                    </td>
                                    <td>UGX <?= number_format($booking['total_amount']) ?></td>
                                    <td>
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
                                    </td>
                                    <td><?= date('M j, Y', strtotime($booking['created_at'])) ?></td>
                                    <td>
                                        <a href="admin_booking_details.php?id=<?= $booking['booking_id'] ?>" class="btn btn-sm btn-primary">View</a>
                                        <a href="admin_edit_booking.php?id=<?= $booking['booking_id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
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