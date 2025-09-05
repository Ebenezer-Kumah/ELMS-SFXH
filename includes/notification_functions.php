<?php
// includes/notification_functions.php

// Check if function already exists
if (!function_exists('createNotification')) {

/**
 * Create a new notification
 */
function createNotification($user_id, $title, $message, $type = 'info', $related_url = null) {
    global $pdo;
    
    try {
        // Ensure notifications table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            related_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, related_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $type, $related_url]);
        return true;
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotificationCount($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Create notification for leave request status change
 */
function notifyLeaveStatusChange($request_id, $status) {
    global $pdo;
    
    // Get leave request details
    $stmt = $pdo->prepare("SELECT lr.*, u.first_name, u.last_name, u.id as user_id, lt.name as leave_type_name 
                          FROM leave_requests lr 
                          JOIN users u ON lr.employee_id = u.id 
                          JOIN leave_types lt ON lr.leave_type_id = lt.id 
                          WHERE lr.id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    
    if (!$request) return false;
    
    $user_id = $request['user_id'];
    $leave_type = $request['leave_type_name'];
    $start_date = date('M j, Y', strtotime($request['start_date']));
    $end_date = date('M j, Y', strtotime($request['end_date']));
    
    switch ($status) {
        case 'approved':
            $title = "Leave Request Approved";
            $message = "Your $leave_type request from $start_date to $end_date has been approved.";
            $type = "success";
            break;
            
        case 'rejected':
            $title = "Leave Request Rejected";
            $message = "Your $leave_type request from $start_date to $end_date has been rejected.";
            $type = "danger";
            break;
            
        default:
            return false;
    }
    
    $related_url = "leave_history.php";
    return createNotification($user_id, $title, $message, $type, $related_url);
}

/**
 * Create notification for new leave request (for managers)
 */
function notifyNewLeaveRequest($request_id, $manager_id) {
    global $pdo;
    
    // Get leave request details
    $stmt = $pdo->prepare("SELECT lr.*, u.first_name, u.last_name, lt.name as leave_type_name 
                          FROM leave_requests lr 
                          JOIN users u ON lr.employee_id = u.id 
                          JOIN leave_types lt ON lr.leave_type_id = lt.id 
                          WHERE lr.id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    
    if (!$request) return false;
    
    $employee_name = $request['first_name'] . ' ' . $request['last_name'];
    $leave_type = $request['leave_type_name'];
    $start_date = date('M j, Y', strtotime($request['start_date']));
    $end_date = date('M j, Y', strtotime($request['end_date']));
    
    $title = "New Leave Request";
    $message = "$employee_name has submitted a $leave_type request from $start_date to $end_date.";
    $type = "info";
    $related_url = "manage_requests.php";
    
    return createNotification($manager_id, $title, $message, $type, $related_url);
}

} // End of function_exists check