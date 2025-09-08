<?php
require_once __DIR__ . '/auth.php';
check_user_role(['admin']);
$page_title = "View Message";
require_once __DIR__ . '/header.php';

if (!isset($_GET['id'])) {
    header('Location: admin_messages.php');
    exit();
}

global $conn;
$message_id = (int)$_GET['id'];

// Get message details
$query = "SELECT m.*, 
          u1.full_name AS sender_name, 
          u1.email AS sender_email,
          u2.full_name AS receiver_name,
          u2.email AS receiver_email
          FROM messages m
          JOIN users u1 ON m.sender_id = u1.user_id
          JOIN users u2 ON m.receiver_id = u2.user_id
          WHERE m.message_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $message_id);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();

if (!$message) {
    header('Location: admin_messages.php');
    exit();
}

// Mark as read
$update_query = "UPDATE messages SET is_read = 1 WHERE message_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $message_id);
$update_stmt->execute();
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Message Details</h1>
                <a href="admin_messages.php" class="btn btn-outline-secondary">Back to Messages</a>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?= htmlspecialchars($message['subject']) ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <p><strong>From:</strong> <?= htmlspecialchars($message['sender_name']) ?> (<?= htmlspecialchars($message['sender_email']) ?>)</p>
                        <p><strong>To:</strong> <?= htmlspecialchars($message['receiver_name']) ?> (<?= htmlspecialchars($message['receiver_email']) ?>)</p>
                        <p><strong>Date:</strong> <?= date('M j, Y h:i A', strtotime($message['created_at'])) ?></p>
                    </div>
                    <div class="border-top pt-3">
                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="admin_reply_message.php?id=<?= $message['message_id'] ?>" class="btn btn-primary">Reply</a>
                    <a href="admin_delete_message.php?id=<?= $message['message_id'] ?>" class="btn btn-danger float-end">Delete</a>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>