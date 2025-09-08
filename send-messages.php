<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$page_title = "Send Message";
require_once __DIR__ . '/header.php';

global $conn;

// Initialize variables
$error = '';
$success = '';
$recipients = [];
$subject = '';
$message = '';

// Get list of possible recipients (excluding current user)
$current_user_id = $_SESSION['user_id'];
$query = "SELECT user_id, full_name, user_type FROM users WHERE user_id != ? ORDER BY full_name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$all_users = $result->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $recipient_ids = $_POST['recipients'] ?? [];
    $subject = sanitize_input($conn, $_POST['subject'] ?? '');
    $message = sanitize_input($conn, $_POST['message'] ?? '');
    
    // Basic validation
    if (empty($recipient_ids)) {
        $error = "Please select at least one recipient.";
    } elseif (empty($subject)) {
        $error = "Subject is required.";
    } elseif (empty($message)) {
        $error = "Message content is required.";
    } elseif (strlen($subject) > 100) {
        $error = "Subject must be less than 100 characters.";
    } else {
        try {
            $conn->begin_transaction();
            
            // Prepare the insert statement
            $insert_query = "INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) 
                             VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_query);
            
            // Send to each recipient
            $success_count = 0;
            foreach ($recipient_ids as $recipient_id) {
                $recipient_id = (int)$recipient_id;
                $stmt->bind_param("iiss", $current_user_id, $recipient_id, $subject, $message);
                if ($stmt->execute()) {
                    $success_count++;
                }
            }
            
            $conn->commit();
            
            if ($success_count > 0) {
                $success = "Message sent successfully to $success_count recipient(s).";
                // Clear form on success
                $subject = '';
                $message = '';
            } else {
                $error = "Failed to send message to any recipients.";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error sending message: " . $e->getMessage();
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php require_once __DIR__ . '/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Send New Message</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="send-message.php">
                        <div class="mb-3">
                            <label for="recipients" class="form-label">Recipients</label>
                            <select multiple class="form-select" id="recipients" name="recipients[]" required>
                                <?php foreach ($all_users as $user): ?>
                                    <option value="<?= $user['user_id'] ?>" <?= in_array($user['user_id'], $recipient_ids ?? []) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['full_name']) ?> (<?= ucfirst($user['user_type']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple recipients</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="<?= htmlspecialchars($subject) ?>" required maxlength="100">
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required><?= htmlspecialchars($message) ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="messages.php" class="btn btn-secondary">Back to Messages</a>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize select2 for better multi-select UI
$(document).ready(function() {
    $('#recipients').select2({
        placeholder: "Select recipients",
        allowClear: true,
        width: '100%'
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>