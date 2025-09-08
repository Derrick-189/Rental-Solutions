<?php
require_once __DIR__ . '/auth.php';

// Only landlords can access this page
check_user_role(['landlord']);
$user = get_current_user();

if (!isset($_GET['id'])) {
    header('Location: l_messages.php');
    exit();
}

global $conn;
$message_id = (int)$_GET['id'];

// Get message details
$query = "SELECT m.*, 
          u.full_name AS sender_name,
          u.email AS sender_email,
          u.user_type AS sender_type
          FROM messages m
          JOIN users u ON m.sender_id = u.user_id
          WHERE m.message_id = ? AND m.receiver_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $message_id, $user['user_id']);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();

if (!$message) {
    header('Location: l_messages.php');
    exit();
}

// Mark message as read
$update_query = "UPDATE messages SET is_read = TRUE WHERE message_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $message_id);
$update_stmt->execute();

// Handle reply submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = "Re: " . sanitize_input($conn, $message['subject']);
    $reply_content = sanitize_input($conn, $_POST['reply_content']);
    
    if (empty($reply_content)) {
        $errors[] = "Reply content is required";
    }
    
    if (empty($errors)) {
        $insert_query = "INSERT INTO messages (sender_id, receiver_id, subject, message)
                         VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiss", $user['user_id'], $message['sender_id'], $subject, $reply_content);
        
        if ($insert_stmt->execute()) {
            $success = true;
            header("Location: view-message.php?id=$message_id");
            exit();
        } else {
            $errors[] = "Error sending reply: " . $conn->error;
        }
    }
}

$page_title = "View Message";
require_once __DIR__ . '/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php require_once __DIR__ . 'landlord_sidebar.php'; ?>
        </div>
        
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Message Details</h5>
                    <a href="l_messages.php" class="btn btn-sm btn-light">Back to Messages</a>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6><?= htmlspecialchars($message['subject']) ?></h6>
                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-muted">From: <?= htmlspecialchars($message['sender_name']) ?> (<?= ucfirst($message['sender_type']) ?>)</small><br>
                                <small class="text-muted">Email: <?= htmlspecialchars($message['sender_email']) ?></small>
                            </div>
                            <div>
                                <small class="text-muted"><?= date('M j, Y h:i A', strtotime($message['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-top pt-3 pb-3">
                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Reply to Message</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Your reply has been sent successfully!
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" value="Re: <?= htmlspecialchars($message['subject']) ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">To</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($message['sender_name']) ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Your Reply</label>
                            <textarea class="form-control" name="reply_content" rows="5" required></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Send Reply</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>