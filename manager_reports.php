<?php
// manager_reports.php
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'config/database.php';

// Define all required functions right here to ensure they exist
function getTeamMembers($manager_id) {
    global $pdo;
    
    // First, check if the team_assignments table exists
    try {
        $stmt = $pdo->query("SELECT 1 FROM team_assignments LIMIT 1");
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        $pdo->exec("CREATE TABLE IF NOT EXISTS team_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            manager_id INT NOT NULL,
            employee_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (manager_id) REFERENCES users(id),
            FOREIGN KEY (employee_id) REFERENCES users(id),
            UNIQUE KEY unique_assignment (manager_id, employee_id)
        )");
        
        // Add some demo assignments if table was just created
        $pdo->exec("INSERT IGNORE INTO team_assignments (manager_id, employee_id) VALUES 
            (2, 3)  -- Manager John manages employee Jane
        ");
    }
    
    // Get team members from team_assignments table
    $stmt = $pdo->prepare("SELECT u.* FROM users u 
                          JOIN team_assignments ta ON u.id = ta.employee_id 
                          WHERE ta.manager_id = ? AND u.role = 'employee' 
                          ORDER BY u.first_name, u.last_name");
    $stmt->execute([$manager_id]);
    $team_members = $stmt->fetchAll();
    
    // If no team members found, return all employees (for demo purposes)
    if (empty($team_members)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'employee' ORDER BY first_name, last_name");
        $stmt->execute();
        $team_members = $stmt->fetchAll();
    }
    
    return $team_members;
}

function getLeaveTypes() {
    global $pdo;
    
    // First, check if the leave_types table exists and has data
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM leave_types");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            // Table is empty, insert default leave types
            $pdo->exec("INSERT INTO leave_types (name, description, max_days, can_carry_over, carry_over_limit) VALUES 
                ('Annual Leave', 'Paid time off work', 20, 1, 5),
                ('Sick Leave', 'Leave for health reasons', 10, 0, 0),
                ('Maternity Leave', 'Leave for new mothers', 90, 0, 0),
                ('Paternity Leave', 'Leave for new fathers', 10, 0, 0),
                ('Emergency Leave', 'Leave for urgent matters', 5, 0, 0)
            ");
        }
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        $pdo->exec("CREATE TABLE IF NOT EXISTS leave_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            description TEXT,
            max_days INT NOT NULL,
            can_carry_over BOOLEAN DEFAULT FALSE,
            carry_over_limit INT DEFAULT 0
        )");
        
        // Insert default leave types
        $pdo->exec("INSERT INTO leave_types (name, description, max_days, can_carry_over, carry_over_limit) VALUES 
            ('Annual Leave', 'Paid time off work', 20, 1, 5),
            ('Sick Leave', 'Leave for health reasons', 10, 0, 0),
            ('Maternity Leave', 'Leave for new mothers', 90, 0, 0),
            ('Paternity Leave', 'Leave for new fathers', 10, 0, 0),
            ('Emergency Leave', 'Leave for urgent matters', 5, 0, 0)
        ");
    }
    
    // Now get all leave types
    $stmt = $pdo->query("SELECT * FROM leave_types ORDER BY name");
    return $stmt->fetchAll();
}

// Only managers can access this page
if ($_SESSION['role'] != 'manager' && $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$page_title = 'Manager Reports';

// Get team members
$team_members = getTeamMembers($_SESSION['user_id']);

// Default date range (current month)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';

// Get report data
$where_conditions = [];
$params = [];

if (!empty($team_members)) {
    $team_ids = array_map(function($member) { return $member['id']; }, $team_members);
    $placeholders = implode(',', array_fill(0, count($team_ids), '?'));
    $where_conditions[] = "lr.employee_id IN ($placeholders)";
    $params = array_merge($params, $team_ids);
}

if (!empty($employee_id)) {
    $where_conditions[] = "lr.employee_id = ?";
    $params[] = $employee_id;
}

$where_conditions[] = "lr.start_date BETWEEN ? AND ?";
$params[] = $start_date;
$params[] = $end_date;

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Leave by status
$stmt = $pdo->prepare("
    SELECT lr.status, COUNT(lr.id) as count
    FROM leave_requests lr
    $where_clause
    GROUP BY lr.status
    ORDER BY lr.status
");
$stmt->execute($params);
$leave_by_status = $stmt->fetchAll();

// Leave by type
$stmt = $pdo->prepare("
    SELECT lt.name, COUNT(lr.id) as total_requests, 
           SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
           SUM(CASE WHEN lr.status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
           SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests
    FROM leave_requests lr
    JOIN leave_types lt ON lr.leave_type_id = lt.id
    $where_clause
    GROUP BY lt.name
    ORDER BY lt.name
");
$stmt->execute($params);
$leave_by_type = $stmt->fetchAll();

// Employee leave usage
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, 
           COUNT(lr.id) as total_requests, 
           SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
           SUM(CASE WHEN lr.status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
           SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests
    FROM leave_requests lr
    JOIN users u ON lr.employee_id = u.id
    $where_clause
    GROUP BY u.id
    ORDER BY approved_requests DESC
");
$stmt->execute($params);
$employee_usage = $stmt->fetchAll();

// Monthly trends (last 6 months)
$monthly_trends = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_start = date('Y-m-01', strtotime($month));
    $month_end = date('Y-m-t', strtotime($month));
    
    $month_params = $team_ids;
    $month_where = "WHERE lr.employee_id IN ($placeholders) AND lr.start_date BETWEEN ? AND ?";
    $month_params = array_merge($month_params, [$month_start, $month_end]);
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_requests,
               SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests
        FROM leave_requests lr
        $month_where
    ");
    $stmt->execute($month_params);
    $trend = $stmt->fetch();
    
    $monthly_trends[] = [
        'month' => date('M Y', strtotime($month)),
        'total_requests' => $trend['total_requests'] ?? 0,
        'approved_requests' => $trend['approved_requests'] ?? 0
    ];
}

// Calculate statistics for the dashboard
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

foreach ($leave_by_status as $status) {
    if ($status['status'] == 'pending') $pending_count = $status['count'];
    if ($status['status'] == 'approved') $approved_count = $status['count'];
    if ($status['status'] == 'rejected') $rejected_count = $status['count'];
}
?>

<div class="content-container">
    <h2>Team Reports & Analytics</h2>
    
    <div class="report-filters">
        <h3>Filters</h3>
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <div class="form-group">
                    <label for="employee_id">Team Member</label>
                    <select id="employee_id" name="employee_id">
                        <option value="">All Team Members</option>
                        <?php foreach ($team_members as $member): ?>
                        <option value="<?php echo $member['id']; ?>" <?php echo $employee_id == $member['id'] ? 'selected' : ''; ?>>
                            <?php echo $member['first_name'] . ' ' . $member['last_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary">Apply Filters</button>
                    <a href="manager_reports.php" class="btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (empty($team_members)): ?>
    <div class="alert alert-info">
        <p>You don't have any team members assigned yet.</p>
    </div>
    <?php else: ?>
    
    <div class="stats-container">
        <div class="stat-card">
            <h3>Team Members</h3>
            <div class="stat-number"><?php echo count($team_members); ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Pending Requests</h3>
            <div class="stat-number"><?php echo $pending_count; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Approved Requests</h3>
            <div class="stat-number"><?php echo $approved_count; ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Rejected Requests</h3>
            <div class="stat-number"><?php echo $rejected_count; ?></div>
        </div>
    </div>
    
    <div class="report-section">
        <h3>Leave Requests by Status</h3>
        <div class="chart-container">
            <canvas id="statusChart" width="400" height="200"></canvas>
        </div>
    </div>
    
    <div class="report-section">
        <h3>Leave Requests by Type</h3>
        <table>
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Total Requests</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                    <th>Pending</th>
                    <th>Approval Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leave_by_type as $type): 
                    $approval_rate = $type['total_requests'] > 0 ? round(($type['approved_requests'] / $type['total_requests']) * 100, 2) : 0;
                ?>
                <tr>
                    <td><?php echo $type['name']; ?></td>
                    <td><?php echo $type['total_requests']; ?></td>
                    <td><?php echo $type['approved_requests']; ?></td>
                    <td><?php echo $type['rejected_requests']; ?></td>
                    <td><?php echo $type['pending_requests']; ?></td>
                    <td><?php echo $approval_rate; ?>%</td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($leave_by_type)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No data available for the selected filters</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="report-section">
        <h3>Team Member Leave Usage</h3>
        <table>
            <thead>
                <tr>
                    <th>Team Member</th>
                    <th>Total Requests</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                    <th>Pending</th>
                    <th>Approval Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employee_usage as $employee): 
                    $approval_rate = $employee['total_requests'] > 0 ? round(($employee['approved_requests'] / $employee['total_requests']) * 100, 2) : 0;
                ?>
                <tr>
                    <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                    <td><?php echo $employee['total_requests']; ?></td>
                    <td><?php echo $employee['approved_requests']; ?></td>
                    <td><?php echo $employee['rejected_requests']; ?></td>
                    <td><?php echo $employee['pending_requests']; ?></td>
                    <td><?php echo $approval_rate; ?>%</td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($employee_usage)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No data available for the selected filters</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="report-section">
        <h3>Monthly Trends (Last 6 Months)</h3>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Requests</th>
                    <th>Approved Requests</th>
                    <th>Approval Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthly_trends as $trend): 
                    $approval_rate = $trend['total_requests'] > 0 ? round(($trend['approved_requests'] / $trend['total_requests']) * 100, 2) : 0;
                ?>
                <tr>
                    <td><?php echo $trend['month']; ?></td>
                    <td><?php echo $trend['total_requests']; ?></td>
                    <td><?php echo $trend['approved_requests']; ?></td>
                    <td><?php echo $approval_rate; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="report-actions">
        <button onclick="window.print()" class="btn-primary">Print Report</button>
        <button id="exportBtn" class="btn-secondary">Export to CSV</button>
    </div>
    
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [<?php echo $pending_count; ?>, <?php echo $approved_count; ?>, <?php echo $rejected_count; ?>],
                backgroundColor: [
                    '#ffc107',
                    '#28a745',
                    '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Export to CSV functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        alert('CSV export functionality would be implemented here. In a real application, this would generate and download a CSV file with the report data.');
    });
    
    // Set max date for end date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('start_date').max = today;
    document.getElementById('end_date').max = today;
    
    // Validate date range
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    startDate.addEventListener('change', function() {
        endDate.min = this.value;
    });
    
    endDate.addEventListener('change', function() {
        if (this.value < startDate.value) {
            alert('End date cannot be before start date');
            this.value = startDate.value;
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>