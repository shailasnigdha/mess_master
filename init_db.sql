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

-- Meal Plans Table (Admin sets meals by type and date)
CREATE TABLE meal_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_date DATE NOT NULL,
    meal_type ENUM('BREAKFAST','LUNCH','DINNER') NOT NULL,
    meal_name VARCHAR(200) NOT NULL,
    meal_description TEXT,
    meal_price DECIMAL(10,2) NOT NULL,
    is_vacation_day BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_meal_date_type (meal_date, meal_type)
);

-- User Meal Selections Table (User toggles individual meals)
CREATE TABLE user_meal_selections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    meal_plan_id INT NOT NULL,
    meal_date DATE NOT NULL,
    meal_type ENUM('BREAKFAST','LUNCH','DINNER') NOT NULL,
    status ENUM('ON','OFF') DEFAULT 'OFF',
    payment_status ENUM('PAID','DUE') DEFAULT 'DUE',
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_plan_id) REFERENCES meal_plans(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_meal_date_type (user_id, meal_date, meal_type)
);

-- Monthly Meal Summary Table (Tracks individual meal types)
CREATE TABLE monthly_meal_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    total_breakfast_opted INT DEFAULT 0,
    total_lunch_opted INT DEFAULT 0,
    total_dinner_opted INT DEFAULT 0,
    total_amount_due DECIMAL(10,2) DEFAULT 0,
    total_amount_paid DECIMAL(10,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month_year (user_id, month, year)
);

-- Default Admin (Change password after first login)
INSERT INTO admins (username, password_hash, name, email)
VALUES ('admin', 'admin123', 'System Administrator', 'admin@messmaster.com')
ON DUPLICATE KEY UPDATE 
username = VALUES(username);

