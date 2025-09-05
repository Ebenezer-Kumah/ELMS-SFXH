<?php
// team_calendar.php
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Include database configuration
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

$page_title = 'Team Calendar';

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Adjust month and year if needed
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Get team members
$team_members = getTeamMembers($_SESSION['user_id']);

// Get approved leave requests for the team for the selected month
$start_date = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
$end_date = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

$team_requests = [];
if (!empty($team_members)) {
    $team_ids = array_map(function($member) { return $member['id']; }, $team_members);
    $placeholders = implode(',', array_fill(0, count($team_ids), '?'));
    
    $stmt = $pdo->prepare("SELECT lr.*, u.first_name, u.last_name, lt.name as leave_type_name 
                          FROM leave_requests lr 
                          JOIN users u ON lr.employee_id = u.id 
                          JOIN leave_types lt ON lr.leave_type_id = lt.id
                          WHERE lr.employee_id IN ($placeholders) 
                          AND lr.status = 'approved'
                          AND ((lr.start_date BETWEEN ? AND ?) OR (lr.end_date BETWEEN ? AND ?) OR (lr.start_date <= ? AND lr.end_date >= ?))
                          ORDER BY lr.start_date");
    
    $params = array_merge($team_ids, [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
    $stmt->execute($params);
    $team_requests = $stmt->fetchAll();
}

// Get number of days in month
$days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));

// Get first day of month
$first_day = date('N', mktime(0, 0, 0, $month, 1, $year));

// Navigation for previous and next months
$prev_month = $month - 1;
$prev_year = $year;
$next_month = $month + 1;
$next_year = $year;

if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get leave types for color coding
$leave_types = getLeaveTypes();
$leave_type_colors = [
    'Annual Leave' => '#3498db',
    'Sick Leave' => '#e74c3c',
    'Maternity Leave' => '#9b59b6',
    'Paternity Leave' => '#3498db',
    'Emergency Leave' => '#f39c12'
];

// Add colors for any additional leave types
foreach ($leave_types as $type) {
    if (!isset($leave_type_colors[$type['name']])) {
        // Generate a random color for unknown leave types
        $leave_type_colors[$type['name']] = '#' . substr(md5($type['name']), 0, 6);
    }
}
?>

<div class="content-container">
    <h2>Team Calendar</h2>
    
    <div class="calendar-controls">
        <a href="team_calendar.php?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn-secondary">
            <i class="fas fa-chevron-left"></i> Previous Month
        </a>
        
        <h3><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
        
        <a href="team_calendar.php?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn-secondary">
            Next Month <i class="fas fa-chevron-right"></i>
        </a>
        
        <a href="team_calendar.php" class="btn-primary">Current Month</a>
    </div>
    
    <?php if (empty($team_members)): ?>
    <div class="alert alert-info">
        <p>You don't have any team members assigned yet.</p>
    </div>
    <?php else: ?>
    
    <div class="calendar-container">
        <div class="calendar-header">
            <div class="day-header">Mon</div>
            <div class="day-header">Tue</div>
            <div class="day-header">Wed</div>
            <div class="day-header">Thu</div>
            <div class="day-header">Fri</div>
            <div class="day-header">Sat</div>
            <div class="day-header">Sun</div>
        </div>
        
        <div class="calendar-body">
            <?php
            // Add empty cells for days before the first day of the month
            for ($i = 1; $i < $first_day; $i++) {
                echo '<div class="calendar-day empty"></div>';
            }
            
            // Generate days of the month
            for ($day = 1; $day <= $days_in_month; $day++) {
                $current_date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
                $is_weekend = (date('N', strtotime($current_date)) >= 6);
                $is_today = ($current_date == date('Y-m-d'));
                
                echo '<div class="calendar-day' . ($is_weekend ? ' weekend' : '') . ($is_today ? ' today' : '') . '">';
                echo '<div class="day-number">' . $day . '</div>';
                
                // Display leave requests for this day
                foreach ($team_requests as $request) {
                    $request_start = strtotime($request['start_date']);
                    $request_end = strtotime($request['end_date']);
                    $current_timestamp = strtotime($current_date);
                    
                    if ($current_timestamp >= $request_start && $current_timestamp <= $request_end) {
                        $color = $leave_type_colors[$request['leave_type_name']] ?? '#95a5a6';
                        
                        echo '<div class="leave-event" style="border-left: 3px solid ' . $color . '" title="' . $request['leave_type_name'] . '">';
                        echo '<span class="employee-name">' . $request['first_name'] . ' ' . $request['last_name'] . '</span>';
                        echo '</div>';
                    }
                }
                
                echo '</div>';
            }
            
            // Add empty cells to complete the last week
            $last_day = date('N', mktime(0, 0, 0, $month, $days_in_month, $year));
            if ($last_day < 7) {
                for ($i = $last_day; $i < 7; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
            }
            ?>
        </div>
    </div>
    
    <div class="calendar-legend">
        <h4>Legend</h4>
        <div class="legend-items">
            <?php foreach ($leave_type_colors as $type => $color): ?>
            <div class="legend-item">
                <span class="color-box" style="background-color: <?php echo $color; ?>"></span>
                <span class="legend-label"><?php echo $type; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="team-availability">
        <h3>Team Availability Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Team Member</th>
                    <th>Days on Leave This Month</th>
                    <th>Current Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($team_members as $member): 
                    $member_requests = array_filter($team_requests, function($req) use ($member) {
                        return $req['employee_id'] == $member['id'];
                    });
                    
                    $days_on_leave = 0;
                    foreach ($member_requests as $request) {
                        $start = new DateTime($request['start_date']);
                        $end = new DateTime($request['end_date']);
                        $end->modify('+1 day'); // Include end date
                        $interval = $start->diff($end);
                        $days_on_leave += $interval->days;
                    }
                    
                    $today = date('Y-m-d');
                    $is_on_leave = false;
                    foreach ($member_requests as $request) {
                        if ($today >= $request['start_date'] && $today <= $request['end_date']) {
                            $is_on_leave = true;
                            break;
                        }
                    }
                ?>
                <tr>
                    <td><?php echo $member['first_name'] . ' ' . $member['last_name']; ?></td>
                    <td><?php echo $days_on_leave; ?> days</td>
                    <td>
                        <span class="status-badge status-<?php echo $is_on_leave ? 'absent' : 'present'; ?>">
                            <?php echo $is_on_leave ? 'On Leave' : 'Available'; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>