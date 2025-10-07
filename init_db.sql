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

-- Optional payments table to track payment history (if used by admin)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    paid_on DATE NOT NULL,
    reference VARCHAR(100) NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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

-- Seed Notices (latest first)
INSERT INTO notices (title, description, created_at) VALUES
('Mess Hall Maintenance & Menu Update', 'Dear residents,

Kindly note that the mess hall will undergo routine maintenance on Friday, 10 October 2025, from 2:00 PM to 5:00 PM. During this time the dining area will remain closed for cleaning and equipment checks.

To ensure everyone is still served, snacks and beverages will be available at the common room kiosk.

Also, the weekend menu has been refreshed! Visit the notice board or your Mess Master dashboard to see the new dishes planned for Saturday and Sunday.

Thank you for your cooperation.
— Mess Administration', '2025-10-08 09:00:00'),
('Cultural Evening & Open Mic Night', 'Hello everyone,

We''re excited to host a Cultural Evening and Open Mic Night on Saturday, 18 October 2025, starting at 7:30 PM in the mess courtyard. Solo performers and group acts are equally welcome!

If you''d like to perform, please submit your entry to the cultural committee by Wednesday, 15 October. A short rehearsal session is scheduled for Friday evening.

Family-friendly refreshments will be served. Bring your friends and let''s make it a memorable night.', '2025-10-07 18:30:00'),
('October Mess Dues Reminder', 'Dear boarders,

This is a reminder that October mess dues are payable by Thursday, 31 October 2025. Please clear your dues at the accounts desk between 10:00 AM and 2:00 PM on weekdays.

Late payments after 31 October will incur a late fee of ₹150. If you have already paid, kindly ignore this message.

For any billing discrepancies, contact the mess accounts office or email accounts@messmaster.com.', '2025-10-05 11:15:00');

