<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Only admins can access this page
check_user_role(['admin']);
$user = get_logged_in_user();

$page_title = "Manage Hostels";
require_once __DIR__ . '/header.php';

global $conn;

// Ensure schema compatibility: add 'approved' column if missing
@mysqli_query($conn, "ALTER TABLE hostels ADD COLUMN IF NOT EXISTS approved TINYINT(1) NOT NULL DEFAULT 0");

// Handle actions (approve/delete) before output
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $hostel_id = (int)$_GET['id'];

    if ($action === 'delete') {
        // Delete hostel images first
        $images = [];
        $img_stmt = $conn->prepare("SELECT image_path FROM hostel_images WHERE hostel_id = ?");
        if ($img_stmt) {
            $img_stmt->bind_param("i", $hostel_id);
            $img_stmt->execute();
            $images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        foreach ($images as $image) {
            $file_path = __DIR__ . "/uploads/hostel_images/" . $image['image_path'];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }

        // Delete hostel and related images rows
        $conn->query("DELETE FROM hostel_images WHERE hostel_id = " . (int)$hostel_id);
        $delete_stmt = $conn->prepare("DELETE FROM hostels WHERE hostel_id = ?");
        if ($delete_stmt) {
            $delete_stmt->bind_param("i", $hostel_id);
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "Hostel deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting hostel: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Unable to prepare delete statement.";
        }

        header("Location: admin_hostels.php");
        exit();
    } elseif ($action === 'approve') {
        $update_stmt = $conn->prepare("UPDATE hostels SET approved = TRUE WHERE hostel_id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("i", $hostel_id);
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Hostel approved successfully";
            } else {
                $_SESSION['error'] = "Error approving hostel: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Unable to prepare approve statement.";
        }

        header("Location: admin_hostels.php");
        exit();
    }
}

// Get all hostels with landlord and university info
$query = "SELECT h.*, 
                 l.full_name AS landlord_name, l.phone AS landlord_phone,
                 u.name AS university_name
          FROM hostels h
          JOIN users l ON h.landlord_id = l.user_id
          LEFT JOIN universities u ON h.university_id = u.university_id
          ORDER BY h.created_at DESC";
$hostels = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
  <div class="row">
    <?php require_once __DIR__ . '/admin_sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <h2>Manage Hostels</h2>
        <div>
          <a href="admin_hostels.php?action=export" class="btn btn-outline-secondary">
            <i class="bi bi-download"></i> Export
          </a>
        </div>
      </div>

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
      <?php endif; ?>

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
                  <td><?php echo (int)$hostel['hostel_id']; ?></td>
                  <td>
                    <a href="hostel-details.php?id=<?php echo (int)$hostel['hostel_id']; ?>" target="_blank">
                      <?php echo htmlspecialchars($hostel['name']); ?>
                    </a>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($hostel['landlord_name']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($hostel['landlord_phone']); ?></small>
                  </td>
                  <td><?php echo htmlspecialchars($hostel['university_name'] ?? ''); ?></td>
                  <td class="text-success">UGX <?php echo number_format((float)$hostel['price_per_month']); ?></td>
                  <td><?php echo (int)$hostel['rooms_available']; ?></td>
                  <td>
                    <?php if (!empty($hostel['approved'])): ?>
                      <span class="badge bg-success">Approved</span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark">Pending</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <?php if (empty($hostel['approved'])): ?>
                        <a href="admin_hostels.php?action=approve&id=<?php echo (int)$hostel['hostel_id']; ?>" class="btn btn-outline-success" title="Approve">
                          <i class="bi bi-check-circle"></i>
                        </a>
                      <?php endif; ?>
                      <a href="landlord/listings.php?action=edit&id=<?php echo (int)$hostel['hostel_id']; ?>" class="btn btn-outline-primary" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="admin_hostels.php?action=delete&id=<?php echo (int)$hostel['hostel_id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this hostel?');" title="Delete">
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
    </main>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
