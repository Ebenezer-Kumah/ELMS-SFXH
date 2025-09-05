<?php
// dashboard.php

require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$page_title = 'Dashboard';

// Get statistics based on user role
if ($_SESSION['role'] == 'employee') {
    $pending_requests = count(getLeaveRequests($_SESSION['user_id'], 'pending'));
    $approved_requests = count(getLeaveRequests($_SESSION['user_id'], 'approved'));
    $rejected_requests = count(getLeaveRequests($_SESSION['user_id'], 'rejected'));
} else if ($_SESSION['role'] == 'manager') {
    $team_requests = getTeamRequests($_SESSION['user_id']);
    $pending_requests = count(array_filter($team_requests, function($req) { return $req['status'] == 'pending'; }));
    $approved_requests = count(array_filter($team_requests, function($req) { return $req['status'] == 'approved'; }));
    $rejected_requests = count(array_filter($team_requests, function($req) { return $req['status'] == 'rejected'; }));
} else if ($_SESSION['role'] == 'admin') {
    $all_requests = getLeaveRequests();
    $pending_requests = count(array_filter($all_requests, function($req) { return $req['status'] == 'pending'; }));
    $approved_requests = count(array_filter($all_requests, function($req) { return $req['status'] == 'approved'; }));
    $rejected_requests = count(array_filter($all_requests, function($req) { return $req['status'] == 'rejected'; }));
}
?>

<div class="dashboard">
    <h2>Dashboard</h2>
    
    <div class="stats-container">
        <div class="stat-card">
            <h3>Pending Requests</h3>
            <div class="stat-number"><?php echo $pending_requests; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Approved Requests</h3>
            <div class="stat-number"><?php echo $approved_requests; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Rejected Requests</h3>
            <div class="stat-number"><?php echo $rejected_requests; ?></div>
        </div>
        
        <?php if ($_SESSION['role'] == 'employee'): ?>
        <div class="stat-card">
            <h3>Leave Balance</h3>
            <div class="stat-number"><?php 
                global $pdo;
                $stmt = $pdo->prepare("SELECT leave_balance FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $result = $stmt->fetch();
                echo $result['leave_balance'];
            ?> days</div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="recent-activity">
        <h3>Recent Leave Requests</h3>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($_SESSION['role'] == 'employee') {
                    $requests = getLeaveRequests($_SESSION['user_id']);
                } else if ($_SESSION['role'] == 'manager') {
                    $requests = getTeamRequests($_SESSION['user_id']);
                } else {
                    $requests = getLeaveRequests();
                }
                
                $recent_requests = array_slice($requests, 0, 5);
                
                foreach ($recent_requests as $request):
                ?>
                <tr>
                    <td><?php echo $request['first_name'] . ' ' . $request['last_name']; ?></td>
                    <td><?php echo $request['leave_type_name']; ?></td>
                    <td><?php echo date('M j, Y', strtotime($request['start_date'])); ?></td>
                    <td><?php echo date('M j, Y', strtotime($request['end_date'])); ?></td>
                    <td><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>