<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$page_title = 'Leave History';

// Get user's leave requests
$requests = getLeaveRequests($_SESSION['user_id']);
?>

<div class="content-container">
    <h2>My Leave History</h2>
    
    <table>
        <thead>
            <tr>
                <th>Leave Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Applied On</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $request): ?>
            <tr>
                <td><?php echo $request['leave_type_name']; ?></td>
                <td><?php echo date('M j, Y', strtotime($request['start_date'])); ?></td>
                <td><?php echo date('M j, Y', strtotime($request['end_date'])); ?></td>
                <td><?php echo $request['reason']; ?></td>
                <td><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (empty($requests)): ?>
    <p>You haven't applied for any leave yet.</p>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>