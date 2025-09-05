<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Only admins can access this page
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$page_title = 'Leave Policies';

// Get policies from database (simplified implementation)
$policies = [
    'annual_leave_accrual' => 'Monthly',
    'probation_period' => '3',
    'max_consecutive_days' => '14',
    'advance_notice' => '7',
    'medical_certificate' => '3',
    'carry_over_percentage' => '25'
];

// Try to load from database if available
try {
    $stmt = $pdo->query("SELECT * FROM leave_policies");
    $db_policies = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($db_policies)) {
        $policies = array_merge($policies, $db_policies);
    }
} catch (PDOException $e) {
    // Table might not exist yet, we'll create it when saving
}

// Save policies
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get all posted values
    $annual_leave_accrual = $_POST['annual_leave_accrual'];
    $probation_period = intval($_POST['probation_period']);
    $max_consecutive_days = intval($_POST['max_consecutive_days']);
    $advance_notice = intval($_POST['advance_notice']);
    $medical_certificate = intval($_POST['medical_certificate']);
    $carry_over_percentage = intval($_POST['carry_over_percentage']);
    
    // Validate input
    if ($probation_period <= 0 || $max_consecutive_days <= 0 || $advance_notice < 0 || $medical_certificate < 0 || $carry_over_percentage < 0 || $carry_over_percentage > 100) {
        $error = 'Please enter valid values for all fields';
    } else {
        try {
            // Create table if it doesn't exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS leave_policies (
                policy_key VARCHAR(50) PRIMARY KEY,
                policy_value VARCHAR(255) NOT NULL
            )");
            
            // Save policies
            $policies_to_save = [
                'annual_leave_accrual' => $annual_leave_accrual,
                'probation_period' => $probation_period,
                'max_consecutive_days' => $max_consecutive_days,
                'advance_notice' => $advance_notice,
                'medical_certificate' => $medical_certificate,
                'carry_over_percentage' => $carry_over_percentage
            ];
            
            $stmt = $pdo->prepare("REPLACE INTO leave_policies (policy_key, policy_value) VALUES (?, ?)");
            
            foreach ($policies_to_save as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            
            $success = 'Policies updated successfully';
            
            // Update local policies array
            $policies = $policies_to_save;
            
        } catch (PDOException $e) {
            $error = 'Error saving policies: ' . $e->getMessage();
        }
    }
}
?>

<div class="content-container">
    <h2>Leave Policies Configuration</h2>
    
    <?php if (!empty($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="policy-section">
            <h3>Annual Leave Policies</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="annual_leave_accrual">Leave Accrual Frequency</label>
                    <select id="annual_leave_accrual" name="annual_leave_accrual" required>
                        <option value="Monthly" <?php echo $policies['annual_leave_accrual'] == 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="Quarterly" <?php echo $policies['annual_leave_accrual'] == 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                        <option value="Yearly" <?php echo $policies['annual_leave_accrual'] == 'Yearly' ? 'selected' : ''; ?>>Yearly (on anniversary)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="carry_over_percentage">Maximum Carry Over Percentage</label>
                    <input type="number" id="carry_over_percentage" name="carry_over_percentage" min="0" max="100" value="<?php echo $policies['carry_over_percentage']; ?>" required>
                    <span class="input-suffix">%</span>
                </div>
            </div>
        </div>
        
        <div class="policy-section">
            <h3>Eligibility Policies</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="probation_period">Probation Period (Months)</label>
                    <input type="number" id="probation_period" name="probation_period" min="0" max="12" value="<?php echo $policies['probation_period']; ?>" required>
                    <p class="help-text">Employees become eligible for leave after this period</p>
                </div>
            </div>
        </div>
        
        <div class="policy-section">
            <h3>Application Policies</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="max_consecutive_days">Maximum Consecutive Leave Days</label>
                    <input type="number" id="max_consecutive_days" name="max_consecutive_days" min="1" value="<?php echo $policies['max_consecutive_days']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="advance_notice">Minimum Advance Notice (Days)</label>
                    <input type="number" id="advance_notice" name="advance_notice" min="0" value="<?php echo $policies['advance_notice']; ?>" required>
                    <p class="help-text">Required notice period for leave applications</p>
                </div>
            </div>
        </div>
        
        <div class="policy-section">
            <h3>Sick Leave Policies</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="medical_certificate">Medical Certificate Required After (Days)</label>
                    <input type="number" id="medical_certificate" name="medical_certificate" min="0" value="<?php echo $policies['medical_certificate']; ?>" required>
                    <p class="help-text">Number of consecutive sick days after which a medical certificate is required</p>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Save Policies</button>
            <button type="reset" class="btn-secondary">Reset to Defaults</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Reset to defaults functionality
    document.querySelector('button[type="reset"]').addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to reset all policies to default values?')) {
            document.getElementById('annual_leave_accrual').value = 'Monthly';
            document.getElementById('probation_period').value = '3';
            document.getElementById('max_consecutive_days').value = '14';
            document.getElementById('advance_notice').value = '7';
            document.getElementById('medical_certificate').value = '3';
            document.getElementById('carry_over_percentage').value = '25';
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>