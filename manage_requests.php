<?php
// manage_requests.php
require_once 'includes/auth.php';

// Only managers and admins can access this page
if ($_SESSION['role'] != 'manager' && $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';
require_once 'includes/notification_functions.php';

// Get pending leave requests
function getTeamRequests($manager_id) {
    global $pdo;
    // This is a simplified version - you would need a team_assignments table
    // For now, we'll return all pending requests
    $stmt = $pdo->prepare("SELECT lr.*, u.first_name, u.last_name, lt.name as leave_type_name 
                           FROM leave_requests lr 
                           JOIN users u ON lr.employee_id = u.id 
                           JOIN leave_types lt ON lr.leave_type_id = lt.id
                           WHERE lr.status = 'pending'");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Process approval/rejection
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $comments = trim($_POST['comments']);
    
    if ($action == 'approve' || $action == 'reject') {
        $status = $action == 'approve' ? 'approved' : 'rejected';
        
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, manager_id = ?, comments = ? WHERE id = ?");
        $stmt->execute([$status, $_SESSION['user_id'], $comments, $request_id]);
        
        // If approved, deduct from leave balance
        if ($action == 'approve') {
            // Get the request details
            $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $request_details = $stmt->fetch();
            
            // Calculate weekdays
            $start = new DateTime($request_details['start_date']);
            $end = new DateTime($request_details['end_date']);
            $end->modify('+1 day');
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
            
            // Update leave balance
            $stmt = $pdo->prepare("UPDATE users SET leave_balance = leave_balance - ? WHERE id = ?");
            $stmt->execute([$weekdayCount, $request_details['employee_id']]);
        }
        
        // Send notification to employee
        notifyLeaveStatusChange($request_id, $status);
        
        $success = 'Leave request ' . $status . ' successfully';
        // Refresh the page
        header('Location: manage_requests.php');
        exit();
    }
}

// Now include the header after all potential redirects
require_once 'includes/header.php';

$page_title = 'Manage Leave Requests';

// Get pending leave requests
$requests = getTeamRequests($_SESSION['user_id']);
?>

<div class="content-container">
    <h2>Manage Leave Requests</h2>
    
    <?php if (!empty($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (empty($requests)): ?>
    <div class="alert alert-info">
        <p>There are no pending leave requests.</p>
    </div>
    <?php else: ?>
    
    <?php foreach ($requests as $request): ?>
    <div class="request-card">
        <div class="request-header">
            <h3><?php echo $request['first_name'] . ' ' . $request['last_name']; ?></h3>
            <span class="leave-type"><?php echo $request['leave_type_name']; ?></span>
        </div>
        
        <div class="request-details">
            <p><strong>Dates:</strong> <?php echo date('M j, Y', strtotime($request['start_date'])) . ' to ' . date('M j, Y', strtotime($request['end_date'])); ?></p>
            <p><strong>Reason:</strong> <?php echo $request['reason']; ?></p>
            <p><strong>Applied on:</strong> <?php echo date('M j, Y', strtotime($request['created_at'])); ?></p>
        </div>
        
        <form method="POST" action="" class="request-action-form">
            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
            
            <div class="form-group">
                <label for="comments">Comments (optional):</label>
                <textarea name="comments" rows="2"></textarea>
            </div>
            
            <div class="action-buttons">
                <button type="submit" name="action" value="approve" class="btn-success">Approve</button>
                <button type="submit" name="action" value="reject" class="btn-danger">Reject</button>
            </div>
        </form>
    </div>
    <?php endforeach; ?>
    
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>