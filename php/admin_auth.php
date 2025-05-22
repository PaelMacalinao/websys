<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email === 'admin@connect.com' && $password === 'admin123') {
        // Get admin user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = 'admin';
            header('Location: admin/dashboard.php');
            exit;
        }
    }
    
    $_SESSION['error'] = 'Invalid admin credentials';
    header('Location: admin_login.php');
    exit;
}

// If not POST request, redirect to admin login page
header('Location: admin_login.php');
exit;
