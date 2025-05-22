<?php
require_once 'config.php';

// Check if admin account already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$adminExists = $stmt->fetch();

if (!$adminExists) {
    // Create admin account
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $email = 'admin@connect.com';
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
    
    try {
        $stmt->execute([$username, $password, $email]);
        echo "Admin account created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "Please change your password after first login.";
    } catch (PDOException $e) {
        echo "Error creating admin account: " . $e->getMessage();
    }
} else {
    echo "An admin account already exists.";
}
?>
