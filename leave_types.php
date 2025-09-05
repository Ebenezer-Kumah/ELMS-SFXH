<?php
// leave_types.php
require_once 'includes/auth.php';

// Only admins can access this page
if ($_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Include database configuration
require_once 'config/database.php';

// Get all leave types
function getLeaveTypes() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM leave_types");
    return $stmt->fetchAll();
}

// Add new leave type
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_leave_type'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $max_days = intval($_POST['max_days']);
    $can_carry_over = isset($_POST['can_carry_over']) ? 1 : 0;
    $carry_over_limit = intval($_POST['carry_over_limit']);
    
    // Validate input
    if (empty($name) || empty($max_days)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if leave type already exists
        $stmt = $pdo->prepare("SELECT * FROM leave_types WHERE name = ?");
        $stmt->execute([$name]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $error = 'Leave type already exists';
        } else {
            // Insert new leave type
            $stmt = $pdo->prepare("INSERT INTO leave_types (name, description, max_days, can_carry_over, carry_over_limit) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $max_days, $can_carry_over, $carry_over_limit])) {
                $success = 'Leave type added successfully';
                // Refresh the leave types list
                header('Location: leave_types.php');
                exit();
            } else {
                $error = 'Error adding leave type';
            }
        }
    }
}

// Update leave type
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_leave_type'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $max_days = intval($_POST['max_days']);
    $can_carry_over = isset($_POST['can_carry_over']) ? 1 : 0;
    $carry_over_limit = intval($_POST['carry_over_limit']);
    
    // Validate input
    if (empty($name) || empty($max_days)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if leave type already exists (excluding current one)
        $stmt = $pdo->prepare("SELECT * FROM leave_types WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $error = 'Leave type already exists';
        } else {
            // Update leave type
            $stmt = $pdo->prepare("UPDATE leave_types SET name = ?, description = ?, max_days = ?, can_carry_over = ?, carry_over_limit = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $max_days, $can_carry_over, $carry_over_limit, $id])) {
                $success = 'Leave type updated successfully';
                // Refresh the leave types list
                header('Location: leave_types.php');
                exit();
            } else {
                $error = 'Error updating leave type';
            }
        }
    }
}

// Delete leave type
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if leave type has associated requests
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE leave_type_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        $error = 'Cannot delete leave type with associated leave requests';
    } else {
        $stmt = $pdo->prepare("DELETE FROM leave_types WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Leave type deleted successfully';
            // Refresh the leave types list
            header('Location: leave_types.php');
            exit();
        } else {
            $error = 'Error deleting leave type';
        }
    }
}

// Now include the header after all potential redirects
require_once 'includes/header.php';

$page_title = 'Manage Leave Types';
$leave_types = getLeaveTypes();
?>

<div class="content-container">
    <h2>Manage Leave Types</h2>
    
    <?php if (!empty($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="leave-types-list">Leave Types List</button>
            <button class="tab-btn" data-tab="add-leave-type">Add New Leave Type</button>
        </div>
        
        <div class="tab-content active" id="leave-types-list">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Max Days</th>
                        <th>Can Carry Over</th>
                        <th>Carry Over Limit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leave_types as $type): ?>
                    <tr>
                        <td><?php echo $type['name']; ?></td>
                        <td><?php echo $type['description']; ?></td>
                        <td><?php echo $type['max_days']; ?></td>
                        <td><?php echo $type['can_carry_over'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $type['carry_over_limit']; ?></td>
                        <td>
                            <button class="btn-edit" data-id="<?php echo $type['id']; ?>" data-name="<?php echo $type['name']; ?>" data-description="<?php echo $type['description']; ?>" data-max-days="<?php echo $type['max_days']; ?>" data-can-carry-over="<?php echo $type['can_carry_over']; ?>" data-carry-over-limit="<?php echo $type['carry_over_limit']; ?>">Edit</button>
                            <a href="leave_types.php?delete=<?php echo $type['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this leave type?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="tab-content" id="add-leave-type">
            <h3>Add New Leave Type</h3>
            <form method="POST" action="">
                <input type="hidden" name="add_leave_type" value="1">
                
                <div class="form-group">
                    <label for="name">Leave Type Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_days">Maximum Days *</label>
                        <input type="number" id="max_days" name="max_days" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="carry_over_limit">Carry Over Limit</label>
                        <input type="number" id="carry_over_limit" name="carry_over_limit" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="can_carry_over" name="can_carry_over" value="1">
                        Allow carry over to next year
                    </label>
                </div>
                
                <button type="submit" class="btn-primary">Add Leave Type</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Leave Type Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Leave Type</h3>
            <form method="POST" action="">
                <input type="hidden" name="update_leave_type" value="1">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label for="edit_name">Leave Type Name *</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_max_days">Maximum Days *</label>
                        <input type="number" id="edit_max_days" name="max_days" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_carry_over_limit">Carry Over Limit</label>
                        <input type="number" id="edit_carry_over_limit" name="carry_over_limit" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_can_carry_over" name="can_carry_over" value="1">
                        Allow carry over to next year
                    </label>
                </div>
                
                <button type="submit" class="btn-primary">Update Leave Type</button>
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
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_description').value = this.getAttribute('data-description');
            document.getElementById('edit_max_days').value = this.getAttribute('data-max-days');
            document.getElementById('edit_carry_over_limit').value = this.getAttribute('data-carry-over-limit');
            
            // Set checkbox state
            const canCarryOver = this.getAttribute('data-can-carry-over') === '1';
            document.getElementById('edit_can_carry_over').checked = canCarryOver;
            
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