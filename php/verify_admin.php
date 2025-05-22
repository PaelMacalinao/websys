<?php
require_once 'config.php';

// Function to create tables and admin account
function setupDatabase($pdo) {
    try {
        // Create users table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
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

        // Check if admin exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(['admin@connect.com']);
        $admin = $stmt->fetch();

        if (!$admin) {
            // Create new admin account
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, role, mobile, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                'admin',
                'Admin',
                'User',
                'admin@connect.com',
                'admin',
                '00000000000',
                $hashedPassword
            ]);
            echo "Admin account created successfully!<br>";
        } else {
            // Update existing admin password
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, 'admin@connect.com']);
            echo "Admin password reset successfully!<br>";
        }

        // Verify admin account
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['admin@connect.com']);
        $admin = $stmt->fetch();

        if ($admin && password_verify('admin123', $admin['password'])) {
            echo "Admin account verification successful!<br>";
            echo "Email: admin@connect.com<br>";
            echo "Password: admin123<br>";
        } else {
            echo "Admin account verification failed!<br>";
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}

// Run the setup
setupDatabase($pdo);
?>
