<?php
// manage_employees.php
require_once 'includes/auth.php';

// Only admins can access this page
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

require_once 'config/database.php';

// Get all employees
$stmt = $pdo->query("SELECT * FROM users ORDER BY first_name, last_name");
$employees = $stmt->fetchAll();

// Add new employee
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_employee'])) {
    $employee_id = trim($_POST['employee_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $leave_balance = intval($_POST['leave_balance']);
    
    // Validate input
    if (empty($employee_id) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if employee ID or email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_id = ? OR email = ?");
        $stmt->execute([$employee_id, $email]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $error = 'Employee ID or email already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new employee
            $stmt = $pdo->prepare("INSERT INTO users (employee_id, first_name, last_name, email, password, role, department, position, leave_balance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$employee_id, $first_name, $last_name, $email, $hashed_password, $role, $department, $position, $leave_balance])) {
                $success = 'Employee added successfully';
                // Refresh the employee list
                header('Location: manage_employees.php');
                exit();
            } else {
                $error = 'Error adding employee';
            }
        }
    }
}

// Update employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_employee'])) {
    $id = $_POST['id'];
    $employee_id = trim($_POST['employee_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $leave_balance = intval($_POST['leave_balance']);
    
    // Validate input
    if (empty($employee_id) || empty($first_name) || empty($last_name) || empty($email)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if employee ID or email already exists (excluding current employee)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (employee_id = ? OR email = ?) AND id != ?");
        $stmt->execute([$employee_id, $email, $id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $error = 'Employee ID or email already exists';
        } else {
            // Update employee
            $stmt = $pdo->prepare("UPDATE users SET employee_id = ?, first_name = ?, last_name = ?, email = ?, role = ?, department = ?, position = ?, leave_balance = ? WHERE id = ?");
            if ($stmt->execute([$employee_id, $first_name, $last_name, $email, $role, $department, $position, $leave_balance, $id])) {
                $success = 'Employee updated successfully';
                // Refresh the employee list
                header('Location: manage_employees.php');
                exit();
            } else {
                $error = 'Error updating employee';
            }
        }
    }
}

// Delete employee
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if employee has leave requests
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE employee_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        $error = 'Cannot delete employee with existing leave requests';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Employee deleted successfully';
            // Refresh the employee list
            header('Location: manage_employees.php');
            exit();
        } else {
            $error = 'Error deleting employee';
        }
    }
}

// Now include the header after all potential redirects
require_once 'includes/header.php';

$page_title = 'Manage Employees';
?>

<div class="content-container">
    <h2>Manage Employees</h2>
    
    <?php if (!empty($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="employees-list">Employees List</button>
            <button class="tab-btn" data-tab="add-employee">Add New Employee</button>
        </div>
        
        <div class="tab-content active" id="employees-list">
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Leave Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?php echo $employee['employee_id']; ?></td>
                        <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                        <td><?php echo $employee['email']; ?></td>
                        <td><?php echo ucfirst($employee['role']); ?></td>
                        <td><?php echo $employee['department']; ?></td>
                        <td><?php echo $employee['position']; ?></td>
                        <td><?php echo $employee['leave_balance']; ?> days</td>
                        <td>
                            <button class="btn-edit" data-id="<?php echo $employee['id']; ?>" data-employee-id="<?php echo $employee['employee_id']; ?>" data-first-name="<?php echo $employee['first_name']; ?>" data-last-name="<?php echo $employee['last_name']; ?>" data-email="<?php echo $employee['email']; ?>" data-role="<?php echo $employee['role']; ?>" data-department="<?php echo $employee['department']; ?>" data-position="<?php echo $employee['position']; ?>" data-leave-balance="<?php echo $employee['leave_balance']; ?>">Edit</button>
                            <a href="manage_employees.php?delete=<?php echo $employee['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this employee?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="tab-content" id="add-employee">
            <h3>Add New Employee</h3>
            <form method="POST" action="">
                <input type="hidden" name="add_employee" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="employee_id">Employee ID *</label>
                        <input type="text" id="employee_id" name="employee_id" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="employee">Employee</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="leave_balance">Initial Leave Balance</label>
                    <input type="number" id="leave_balance" name="leave_balance" value="20" min="0">
                </div>
                
                <button type="submit" class="btn-primary">Add Employee</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Employee Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Employee</h3>
            <form method="POST" action="">
                <input type="hidden" name="update_employee" value="1">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_employee_id">Employee ID *</label>
                        <input type="text" id="edit_employee_id" name="employee_id" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">Email *</label>
                        <input type="email" id="edit_email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_first_name">First Name *</label>
                        <input type="text" id="edit_first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_last_name">Last Name *</label>
                        <input type="text" id="edit_last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_role">Role *</label>
                        <select id="edit_role" name="role" required>
                            <option value="employee">Employee</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_department">Department</label>
                        <input type="text" id="edit_department" name="department">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_position">Position</label>
                        <input type="text" id="edit_position" name="position">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_leave_balance">Leave Balance</label>
                        <input type="number" id="edit_leave_balance" name="leave_balance" min="0">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">Update Employee</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to current button and content
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Edit modal functionality
    const modal = document.getElementById('editModal');
    const closeBtn = document.querySelector('.close');
    const editButtons = document.querySelectorAll('.btn-edit');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_employee_id').value = this.getAttribute('data-employee-id');
            document.getElementById('edit_first_name').value = this.getAttribute('data-first-name');
            document.getElementById('edit_last_name').value = this.getAttribute('data-last-name');
            document.getElementById('edit_email').value = this.getAttribute('data-email');
            document.getElementById('edit_role').value = this.getAttribute('data-role');
            document.getElementById('edit_department').value = this.getAttribute('data-department');
            document.getElementById('edit_position').value = this.getAttribute('data-position');
            document.getElementById('edit_leave_balance').value = this.getAttribute('data-leave-balance');
            
            modal.style.display = 'block';
        });
    });
    
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>