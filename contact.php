<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($conn, $_POST['name'] ?? '');
    $email = sanitize_input($conn, $_POST['email'] ?? '');
    $subject = sanitize_input($conn, $_POST['subject'] ?? '');
    $message = sanitize_input($conn, $_POST['message'] ?? '');
    
    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) {
        $errors[] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        // Save to database if logged in
        if (is_logged_in()) {
            $query = "INSERT INTO messages (sender_id, receiver_id, subject, message) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $admin_id = 1; // Assuming admin user_id is 1
            $stmt->bind_param("iiss", $_SESSION['user_id'], $admin_id, $subject, $message);
            $stmt->execute();
        }
        
        // Send email
        $to = "support@" . $_SERVER['HTTP_HOST'];
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        
        $email_body = "You have received a new contact form submission:\n\n";
        $email_body .= "Name: $name\n";
        $email_body .= "Email: $email\n\n";
        $email_body .= "Subject: $subject\n\n";
        $email_body .= "Message:\n$message\n";
        
        if (mail($to, $subject, $email_body, $headers)) {
            $success = true;
        } else {
            $errors[] = "Failed to send message. Please try again.";
        }
    }
}

$page_title = "Contact Us";
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Contact Us</h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Thank you for contacting us! We'll get back to you soon.
                    </div>
                    <div class="text-center">
                        <a href="index.php" class="btn btn-primary">Back to Home</a>
                    </div>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo is_logged_in() ? htmlspecialchars(get_logged_in_user()['full_name']) : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo is_logged_in() ? htmlspecialchars(get_logged_in_user()['email']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Send Message</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>