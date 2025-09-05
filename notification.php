<?php
// notification.php
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/notification_functions.php'; // Add this line

$page_title = 'Notifications';

// Mark notification as read
if (isset($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    header('Location: notification.php');
    exit();
}

// Mark all notifications as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$_SESSION['user_id']]);
    header('Location: notification.php');
    exit();
}

// Delete notification
if (isset($_GET['delete'])) {
    $notification_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $_SESSION['user_id']]);
    header('Location: notification.php');
    exit();
}

// Clear all notifications
if (isset($_POST['clear_all'])) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    header('Location: notification.php');
    exit();
}

// Get user's notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Count unread notifications
$unread_count = 0;
foreach ($notifications as $notification) {
    if (!$notification['is_read']) {
        $unread_count++;
    }
}
?>

<div class="content-container">
    <div class="notifications-header">
        <h2>Notifications</h2>
        <div class="notification-actions">
            <?php if ($unread_count > 0): ?>
            <form method="POST" action="" style="display: inline;">
                <button type="submit" name="mark_all_read" class="btn-primary">Mark All as Read</button>
            </form>
            <?php endif; ?>
            
            <?php if (!empty($notifications)): ?>
            <form method="POST" action="" style="display: inline;">
                <button type="submit" name="clear_all" class="btn-danger" onclick="return confirm('Are you sure you want to clear all notifications?')">Clear All</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="notification-stats">
        <span class="stat-item">Total: <?php echo count($notifications); ?></span>
        <span class="stat-item">Unread: <span class="unread-count"><?php echo $unread_count; ?></span></span>
    </div>
    
    <?php if (empty($notifications)): ?>
    <div class="empty-state">
        <i class="fas fa-bell-slash"></i>
        <h3>No notifications yet</h3>
        <p>You don't have any notifications at the moment.</p>
    </div>
    <?php else: ?>
    
    <div class="notifications-list">
        <?php foreach ($notifications as $notification): ?>
        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?> notification-<?php echo $notification['type']; ?>">
            <div class="notification-icon">
                <?php switch($notification['type']):
                    case 'success': ?>
                        <i class="fas fa-check-circle"></i>
                        <?php break;
                    case 'warning': ?>
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php break;
                    case 'danger': ?>
                        <i class="fas fa-exclamation-circle"></i>
                        <?php break;
                    default: ?>
                        <i class="fas fa-info-circle"></i>
                <?php endswitch; ?>
            </div>
            
            <div class="notification-content">
                <h4 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h4>
                <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                <div class="notification-meta">
                    <span class="notification-time"><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></span>
                    
                    <?php if ($notification['related_url']): ?>
                    <a href="<?php echo $notification['related_url']; ?>" class="notification-link">View Details</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="notification-actions">
                <?php if (!$notification['is_read']): ?>
                <a href="notification.php?mark_read=<?php echo $notification['id']; ?>" class="btn-mark-read" title="Mark as read">
                    <i class="fas fa-check"></i>
                </a>
                <?php endif; ?>
                
                <a href="notification.php?delete=<?php echo $notification['id']; ?>" class="btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this notification?')">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>