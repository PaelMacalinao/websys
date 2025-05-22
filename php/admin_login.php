<?php
session_start();
require_once 'config.php';

// First, ensure admin account exists
try {
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@connect.com'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if (!$admin) {
        // Create admin account
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, role, mobile, password) 
            VALUES ('admin', 'Admin', 'User', 'admin@connect.com', 'admin', '00000000000', ?)");
        $stmt->execute([$hashedPassword]);
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Connect</title>
    <link rel="stylesheet" href="../css/register-login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .admin-login-notice {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e3e8ff;
            border-radius: 5px;
            color: #4f46e5;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="left-section">
            <div class="content">
                <img src="../images/connect-logo.png" alt="Connect Logo" class="logo">
                <h1>CONNECT ADMIN</h1>
                <p>Administrative Control Panel</p>
                <div class="divider"></div>
            </div>
        </div>
        
        <div class="right-section">
            <div class="login-container">
                <div class="admin-login-notice">
                    <strong>Admin Login</strong><br>
                    Email: admin@connect.com<br>
                    Password: admin123
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <form action="admin_auth.php" method="post">
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" value="admin@connect.com" placeholder="Email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" value="admin123" placeholder="Password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="register-btn">Login as Admin</button>
                </form>
                
                <div class="login-link">
                    <p><a href="login.php">Back to User Login</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
