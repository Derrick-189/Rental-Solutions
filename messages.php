<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

check_user_role(['student']);
$user = get_logged_in_user();

$page_title = "Messages";

// Get message threads
$query = "SELECT m.*, u.full_name AS sender_name, u.user_type AS sender_type
          FROM messages m
          JOIN users u ON m.sender_id = u.user_id
          WHERE m.receiver_id = ?
          ORDER BY m.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Mark messages as read when viewing
if (!empty($messages)) {
    $message_ids = array_column($messages, 'message_id');
    $placeholders = implode(',', array_fill(0, count($message_ids), '?'));
    
    $update_query = "UPDATE messages SET is_read = TRUE WHERE message_id IN ($placeholders)";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param(str_repeat('i', count($message_ids)), ...$message_ids);
    $update_stmt->execute();
}

// Handle message sending
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = (int)$_POST['receiver_id'];
    $subject = sanitize_input($conn, $_POST['subject']);
    $message = sanitize_input($conn, $_POST['message']);
    
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        $insert_query = "INSERT INTO messages (sender_id, receiver_id, subject, message)
                         VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiss", $_SESSION['user_id'], $receiver_id, $subject, $message);
        
        if ($insert_stmt->execute()) {
            $success = true;
            header("Location: messages.php");
            exit();
        } else {
            $errors[] = "Error sending message: " . $conn->error;
        }
    }
}
// Safe to start output now
$page_title = "Messages";
require_once __DIR__ . '/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Compose Message</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Message sent successfully!
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="messages.php">
                    <div class="mb-3">
                        <label for="receiver" class="form-label">To</label>
                        <select class="form-select" id="receiver" name="receiver_id" required>
                            <option value="">Select recipient</option>
                            <?php
                            // Get landlords the student has interacted with
                            $landlords_query = "SELECT DISTINCT u.user_id, u.full_name 
                                               FROM bookings b
                                               JOIN users u ON b.landlord_id = u.user_id
                                               WHERE b.student_id = ?";
                            $landlords_stmt = $conn->prepare($landlords_query);
                            $landlords_stmt->bind_param("i", $_SESSION['user_id']);
                            $landlords_stmt->execute();
                            $landlords = $landlords_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            foreach ($landlords as $landlord):
                            ?>
                                <option value="<?php echo $landlord['user_id']; ?>">
                                    <?php echo htmlspecialchars($landlord['full_name']); ?> (Landlord)
                                </option>
                            <?php endforeach; ?>
                            
                            <option value="1">Admin</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Send Message</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Inbox</h5>
                <span class="badge bg-light text-dark">
                    <?php echo count($messages); ?> messages
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($messages)): ?>
                    <div class="alert alert-info m-3">
                        You have no messages yet.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($messages as $message): ?>
                            <a href="#message-<?php echo $message['message_id']; ?>" 
                               class="list-group-item list-group-item-action" data-bs-toggle="collapse">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                    <small><?php echo time_ago($message['created_at']); ?></small>
                                </div>
                                <p class="mb-1">
                                    From: <?php echo htmlspecialchars($message['sender_name']); ?> 
                                    (<?php echo ucfirst($message['sender_type']); ?>)
                                </p>
                                
                                <div class="collapse" id="message-<?php echo $message['message_id']; ?>">
                                    <div class="mt-2 p-2 bg-light rounded">
                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>