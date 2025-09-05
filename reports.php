<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Only admins can access this page
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$page_title = 'Reports';

// Default date range (current month)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$department = isset($_GET['department']) ? $_GET['department'] : '';

// Get all departments for filter
$stmt = $pdo->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department");
$departments = $stmt->fetchAll();

// Get report data
$where_conditions = [];
$params = [];

if (!empty($department)) {
    $where_conditions[] = "u.department = ?";
    $params[] = $department;
}

$where_conditions[] = "lr.start_date BETWEEN ? AND ?";
$params[] = $start_date;
$params[] = $end_date;

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Leave by department
$stmt = $pdo->prepare("
    SELECT u.department, COUNT(lr.id) as total_requests, 
           SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
           SUM(CASE WHEN lr.status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
           SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests
    FROM leave_requests lr
    JOIN users u ON lr.employee_id = u.id
    $where_clause
    GROUP BY u.department
    ORDER BY u.department
");
$stmt->execute($params);
$leave_by_department = $stmt->fetchAll();

// Leave by type
$stmt = $pdo->prepare("
    SELECT lt.name, COUNT(lr.id) as total_requests, 
           SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
           SUM(CASE WHEN lr.status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
           SUM(CASE WHEN lr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests
    FROM leave_requests lr
    JOIN leave_types lt ON lr.leave_type_id = lt.id
    JOIN users u ON lr.employee_id = u.id
    $where_clause
    GROUP BY lt.name
    ORDER BY lt.name
");
$stmt->execute($params);
$leave_by_type = $stmt->fetchAll();

// Employee leave usage
$stmt = $pdo->prepare("
    SELECT u.employee_id, u.first_name, u.last_name, u.department,
           COUNT(lr.id) as total_requests, 
           SUM(CASE WHEN lr.status = 'approved' THEN 1 ELSE 0 END) as approved_requests
    FROM leave_requests lr
    JOIN users u ON lr.employee_id = u.id
    $where_clause
    GROUP BY u.id
    ORDER BY approved_requests DESC
    LIMIT 10
");
$stmt->execute($params);
$employee_usage = $stmt->fetchAll();

// Monthly trends (last 6 months)
$monthly_trends = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_start = date('Y-m-01', strtotime($month));
    $month_end = date('Y-m-t', strtotime($month));
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_requests,
               SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests
        FROM leave_requests
        WHERE start_date BETWEEN ? AND ?
    ");
    $stmt->execute([$month_start, $month_end]);
    $trend = $stmt->fetch();
    
    $monthly_trends[] = [
        'month' => date('M Y', strtotime($month)),
        'total_requests' => $trend['total_requests'],
        'approved_requests' => $trend['approved_requests']
    ];
}
?>

<div class="content-container">
    <h2>Reports & Analytics</h2>
    
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
                    <label for="department">Department</label>
                    <select id="department" name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department']; ?>" <?php echo $department == $dept['department'] ? 'selected' : ''; ?>>
                            <?php echo $dept['department']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="report-section">
        <h3>Leave Requests by Department</h3>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Total Requests</th>
                    <th>Approved</th>
                    <th>Rejected</th>
                    <th>Pending</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leave_by_department as $dept): ?>
                <tr>
                    <td><?php echo $dept['department']; ?></td>
                    <td><?php echo $dept['total_requests']; ?></td>
                    <td><?php echo $dept['approved_requests']; ?></td>
                    <td><?php echo $dept['rejected_requests']; ?></td>
                    <td><?php echo $dept['pending_requests']; ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($leave_by_department)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No data available for the selected filters</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leave_by_type as $type): ?>
                <tr>
                    <td><?php echo $type['name']; ?></td>
                    <td><?php echo $type['total_requests']; ?></td>
                    <td><?php echo $type['approved_requests']; ?></td>
                    <td><?php echo $type['rejected_requests']; ?></td>
                    <td><?php echo $type['pending_requests']; ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($leave_by_type)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No data available for the selected filters</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="report-section">
        <h3>Top 10 Employees by Leave Usage</h3>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Total Requests</th>
                    <th>Approved Requests</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employee_usage as $employee): ?>
                <tr>
                    <td><?php echo $employee['employee_id']; ?></td>
                    <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                    <td><?php echo $employee['department']; ?></td>
                    <td><?php echo $employee['total_requests']; ?></td>
                    <td><?php echo $employee['approved_requests']; ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($employee_usage)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No data available for the selected filters</td>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export to CSV functionality
    document.getElementById('exportBtn').addEventListener('click', function() {
        // This is a simplified version - in a real application, you would
        // implement server-side CSV generation
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