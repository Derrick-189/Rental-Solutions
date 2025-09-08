<?php
require_once __DIR__ . '/auth.php';

// Only landlords can access this page
check_user_role(['landlord']);

$page_title = "Messages";
require_once __DIR__ . '/header.php';

global $conn;

// Fetch messages for the landlord
$landlord_id = $_SESSION['user_id'];
$messages_query = "SELECT m.*, s.full_name as sender_name 
                   FROM messages m
                   JOIN users s ON m.sender_id = s.user_id
                   WHERE m.receiver_id = ?
                   ORDER BY m.created_at DESC";
$stmt = $conn->prepare($messages_query);
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-5">
    <h2>Messages</h2>
    <?php if (empty($messages)): ?>
        <div class="alert alert-info">You have no messages.</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($messages as $message): ?>
                <a href="view_message.php?id=<?php echo $message['message_id']; ?>" class="list-group-item list-group-item-action">
                    <h5 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h5>
                    <p class="mb-1"><?php echo substr(htmlspecialchars($message['message']), 0, 50); ?>...</p>
                    <small class="text-muted">From: <?php echo htmlspecialchars($message['sender_name']); ?> | <?php echo time_ago($message['created_at']); ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
