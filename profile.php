<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Only logged in users can access this page
require_login();
$user = get_logged_in_user();

if (!$user || !is_array($user)) {
    $_SESSION['error'] = "User data not found. Please login again.";
    header("Location: login.php");
    exit();
}

$page_title = "My Profile";
require_once __DIR__ . '/header.php';

$errors = [];
$success = false;

// Initialize variables with proper null checks
$full_name = $user['full_name'] ?? '';
$phone = $user['phone'] ?? '';
$university = $user['university'] ?? '';

// Handle AJAX profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_request'])) {
    header('Content-Type: application/json');
    
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/profile_pics/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['profile_pic']['tmp_name']);
        
        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed']);
            exit();
        }
        
        // Delete old profile picture if it exists
        if (!empty($user['profile_pic']) && file_exists($upload_dir . $user['profile_pic'])) {
            unlink($upload_dir . $user['profile_pic']);
        }
        
        // Generate unique filename
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $profile_pic = 'user_' . $user['user_id'] . '_' . time() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $profile_pic)) {
            // Update database
            $query = "UPDATE users SET profile_pic = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $profile_pic, $user['user_id']);
            
            if ($stmt->execute()) {
                // Get updated user data
                $query = "SELECT * FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $updated_user = $result->fetch_assoc();
                
                // Update session
                $_SESSION['user'] = $updated_user;
                $_SESSION['user_profile_pic'] = $profile_pic;
                
                echo json_encode([
                    'success' => true,
                    'newSrc' => '/uploads/profile_pics/' . $profile_pic . '?t=' . time()
                ]);
                exit();
            }
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Failed to upload profile picture']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_request'])) {
    // Sanitize inputs with null checks
    $full_name = !empty($_POST['full_name']) ? sanitize_input($conn, $_POST['full_name']) : '';
    $phone = !empty($_POST['phone']) ? sanitize_input($conn, $_POST['phone']) : '';
    $university = !empty($_POST['university']) ? sanitize_input($conn, $_POST['university']) : '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty(trim($full_name))) $errors[] = "Full name is required";
    if (empty(trim($phone))) $errors[] = "Phone number is required";
    if ($user['user_type'] === 'student' && empty(trim($university))) $errors[] = "University is required";
    
    // Handle password change if provided
    $password_changed = false;
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        } else {
            $password_changed = true;
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        }
    }
    
    if (empty($errors)) {
        // Update user in database
        if ($password_changed) {
            $query = "UPDATE users SET 
                      full_name = ?, 
                      phone = ?, 
                      university = ?, 
                      password = ?
                      WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "ssssi", 
                $full_name, 
                $phone, 
                $university, 
                $hashed_password, 
                $user['user_id']
            );
        } else {
            $query = "UPDATE users SET 
                      full_name = ?, 
                      phone = ?, 
                      university = ?
                      WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "sssi", 
                $full_name, 
                $phone, 
                $university, 
                $user['user_id']
            );
        }
        
        if ($stmt->execute()) {
            // Get updated user data
            $query = "SELECT * FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $updated_user = $result->fetch_assoc();
            
            // Update session
            $_SESSION['user'] = $updated_user;
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();
        } else {
            $errors[] = "Error updating profile: " . $conn->error;
        }
    }
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <form id="profilePicForm" method="POST" enctype="multipart/form-data" style="margin-bottom:2rem;">
        <div style="display:flex;align-items:center;justify-content:center;gap:2rem;flex-wrap:wrap;">
            <?php
            // Determine image source: absolute URL or local file
            $profilePicSrc = '/assets/images/default-profile.png';
            if (!empty($user['profile_pic'])) {
                if (filter_var($user['profile_pic'], FILTER_VALIDATE_URL)) {
                    // Absolute URL (external)
                    $profilePicSrc = $user['profile_pic'];
                } elseif (file_exists(__DIR__ . '/uploads/profile_pics/' . $user['profile_pic'])) {
                    // Local file
                    $profilePicSrc = '/uploads/profile_pics/' . htmlspecialchars($user['profile_pic']) . '?t=' . time();
                }
            }
            ?>
            <img id="profilePicPreview"
        src="<?php echo $profilePicSrc; ?>"
            alt="Profile Picture"
                style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #f7f8f8;background:#000;">
            <div style="text-align:left;">
    <label for="profile_pic" style="font-weight:600;display:block;margin-bottom:0.5rem;">Change Profile Picture</label>
    <input type="file" name="profile_pic" id="profile_pic" accept="image/*" style="display:none;">
    <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('profile_pic').click()">
        <i class="bi bi-camera"></i> Choose File
    </button>
    <div id="fileName" style="font-size:0.8rem;margin-top:0.5rem;color:#6c757d;">No file chosen</div>
</div>
        </div>
                </form>
                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                <p class="text-muted mb-1">
                    <i class="bi bi-person-badge"></i> <?php echo ucfirst($user['user_type']); ?>
                </p>
                <?php if ($user['user_type'] === 'student'): ?>
                    <p class="text-muted">
                        <i class="bi bi-book"></i> <?php echo htmlspecialchars($user['university']); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Account Security</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
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
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                    </div>
                    
                    <?php if ($user['user_type'] === 'student'): ?>
                        <div class="mb-3">
                            <label for="university" class="form-label">University</label>
                            <input type="text" class="form-control" id="university" name="university" value="<?php echo htmlspecialchars($university); ?>" required>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Profile picture upload with AJAX
document.getElementById('profile_pic').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Update file name display
    document.getElementById('fileName').textContent = file.name;
    
    // Client-side validation
    const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
        alert('Only JPG, PNG, or GIF images are allowed');
        this.value = '';
        document.getElementById('fileName').textContent = 'No file chosen';
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) {
        alert('File size must be less than 2MB');
        this.value = '';
        document.getElementById('fileName').textContent = 'No file chosen';
        return;
    }
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('profilePicPreview').src = e.target.result;
    }
    reader.readAsDataURL(file);
    
    // Upload via AJAX
    const formData = new FormData(document.getElementById('profilePicForm'));
    formData.append('ajax_request', true);
    
    fetch('profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update image with cache-busting timestamp
            const preview = document.getElementById('profilePicPreview');
            preview.src = data.newSrc;
            
            // Reset file input and name display
            document.getElementById('profile_pic').value = '';
            document.getElementById('fileName').textContent = 'No file chosen';
            
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                Profile picture updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Remove any existing alerts
            const existingAlerts = document.querySelectorAll('.alert.alert-success');
            existingAlerts.forEach(alert => alert.remove());
            
            document.querySelector('.card-body').prepend(alertDiv);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 150);
                }
            }, 3000);
        } else {
            alert(data.message || 'Failed to update profile picture');
            // Reset file input and name display on error
            document.getElementById('profile_pic').value = '';
            document.getElementById('fileName').textContent = 'No file chosen';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while uploading');
        // Reset file input and name display on error
        document.getElementById('profile_pic').value = '';
        document.getElementById('fileName').textContent = 'No file chosen';
    });
});

// Form validation for password change
document.querySelector('form').addEventListener('submit', function(e) {
    const currentPass = document.getElementById('current_password').value;
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    
    if ((currentPass || newPass || confirmPass) && (!currentPass || !newPass || !confirmPass)) {
        e.preventDefault();
        alert('To change password, all password fields must be filled');
        return;
    }
    
    if (newPass && confirmPass && newPass !== confirmPass) {
        e.preventDefault();
        alert('New passwords do not match');
        return;
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>