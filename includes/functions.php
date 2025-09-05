<?php
// includes/functions.php

// Include configuration
require_once dirname(__FILE__) . '/../config/database.php';

function getLeaveTypes() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM leave_types");
    return $stmt->fetchAll();
}

function getLeaveBalance($employee_id, $leave_type_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT balance FROM leave_balances WHERE employee_id = ? AND leave_type_id = ? AND year = YEAR(CURDATE())");
    $stmt->execute([$employee_id, $leave_type_id]);
    $result = $stmt->fetch();
    return $result ? $result['balance'] : 0;
}

function getLeaveRequests($employee_id = null, $status = null) {
    global $pdo;
    
    $query = "SELECT lr.*, u.first_name, u.last_name, lt.name as leave_type_name 
              FROM leave_requests lr 
              JOIN users u ON lr.employee_id = u.id 
              JOIN leave_types lt ON lr.leave_type_id = lt.id";
    
    $params = [];
    
    if ($employee_id) {
        $query .= " WHERE lr.employee_id = ?";
        $params[] = $employee_id;
    }
    
    if ($status) {
        $query .= $employee_id ? " AND" : " WHERE";
        $query .= " lr.status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY lr.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

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

?>