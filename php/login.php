<?php
session_start();
require 'config.php';

$error = '';
$success = $_SESSION['registration_success'] ?? '';
unset($_SESSION['registration_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        header('Location: user-shop.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Connect</title>
    <link rel="stylesheet" href="../css/register-login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="main-container">
        <div class="left-section">
            <div class="content">
                <img src="../images/connect-logo.png" alt="Connect Logo" class="logo">
                <h1>CONNECT</h1>
                <p>One-stop shop for everyday essentials online.</p>
                <button class="about-btn">Log In</button>
                <div class="divider"></div>
            </div>
        </div>
        
        <div class="right-section">
            <div class="login-container">
                <?php if ($error): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form id="loginForm" action="login.php" method="post">
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Email" required>
                        </div>
                        <span class="error-message" id="email_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Password" required>
                        </div>
                        <span class="error-message" id="password_error"></span>
                    </div>
                    
                    <button type="submit" class="register-btn">Login</button>
                </form>
                
                <div class="login-link">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/login.js"></script>
</body>
</html>