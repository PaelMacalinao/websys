<?php
session_start();
require 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $error = 'Email already exists.';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, email, mobile, password) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$first_name, $middle_name, $last_name, $email, $mobile, $hashed_password])) {
            $_SESSION['registration_success'] = 'Registration successful! You can now login.';
            header('Location: login.php');
            exit;
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Connect</title>
    <link rel="stylesheet" href="../css/register-login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="main-container">
        <div class="left-section">
            <div class="content">
                <img src="../images/connect-logo.png" alt="Amihan Isles Logo" class="logo">
                <h1>CONNECT</h1>
                <p>One-stop shop for everyday essentials online.</p>
                <button class="about-btn">Register</button>
                <div class="divider"></div>
            </div>
        </div>
        
        <div class="right-section">
            <div class="register-container">
                <?php if ($error): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form id="registerForm" action="register.php" method="post">
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                        </div>
                        <span class="error-message" id="first_name_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="middle_name" name="middle_name" placeholder="Middle Name">
                        </div>
                        <span class="error-message" id="middle_name_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                        </div>
                        <span class="error-message" id="last_name_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Email" required>
                        </div>
                        <span class="error-message" id="email_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-phone"></i>
                            <input type="text" id="mobile" name="mobile" placeholder="Mobile Number" required>
                        </div>
                        <span class="error-message" id="mobile_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Password" required>
                        </div>
                        <span class="error-message" id="password_error"></span>
                    </div>
                    
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                        </div>
                        <span class="error-message" id="confirm_password_error"></span>
                    </div>
                    
                    <button type="submit" class="register-btn">Register</button>
                </form>
                
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Log in here</a></p>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/register.js"></script>
</body>
</html>