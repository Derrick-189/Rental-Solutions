<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

check_user_role(['admin']);
$user = get_logged_in_user();

$page_title = "Manage Users";
require_once __DIR__ . '/header.php';

// Get all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$users = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Initialize form variables
$show_form = false;
$form_user = [
    'user_id' => 0,
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => '',
    'user_type' => 'student',
    'university' => '',
    'verified' => false
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $username = sanitize_input($conn, $_POST['username']);
    $email = sanitize_input($conn, $_POST['email']);
    $full_name = sanitize_input($conn, $_POST['full_name']);
    $phone = sanitize_input($conn, $_POST['phone']);
    $user_type = sanitize_input($conn, $_POST['user_type']);
    $university = $user_type === 'student' ? sanitize_input($conn, $_POST['university']) : '';
    
    if ($user_id > 0) {
        // Update existing user
        $query = "UPDATE users SET username=?, email=?, full_name=?, phone=?, 
                user_type=?, university=? WHERE user_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssi", $username, $email, $full_name, 
                        $phone, $user_type, $university, $user_id);
    } else {
        // Create new user (generate random password)
        $password = bin2hex(random_bytes(8)); // Temporary password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, full_name, 
                phone, user_type, university, verified) 
            VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssss", $username, $email, $hashed_password, 
                        $full_name, $phone, $user_type, $university);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User " . ($user_id > 0 ? "updated" : "created") . " successfully";
        header("Location: admin_users.php");
        exit();
    } else {
        $_SESSION['error'] = "Error saving user: " . $conn->error;
    }
}

// Handle user actions
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'add') {
        $show_form = true;
        $page_title = "Add New User";
    } elseif (isset($_GET['id'])) {
        $action = $_GET['action'];
        $user_id = (int)$_GET['id'];
        
        if ($action === 'delete') {
            // Prevent deleting admin users
            $check_query = "SELECT user_type FROM users WHERE user_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $user_data = $result->fetch_assoc();
            
            if ($user_data && $user_data['user_type'] !== 'admin') {
                // Delete messages where user is sender or receiver
                $stmt = $conn->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
                $stmt->bind_param("ii", $user_id, $user_id);
                $stmt->execute();

                // Now delete the user
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "User deleted successfully";
                } else {
                    $_SESSION['error'] = "Error deleting user: " . $conn->error;
                }
            } else {
                $_SESSION['error'] = "Cannot delete admin users";
            }
            
            header("Location: admin_users.php");
            exit();
        } elseif ($action === 'verify') {
            $verify_query = "UPDATE users SET verified = TRUE WHERE user_id = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("i", $user_id);
            
            if ($verify_stmt->execute()) {
                $_SESSION['success'] = "User verified successfully";
            } else {
                $_SESSION['error'] = "Error verifying user: " . $conn->error;
            }
            
            header("Location: admin_users.php");
            exit();
        } elseif ($action === 'edit') {
            $query = "SELECT * FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $form_user = $result->fetch_assoc();
                $show_form = true;
                $page_title = "Edit User";
            } else {
                $_SESSION['error'] = "User not found";
                header("Location: admin_users.php");
                exit();
            }
        }
    }
}

// Display success/error messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Include all the CSS from style.css here */
        /* Same as in admin_booking_details.php */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .main-content {
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .btn {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .alert {
            border: none;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php require_once __DIR__ . '/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $page_title; ?></h2>
                    <?php if (!$show_form): ?>
                        <a href="admin_users.php?action=add" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add User
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($show_form): ?>
                <!-- User form -->
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="admin_users.php">
                            <input type="hidden" name="user_id" value="<?php echo $form_user['user_id']; ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                    value="<?php echo htmlspecialchars($form_user['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                    value="<?php echo htmlspecialchars($form_user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                    value="<?php echo htmlspecialchars($form_user['full_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                    value="<?php echo htmlspecialchars($form_user['phone']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="user_type" class="form-label">User Type</label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <option value="student" <?php echo $form_user['user_type'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                    <option value="landlord" <?php echo $form_user['user_type'] === 'landlord' ? 'selected' : ''; ?>>Landlord</option>
                                    <option value="admin" <?php echo $form_user['user_type'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="mb-3" id="university_field" style="display: <?php echo $form_user['user_type'] === 'student' ? 'block' : 'none'; ?>;">
                                <label for="university" class="form-label">University</label>
                                <input type="text" class="form-control" id="university" name="university" 
                                    value="<?php echo htmlspecialchars($form_user['university']); ?>">
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save User
                                </button>
                                <a href="admin_users.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    // Toggle university field based on user type
                    document.getElementById('user_type').addEventListener('change', function() {
                        var universityField = document.getElementById('university_field');
                        if (this.value === 'student') {
                            universityField.style.display = 'block';
                        } else {
                            universityField.style.display = 'none';
                        }
                    });
                </script>
                <?php endif; ?>

                <?php if (!$show_form): ?>
                <!-- Users table -->
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Full Name</th>
                                    <th>Phone</th>
                                    <th>User Type</th>
                                    <th>University</th>
                                    <th>Verified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $user['user_type'] === 'admin' ? 'danger' : 
                                                ($user['user_type'] === 'landlord' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($user['user_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['university']); ?></td>
                                    <td>
                                        <?php if ($user['verified']): ?>
                                            <span class="badge bg-success">Yes</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="admin_users.php?action=edit&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if ($user['user_type'] !== 'admin'): ?>
                                        <a href="admin_users.php?action=delete&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete this user?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!$user['verified']): ?>
                                        <a href="admin_users.php?action=verify&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-circle"></i> Verify
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
