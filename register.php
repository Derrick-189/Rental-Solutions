<?php
session_start();

// Database connection
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$page_title = "Register";
require_once __DIR__ . '/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($conn, $_POST['username']);
    $email = sanitize_input($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize_input($conn, $_POST['full_name']);
    $phone = sanitize_input($conn, $_POST['phone']);
    $user_type = sanitize_input($conn, $_POST['user_type']);
    $university = ($user_type === 'student') ? sanitize_input($conn, $_POST['university']) : null;
    
    // Validate inputs
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if ($user_type === 'student' && empty($university)) $errors[] = "University is required for students";
    
    // Check if username or email exists
    $query = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Username or email already exists";
    }
    
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $query = "INSERT INTO users (username, email, password, user_type, full_name, phone, university) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssss", $username, $email, $hashed_password, $user_type, $full_name, $phone, $university);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Error creating account: " . $conn->error;
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Create an Account</h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Account created successfully! <a href="login.php">Login here</a>.
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_type" class="form-label">User Type</label>
                        <select class="form-select" id="user_type" name="user_type" required>
                            <option value="">Select type</option>
                            <option value="student">Student</option>
                            <option value="landlord">Landlord</option>
                        </select>
                    </div>
                    <div class="mb-3" id="university-field" style="display:none;">
                        <label for="university" class="form-label">University</label>
                        <input type="text" class="form-control" id="university" name="university">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">I agree to the Terms and Conditions</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
                
                <div class="mt-3 text-center">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide university field based on user type
document.getElementById('user_type').addEventListener('change', function() {
    const universityField = document.getElementById('university-field');
    if (this.value === 'student') {
        universityField.style.display = 'block';
        document.getElementById('university').setAttribute('required', '');
    } else {
        universityField.style.display = 'none';
        document.getElementById('university').removeAttribute('required');
    }
});

// Form validation for password match
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (password.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
    
    if (!document.getElementById('terms').checked) {
        e.preventDefault();
        alert('You must agree to the Terms and Conditions');
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>