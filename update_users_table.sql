-- Add username and role columns to users table
ALTER TABLE users 
ADD COLUMN username VARCHAR(50) UNIQUE AFTER id,
ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER email;
