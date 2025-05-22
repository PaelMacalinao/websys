<?php
session_start();
require_once 'config.php';

function resetAdminAccount($pdo) {
    try {
        // Drop existing users table
        $pdo->exec("DROP TABLE IF EXISTS users");

        // Create fresh users table
        $pdo->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            role ENUM('user', 'admin') DEFAULT 'user',
            mobile VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Create admin account
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, role, mobile, password) 
            VALUES ('admin', 'Admin', 'User', 'admin@connect.com', 'admin', '00000000000', ?)");
        $stmt->execute([$hashedPassword]);

        echo "Admin account has been reset successfully!<br>";
        echo "Login credentials:<br>";
        echo "Email: admin@connect.com<br>";
        echo "Password: admin123<br>";
        echo "<br><a href='login.php'>Go to Login Page</a>";

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Execute the reset
resetAdminAccount($pdo);
?>
