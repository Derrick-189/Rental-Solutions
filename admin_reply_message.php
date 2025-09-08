<?php
require_once __DIR__ . '/auth.php';
check_user_role(['admin']);
$page_title = "Reply to Message";
require_once __DIR__ . '/header.php';

if (!isset($_GET['id'])) {
    header('Location: admin_messages.php');
    exit();
}

global $conn;
$original_message_id = (int)$_GET['id'];

// Get original message details
$query = "SELECT m.*, 
          u1.full_name AS sender_name, 
          u1.email AS sender_email,
          u1.user_id AS sender_id,
          u2.full_name AS receiver_name,
          u2.user_id AS receiver_id
          FROM messages m
          JOIN users u1 ON m.sender_id = u1.user_id
          JOIN users u2 ON m.receiver_id = u2.user_id
          WHERE m.message_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $original_message_id);
$stmt->execute();
$original_message = $stmt->get_result()->fetch_assoc();

if (!$original_message) {
    header('Location: admin_messages.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitize_input($conn, $_POST['subject']);
    $message_content = sanitize_input($conn, $_POST['message']);
    $receiver_id = (int)$_POST['receiver_id'];
    
    // Insert the new message
    $insert_query = "INSERT INTO messages (sender_id, receiver_id, subject, message) 
                     VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("iiss", $_SESSION['user_id'], $receiver_id, $subject, $message_content);
    
    if ($insert_stmt->execute()) {
        $_SESSION['success'] = "Message sent successfully";
        header("Location: admin_messages.php");
        exit();
    } else {
        $error_message = "Error sending message: " . $conn->error;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Reply to Message</h1>
                <a href="admin_messages.php" class="btn btn-outline-secondary">Back to Messages</a>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Replying to: <?= htmlspecialchars($original_message['subject']) ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="receiver_id" value="<?= $original_message['sender_id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" 
                                   value="Re: <?= htmlspecialchars($original_message['subject']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Original Message</label>
                            <div class="border p-3 bg-light mb-3">
                                <p><strong>From:</strong> <?= htmlspecialchars($original_message['sender_name']) ?></p>
                                <p><strong>Date:</strong> <?= date('M j, Y h:i A', strtotime($original_message['created_at'])) ?></p>
                                <hr>
                                <?= nl2br(htmlspecialchars($original_message['message'])) ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Your Reply</label>
                            <textarea class="form-control" name="message" rows="6" required></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Send Reply</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>