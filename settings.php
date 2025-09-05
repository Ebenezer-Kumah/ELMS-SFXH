<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Only admins can access this page
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$page_title = 'System Settings';

// Get settings from database (simplified implementation)
$settings = [
    'system_name' => 'St. Francis Xavier Hospital ELMS',
    'admin_email' => 'admin@sfxhospital.com',
    'notification_emails' => '1',
    'notification_sms' => '0',
    'auto_approval' => '0',
    'year_start_month' => '1',
    'leave_reset_day' => '1'
];

// Try to load from database if available
try {
    $stmt = $pdo->query("SELECT * FROM system_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($db_settings)) {
        $settings = array_merge($settings, $db_settings);
    }
} catch (PDOException $e) {
    // Table might not exist yet, we'll create it when saving
}

// Save settings
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get all posted values
    $system_name = trim($_POST['system_name']);
    $admin_email = trim($_POST['admin_email']);
    $notification_emails = isset($_POST['notification_emails']) ? 1 : 0;
    $notification_sms = isset($_POST['notification_sms']) ? 1 : 0;
    $auto_approval = isset($_POST['auto_approval']) ? 1 : 0;
    $year_start_month = intval($_POST['year_start_month']);
    $leave_reset_day = intval($_POST['leave_reset_day']);
    
    // Validate input
    if (empty($system_name) || empty($admin_email) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter valid values for all required fields';
    } else if ($year_start_month < 1 || $year_start_month > 12 || $leave_reset_day < 1 || $leave_reset_day > 31) {
        $error = 'Please enter valid values for date settings';
    } else {
        try {
            // Create table if it doesn't exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value VARCHAR(255) NOT NULL
            )");
            
            // Save settings
            $settings_to_save = [
                'system_name' => $system_name,
                'admin_email' => $admin_email,
                'notification_emails' => $notification_emails,
                'notification_sms' => $notification_sms,
                'auto_approval' => $auto_approval,
                'year_start_month' => $year_start_month,
                'leave_reset_day' => $leave_reset_day
            ];
            
            $stmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
            
            foreach ($settings_to_save as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            
            $success = 'Settings updated successfully';
            
            // Update local settings array
            $settings = $settings_to_save;
            
            // Update session system name if changed
            if ($_SESSION['system_name'] != $system_name) {
                $_SESSION['system_name'] = $system_name;
            }
            
        } catch (PDOException $e) {
            $error = 'Error saving settings: ' . $e->getMessage();
        }
    }
}
?>

<div class="content-container">
    <h2>System Settings</h2>
    
    <?php if (!empty($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="settings-section">
            <h3>General Settings</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="system_name">System Name *</label>
                    <input type="text" id="system_name" name="system_name" value="<?php echo $settings['system_name']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">Admin Email *</label>
                    <input type="email" id="admin_email" name="admin_email" value="<?php echo $settings['admin_email']; ?>" required>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>Notification Settings</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="notification_emails" name="notification_emails" value="1" <?php echo $settings['notification_emails'] ? 'checked' : ''; ?>>
                        Enable Email Notifications
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="notification_sms" name="notification_sms" value="1" <?php echo $settings['notification_sms'] ? 'checked' : ''; ?>>
                        Enable SMS Notifications
                    </label>
                    <p class="help-text">Note: SMS functionality requires third-party integration</p>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>Leave Management Settings</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="auto_approval" name="auto_approval" value="1" <?php echo $settings['auto_approval'] ? 'checked' : ''; ?>>
                        Enable Auto-Approval for Short Leaves
                    </label>
                    <p class="help-text">Leaves of 2 days or less will be automatically approved</p>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="year_start_month">Leave Year Start Month</label>
                    <select id="year_start_month" name="year_start_month" required>
                        <?php
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        
                        foreach ($months as $num => $name) {
                            $selected = $settings['year_start_month'] == $num ? 'selected' : '';
                            echo "<option value=\"$num\" $selected>$name</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="leave_reset_day">Leave Reset Day</label>
                    <input type="number" id="leave_reset_day" name="leave_reset_day" min="1" max="31" value="<?php echo $settings['leave_reset_day']; ?>" required>
                    <p class="help-text">Day of the month when leave balances reset</p>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>System Maintenance</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <button type="button" id="resetLeaveBalances" class="btn-warning">Reset All Leave Balances</button>
                    <p class="help-text">Reset all employee leave balances to default values</p>
                </div>
                
                <div class="form-group">
                    <button type="button" id="exportData" class="btn-secondary">Export System Data</button>
                    <p class="help-text">Download a backup of all system data</p>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Save Settings</button>
            <button type="reset" class="btn-secondary">Reset Changes</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Reset leave balances functionality
    document.getElementById('resetLeaveBalances').addEventListener('click', function() {
        if (confirm('WARNING: This will reset ALL employee leave balances to default values. This action cannot be undone. Are you sure you want to continue?')) {
            // This would typically be an AJAX call to a server-side script
            alert('This functionality would reset all leave balances. In a real application, this would connect to a server-side script.');
        }
    });
    
    // Export data functionality
    document.getElementById('exportData').addEventListener('click', function() {
        alert('This functionality would export all system data. In a real application, this would generate a downloadable backup file.');
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>