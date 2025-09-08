<?php
require_once __DIR__ . '/auth.php';
check_user_role(['admin']);
$page_title = "Admin - Settings";
require_once __DIR__ . '/header.php';

global $conn;

// Initialize variables
$success_message = '';
$error_message = '';

// Get current settings
$settings = [];
$query = "SELECT * FROM settings";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();
        
        // Process general settings
        $platform_name = sanitize_input($conn, $_POST['platform_name']);
        $platform_email = sanitize_input($conn, $_POST['platform_email']);
        $currency = sanitize_input($conn, $_POST['currency']);
        $platform_fee_percentage = (float)$_POST['platform_fee_percentage'];
        
        // Process payment settings
        $enable_payments = isset($_POST['enable_payments']) ? 1 : 0;
        $payment_methods = isset($_POST['payment_methods']) ? implode(',', $_POST['payment_methods']) : '';
        
        // Process email settings
        $smtp_host = sanitize_input($conn, $_POST['smtp_host']);
        $smtp_port = (int)$_POST['smtp_port'];
        $smtp_username = sanitize_input($conn, $_POST['smtp_username']);
        $smtp_password = !empty($_POST['smtp_password']) ? encrypt_password($_POST['smtp_password']) : $settings['smtp_password'];
        $smtp_secure = sanitize_input($conn, $_POST['smtp_secure']);
        
        // Prepare update statements
        $update_query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $stmt = $conn->prepare($update_query);
        
        // General settings
        $settings_to_update = [
            'platform_name' => $platform_name,
            'platform_email' => $platform_email,
            'currency' => $currency,
            'platform_fee_percentage' => $platform_fee_percentage,
            'enable_payments' => $enable_payments,
            'payment_methods' => $payment_methods,
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_username' => $smtp_username,
            'smtp_password' => $smtp_password,
            'smtp_secure' => $smtp_secure
        ];
        
        foreach ($settings_to_update as $key => $value) {
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        }
        
        $conn->commit();
        $success_message = "Settings updated successfully!";
        
        // Refresh settings
        $result = $conn->query($query);
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error updating settings: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">System Settings</h1>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">General Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Platform Name</label>
                                    <input type="text" class="form-control" name="platform_name" 
                                           value="<?= htmlspecialchars($settings['platform_name'] ?? 'Hostel Booking System') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Platform Email</label>
                                    <input type="email" class="form-control" name="platform_email" 
                                           value="<?= htmlspecialchars($settings['platform_email'] ?? 'admin@hostelbookings.com') ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Currency</label>
                                    <select class="form-select" name="currency" required>
                                        <option value="UGX" <?= ($settings['currency'] ?? 'UGX') === 'UGX' ? 'selected' : '' ?>>UGX - Ugandan Shilling</option>
                                        <option value="USD" <?= ($settings['currency'] ?? 'UGX') === 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                                        <option value="KES" <?= ($settings['currency'] ?? 'UGX') === 'KES' ? 'selected' : '' ?>>KES - Kenyan Shilling</option>
                                        <option value="TZS" <?= ($settings['currency'] ?? 'UGX') === 'TZS' ? 'selected' : '' ?>>TZS - Tanzanian Shilling</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Platform Fee Percentage</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="platform_fee_percentage" 
                                               value="<?= htmlspecialchars($settings['platform_fee_percentage'] ?? '5') ?>" min="0" max="100" step="0.1" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Payment Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="enablePayments" name="enable_payments" 
                                           <?= ($settings['enable_payments'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enablePayments">Enable Payments</label>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Allowed Payment Methods</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="payment_methods[]" 
                                               value="mobile_money" id="mobileMoney" 
                                               <?= str_contains($settings['payment_methods'] ?? '', 'mobile_money') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mobileMoney">Mobile Money</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="payment_methods[]" 
                                               value="credit_card" id="creditCard" 
                                               <?= str_contains($settings['payment_methods'] ?? '', 'credit_card') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="creditCard">Credit Card</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="payment_methods[]" 
                                               value="bank_transfer" id="bankTransfer" 
                                               <?= str_contains($settings['payment_methods'] ?? '', 'bank_transfer') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="bankTransfer">Bank Transfer</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Email Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" name="smtp_host" 
                                           value="<?= htmlspecialchars($settings['smtp_host'] ?? 'smtp.example.com') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" name="smtp_port" 
                                           value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" class="form-control" name="smtp_username" 
                                           value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" name="smtp_password" 
                                           placeholder="Leave blank to keep current password">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">SMTP Security</label>
                                    <select class="form-select" name="smtp_secure">
                                        <option value="">None</option>
                                        <option value="tls" <?= ($settings['smtp_secure'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                        <option value="ssl" <?= ($settings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <button type="button" class="btn btn-secondary" onclick="testEmailSettings()">Test Email Settings</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
function testEmailSettings() {
    // You would implement AJAX to test email settings
    alert('This would test the email configuration with an AJAX call in a real implementation.');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>