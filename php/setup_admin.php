USE ecommerce_db;

-- First, check and add any missing columns
ALTER TABLE users
ADD COLUMN IF NOT EXISTS username VARCHAR(50) AFTER id,
ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') DEFAULT 'user' AFTER email;

-- Create admin account if it doesn't exist
INSERT IGNORE INTO users (
    username,
    first_name,
    last_name,
    email,
    mobile,
    password,
    role
) VALUES (
    'admin',
    'Admin',
    'User',
    'admin@connect.com',
    '00000000000',
    '$2y$10$8KzS.z6C6qXUZWpwx6QO8.2zH3L7mp.MkAr.ReuJNw5ShLBsHDtTi', -- This is the hashed version of 'admin123'
    'admin'
);
