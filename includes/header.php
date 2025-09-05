<?php
// includes/header.php

// This file will be included in all pages after login

// Include notification functions
require_once 'notification_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'ELMS'; ?> - St. Francis Xavier Hospital</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="hospital-info">
                <h1>St. Francis Xavier Hospital</h1>
                <p>Employee Leave Management System</p>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></span>
                <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="apply_leave.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'apply_leave.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Apply Leave</span>
                    </a>
                </li>
                <li>
                    <a href="leave_history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'leave_history.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        <span>Leave History</span>
                    </a>
                </li>
                <li>
                    <a href="notification.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'notification.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                        <?php
                        $unread_count = getUnreadNotificationCount($_SESSION['user_id']);
                        if ($unread_count > 0): ?>
                        <span class="sidebar-notification-count"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                
                <?php if ($_SESSION['role'] == 'manager' || $_SESSION['role'] == 'admin'): ?>
                <li class="nav-section">
                    <span>Management</span>
                </li>
                <li>
                    <a href="manage_requests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_requests.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Manage Requests</span>
                    </a>
                </li>
                <li>
                    <a href="team_calendar.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'team_calendar.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Team Calendar</span>
                    </a>
                </li>
                <li>
                    <a href="manager_reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manager_reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <li class="nav-section">
                    <span>Administration</span>
                </li>
                <li>
                    <a href="manage_employees.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_employees.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Manage Employees</span>
                    </a>
                </li>
                <li>
                    <a href="leave_types.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'leave_types.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list-alt"></i>
                        <span>Leave Types</span>
                    </a>
                </li>
                <li>
                    <a href="policies.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'policies.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>Policies</span>
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-pie"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-container" id="mainContainer">
        <header class="top-header">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="page-title">
                <h2><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h2>
            </div>
            <div class="header-actions">
                <span class="welcome-message">Welcome, <?php echo $_SESSION['first_name']; ?></span>
                <div class="notification-bell">
                    <a href="notification.php">
                        <i class="fas fa-bell"></i>
                        <?php
                        $unread_count = getUnreadNotificationCount($_SESSION['user_id']);
                        if ($unread_count > 0): ?>
                        <span class="notification-count"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </header>
        
        <main class="main-content">