<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($conn, $_POST['email'] ?? '');
    
    if (empty($email)) {
        $errors[] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email exists
        $query = "SELECT user_id, full_name FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = "No account found with that email address";
        } else {
            $user = $result->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
            
            // Store token in database
            $query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $user['user_id'], $token, $expires);
            
            if ($stmt->execute()) {
                // Send reset email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=$token";
                $subject = "Password Reset Request";
                $message = "Hello " . $user['full_name'] . ",\n\n";
                $message .= "You requested a password reset. Click the link below to reset your password:\n";
                $message .= $reset_link . "\n\n";
                $message .= "This link will expire in 1 hour.\n";
                $message .= "If you didn't request this, please ignore this email.\n";
                
                $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
                
                if (mail($email, $subject, $message, $headers)) {
                    $success = true;
                } else {
                    $errors[] = "Failed to send reset email. Please try again.";
                }
            } else {
                $errors[] = "Error processing your request. Please try again.";
            }
        }
    }
}

$page_title = "Forgot Password";
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Forgot Password</h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Password reset link has been sent to your email. Please check your inbox.
                    </div>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-primary">Back to Login</a>
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
                    
                    <p>Enter your email address and we'll send you a link to reset your password.</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="login.php">Remember your password? Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>