<?php
// apply_leave.php

require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/functions.php';


$page_title = 'Apply for Leave';

// Get user's leave balance
global $pdo;
$stmt = $pdo->prepare("SELECT leave_balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$leave_balance = $user['leave_balance'];

// Get leave types
$leave_types = getLeaveTypes();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type_id = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason']);
    
    // Validate dates
    if (strtotime($start_date) > strtotime($end_date)) {
        $error = 'End date must be after start date';
    } else {
        // Calculate number of days (excluding weekends)
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day'); // Include end date in calculation
        $interval = $start->diff($end);
        $days = $interval->days;
        
        $weekendDays = 0;
        for ($i = 0; $i < $days; $i++) {
            $mod_date = $start->modify('+1 day');
            $dayOfWeek = $mod_date->format('N');
            if ($dayOfWeek >= 6) {
                $weekendDays++;
            }
        }
        
        $weekdayCount = $days - $weekendDays;
        
        if ($weekdayCount > $leave_balance) {
            $error = "You don't have enough leave balance for this request";
        } else if (empty($reason)) {
            $error = "Please provide a reason for your leave";
        } else {
            // Submit leave request
            $stmt = $pdo->prepare("INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, reason, status) 
        VALUES (:employee_id, :leave_type_id, :start_date, :end_date, :reason, 'pending')");
            if ($stmt->execute([$_SESSION['user_id'], $leave_type_id, $start_date, $end_date, $reason])) {
                $success = 'Leave request submitted successfully';
            } else {
                $error = 'Error submitting leave request';
            }
        }
    }
}
?>

<div class="form-container">
    <h2>Apply for Leave</h2>
    
    <?php if (!empty($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="leave-balance">
        <p>Your current leave balance: <strong><?php echo $leave_balance; ?> days</strong></p>
    </div>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="leave_type">Leave Type</label>
            <select id="leave_type" name="leave_type" required>
                <option value="">Select Leave Type</option>
                <?php foreach ($leave_types as $type): ?>
                <option value="<?php echo $type['id']; ?>"><?php echo $type['name']; ?> (Max: <?php echo $type['max_days']; ?> days)</option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" required>
        </div>
        
        <div class="form-group">
            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" required>
        </div>
        
        <div class="form-group">
            <label for="reason">Reason</label>
            <textarea id="reason" name="reason" rows="4" required></textarea>
        </div>
        
        <button type="submit" class="btn-primary">Submit Request</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    startDate.min = today;
    endDate.min = today;
    
    // Update end date min when start date changes
    startDate.addEventListener('change', function() {
        endDate.min = this.value;
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>