<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';

$errors = [];
$success = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $errors[] = 'Invalid or missing token.';
} else {
    // Look up token
    $q = "SELECT pr.user_id, pr.expires_at, u.email, u.full_name FROM password_resets pr JOIN users u ON pr.user_id = u.user_id WHERE pr.token = ? LIMIT 1";
    $stmt = $conn->prepare($q);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $errors[] = 'Invalid or expired token.';
    } else {
        $row = $res->fetch_assoc();
        $user_id = (int)$row['user_id'];
        $expires_at = strtotime($row['expires_at']);
        if ($expires_at < time()) {
            $errors[] = 'This reset link has expired. Please request a new one.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        // Update user password
        $uq = "UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?";
        $us = $conn->prepare($uq);
        $us->bind_param('si', $hashed, $user_id);
        if ($us->execute()) {
            // Clear forced reset flag if present
            @mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS must_reset_password TINYINT(1) NOT NULL DEFAULT 0");
            $cr = $conn->prepare("UPDATE users SET must_reset_password = 0 WHERE user_id = ?");
            $cr->bind_param('i', $user_id);
            $cr->execute();
            // Delete token after use
            $dq = "DELETE FROM password_resets WHERE user_id = ? AND token = ?";
            $ds = $conn->prepare($dq);
            $ds->bind_param('is', $user_id, $token);
            $ds->execute();
            $success = true;
        } else {
            $errors[] = 'Failed to update password. Please try again.';
        }
    }
}

$page_title = 'Reset Password';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Reset Password</h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">Your password has been reset successfully. You can now log in.</div>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
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

                    <?php if (empty($errors) || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
