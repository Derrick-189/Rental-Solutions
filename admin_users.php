<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

check_user_role(['admin']);
$user = get_current_user();

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
                $delete_query = "DELETE FROM users WHERE user_id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("i", $user_id);
                
                if ($delete_stmt->execute()) {
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
        
        /* ... rest of the CSS styles ... */
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
                <!-- ... -->
                <?php endif; ?>

                <?php if (!$show_form): ?>
                <!-- Users table -->
                <!-- ... -->
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>