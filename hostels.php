<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

check_user_role(['admin']);
$user = get_current_user();

$page_title = "Manage Hostels";
require_once __DIR__ . '/../../includes/header.php';

// Get all hostels with landlord info
$query = "SELECT h.*, u.full_name AS landlord_name, u.phone AS landlord_phone 
          FROM hostels h
          JOIN users u ON h.landlord_id = u.user_id
          ORDER BY h.created_at DESC";
$hostels = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $hostel_id = (int)$_GET['id'];
    
    if ($action === 'delete') {
        // Delete hostel images first
        $images = get_hostel_images($hostel_id);
        foreach ($images as $image) {
            $file_path = __DIR__ . "/../../uploads/hostel_images/" . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete hostel
        $delete_query = "DELETE FROM hostels WHERE hostel_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $hostel_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Hostel deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting hostel: " . $conn->error;
        }
        
        header("Location: /admin/hostels.php");
        exit();
    } elseif ($action === 'approve') {
        $update_query = "UPDATE hostels SET approved = TRUE WHERE hostel_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $hostel_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Hostel approved successfully";
        } else {
            $_SESSION['error'] = "Error approving hostel: " . $conn->error;
        }
        
        header("Location: /admin/hostels.php");
        exit();
    }
}

// Display messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Hostels</h2>
    <div>
        <a href="/admin/hostels.php?action=export" class="btn btn-outline-secondary">
            <i class="bi bi-download"></i> Export
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hostel Name</th>
                        <th>Landlord</th>
                        <th>University</th>
                        <th>Price</th>
                        <th>Rooms</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hostels as $hostel): ?>
                        <tr>
                            <td><?php echo $hostel['hostel_id']; ?></td>
                            <td>
                                <a href="/hostel-details.php?id=<?php echo $hostel['hostel_id']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($hostel['name']); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($hostel['landlord_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($hostel['landlord_phone']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($hostel['university_name']); ?></td>
                            <td class="text-success">UGX <?php echo number_format($hostel['price_per_month']); ?></td>
                            <td><?php echo $hostel['rooms_available']; ?></td>
                            <td>
                                <?php if ($hostel['approved']): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/landlord/listings.php?action=edit&id=<?php echo $hostel['hostel_id']; ?>" 
                                       class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if (!$hostel['approved']): ?>
                                        <a href="/admin/hostels.php?action=approve&id=<?php echo $hostel['hostel_id']; ?>" 
                                           class="btn btn-outline-success" title="Approve">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="/admin/hostels.php?action=delete&id=<?php echo $hostel['hostel_id']; ?>" 
                                       class="btn btn-outline-danger" 
                                       onclick="return confirm('Are you sure you want to delete this hostel?');"
                                       title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>