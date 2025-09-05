<?php
// login.php

require_once 'config/database.php';
session_start();

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (!empty($email) && !empty($password)) {
        // PostgreSQL uses standard placeholders
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Store session data
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['first_name']  = $user['first_name'];
            $_SESSION['last_name']   = $user['last_name'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['department']  = $user['department'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - St. Francis Xavier Hospital ELMS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body" style="background-image: url('images/bg4.jpg'); background-size: cover; background-position: center;">
    <div class="login-container" style="max-width: 400px; margin: 5% auto; background: rgba(255, 255, 255, 0.7); padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
         <div class="login-logo">
            <img style="width: 140px; display: block; margin: 0 auto;" src="images/logo.png" alt="Logo">
        </div>
        <div class="login-header">
            <h1>St. Francis Xavier Hospital</h1>
            <h2>Employee Leave Management System</h2>
        </div>
        
        <form method="POST" action="" class="login-form">
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-primary">Login</button>
        </form>
    </div>
</body>
</html>