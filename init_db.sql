CREATE DATABASE IF NOT EXISTS mess_master;
USE mess_master;

-- Admins Table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(150),
    email VARCHAR(150)
);

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(150),
    hall_id VARCHAR(50),
    room_no VARCHAR(50),
    type ENUM('Residential','Non-Residential') NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(50)
);

-- Notices Table
CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Meals Table
CREATE TABLE meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    meal_date DATE,
    status ENUM('ON','OFF') DEFAULT 'ON',
    price DECIMAL(10,2),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Dues Table
CREATE TABLE dues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    status ENUM('Pending','Paid') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Default Admin (Change password after first login)
INSERT INTO admins (username, password_hash, name, email)
VALUES ('admin', 'admin123', 'System Administrator', 'admin@messmaster.com')
ON DUPLICATE KEY UPDATE 
username = VALUES(username);
