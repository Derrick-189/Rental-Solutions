<?php
require_once __DIR__ . '/auth.php';
check_user_role(['admin']);
$page_title = "Admin - Messages";
require_once __DIR__ . '/header.php';

global $conn;
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Message Management</h1>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">All Messages</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT m.*, 
                                          u1.full_name AS sender_name, 
                                          u2.full_name AS receiver_name
                                          FROM messages m
                                          JOIN users u1 ON m.sender_id = u1.user_id
                                          JOIN users u2 ON m.receiver_id = u2.user_id
                                          ORDER BY m.created_at DESC";
                                $result = $conn->query($query);
                                
                                while($message = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $message['message_id'] ?></td>
                                    <td><?= htmlspecialchars($message['sender_name']) ?></td>
                                    <td><?= htmlspecialchars($message['receiver_name']) ?></td>
                                    <td><?= htmlspecialchars($message['subject']) ?></td>
                                    <td><?= date('M j, Y h:i A', strtotime($message['created_at'])) ?></td>
                                    <td>
                                        <span class="badge <?= $message['is_read'] ? 'bg-success' : 'bg-warning' ?>">
                                            <?= $message['is_read'] ? 'Read' : 'Unread' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="admin_view_message.php?id=<?= $message['message_id'] ?>" class="btn btn-sm btn-primary">View</a>
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