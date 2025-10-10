# Mess Master System - Complete Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Requirements](#system-requirements)
3. [Installation Guide](#installation-guide)
4. [Database Setup](#database-setup)
5. [System Architecture](#system-architecture)
6. [User Roles & Permissions](#user-roles--permissions)
7. [Features Documentation](#features-documentation)
8. [API Endpoints](#api-endpoints)
9. [File Structure](#file-structure)
10. [Configuration](#configuration)
11. [Troubleshooting](#troubleshooting)
12. [Version History](#version-history)

---

## Project Overview

**Project Name:** Mess Master - Complete Mess Management System  
**Version:** 2.0  
**Developer:** Shaila Snigdha  
**Repository:** https://github.com/shailasnigdha/mess_master  
**Technology:** PHP, MySQL, HTML5, CSS3, JavaScript  
**Currency:** Bangladeshi Taka (Tk)  
**License:** MIT License  

### Purpose
Mess Master is a comprehensive web-based meal management system designed specifically for mess operations in Bangladesh. It provides complete control over meal planning, user management, payment tracking, and administrative oversight.

### Key Features
- ✅ Individual meal selection (breakfast, lunch, dinner)
- ✅ Admin user management with full CRUD operations
- ✅ Vacation day management with meal restrictions
- ✅ Real-time dues tracking and payment confirmation
- ✅ Responsive design for mobile and desktop
- ✅ Role-based access control (Admin/User)
- ✅ Monthly meal summaries and reporting

---

## System Requirements

### Minimum Requirements
- **Web Server:** Apache 2.4+
- **PHP:** Version 7.4 or higher
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **Browser:** Chrome 70+, Firefox 65+, Safari 12+, Edge 79+
- **Storage:** 100MB minimum disk space
- **Memory:** 512MB RAM minimum

### Recommended Requirements
- **Web Server:** Apache 2.4+ with mod_rewrite enabled
- **PHP:** Version 8.0+ with mysqli extension
- **Database:** MySQL 8.0+ with InnoDB storage engine
- **Storage:** 1GB available disk space
- **Memory:** 2GB RAM or higher

### Required PHP Extensions
```
- mysqli
- session
- json
- date
- filter
```

---

## Installation Guide

### Step 1: Download and Setup XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP to `C:\xampp\`
3. Start Apache and MySQL services

### Step 2: Clone/Download Project
```bash
# Clone from GitHub
git clone https://github.com/shailasnigdha/mess_master.git C:\xampp\htdocs\mess_master

# OR download ZIP and extract to C:\xampp\htdocs\mess_master
```

### Step 3: Database Configuration
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create new database named `mess_master`
3. Import the database schema:
   ```sql
   # Run the contents of init_db.sql
   ```

### Step 4: Configure Database Connection
Edit `config.php`:
```php
<?php
$servername = "localhost";
$username = "root";
$password = "";  // Your MySQL password
$dbname = "mess_master";
?>
```

### Step 5: Access the System
- Open browser and go to: `http://localhost/mess_master`
- Default admin login: 
  - Username: `admin`
  - Password: `admin123`

---

## Database Setup

### Database Schema
The system uses the following main tables:

#### Core Tables
```sql
-- Administrators table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(150),
    email VARCHAR(150)
);

-- Users (mess residents) table
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

-- Meal plans table
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

-- User meal selections table
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

-- Monthly meal summary table
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

-- Notices table
CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Manual dues table
CREATE TABLE dues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    status ENUM('Pending','Paid') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Sample Data
The system includes sample data for:
- Default admin user
- Sample notices
- Example meal plans for testing

---

## System Architecture

### MVC Architecture Pattern
```
Model (Database Layer)
├── Users Management
├── Meal Plans Management
├── Dues Management
└── Notices Management

View (Presentation Layer)
├── Admin Interface
│   ├── Dashboard
│   ├── User Management
│   ├── Meal Management
│   └── Notices Management
└── User Interface
    ├── Dashboard
    ├── Meal Selection
    ├── Dues Tracking
    └── Notices Viewing

Controller (Business Logic)
├── Authentication
├── Session Management
├── Data Validation
└── Business Rules
```

### Data Flow
1. **User Authentication** → Session Creation → Role-based Redirection
2. **Admin Actions** → Validation → Database Updates → UI Feedback
3. **User Actions** → Permission Check → Data Processing → Summary Updates
4. **Reports Generation** → Data Aggregation → Display Formatting

---

## User Roles & Permissions

### Admin Role Permissions
- ✅ **User Management:** Create, read, update, delete users
- ✅ **Meal Management:** Add, edit, delete meal plans
- ✅ **Vacation Management:** Set vacation days, block meals
- ✅ **Dues Management:** View user dues, mark payments as confirmed
- ✅ **Notice Management:** Create, edit, delete system notices
- ✅ **Reporting:** View system statistics and user summaries

### User Role Permissions
- ✅ **Meal Selection:** Choose individual breakfast, lunch, dinner
- ✅ **Payment Options:** Select "Pay Now" or "Add to Dues"
- ✅ **Dues Viewing:** Check monthly meal summaries and payment status
- ✅ **Notice Viewing:** Read system announcements
- ❌ **Administrative Functions:** Cannot access admin features

### Session Management
- Session timeout: 24 hours of inactivity
- Role-based access control enforced on every page
- Secure session handling with regeneration

---

## Features Documentation

### 1. User Management System

#### Admin User Management
```php
Location: admin_manage_users.php
Features:
- Add new users with complete profile information
- Edit existing user details with modal interface
- Delete users with confirmation prompts
- View user statistics and summaries
- Manage user dues and payment status
```

**Add User Form Fields:**
- Username (unique)
- Password
- Full Name
- User Type (Residential/Non-Residential)
- Hall ID
- Room Number
- Email Address
- Phone Number

**Edit User Functionality:**
- Pre-populated form with current user data
- Optional password update
- Username uniqueness validation
- Real-time form validation

#### User Profile Management
```php
Location: user_profile.php
Features:
- View personal profile information
- Update contact details
- Change password functionality
```

### 2. Meal Management System

#### Admin Meal Management
```php
Location: admin_meals.php
Features:
- Add individual meals (breakfast, lunch, dinner)
- Set meal prices in Bangladeshi Taka
- Mark vacation days with automatic meal blocking
- View meal statistics and user participation
- Edit existing meal plans
```

**Meal Plan Fields:**
- Meal Date
- Meal Type (Breakfast/Lunch/Dinner)
- Meal Name (auto-generated or custom)
- Meal Description
- Meal Price (Tk)
- Vacation Day Option

**Vacation Day Management:**
- Prevents adding meals on vacation days
- Automatically removes existing meals when vacation is marked
- JavaScript form validation for vacation selection
- Visual feedback with disabled form fields

#### User Meal Selection
```php
Location: user_meals.php  
Features:
- Calendar view of available meals
- Individual meal selection (turn on/off)
- Payment option selection for each meal
- Monthly meal summary with counts and totals
- Real-time status updates
```

**Meal Selection Process:**
1. User views monthly meal calendar
2. Selects individual meals (breakfast, lunch, dinner)
3. Chooses payment method: "Pay Now" or "Add to Dues"
4. System updates monthly summary automatically
5. Admin can view user participation statistics

**Restrictions:**
- Cannot modify meals less than 1 day in advance
- Vacation days show as unavailable
- Past meals cannot be modified

### 3. Dues Management System

#### Admin Dues Management
```php
Location: admin_manage_users.php (Dues Modal)
Features:
- View user monthly meal dues
- Add manual dues for additional charges
- Mark entire months as paid with one click
- Track payment status (Paid/Pending)
- Real-time dues updates
```

**Dues Management Process:**
1. Admin opens "Manage Dues" modal for user
2. Views monthly meal dues and manual dues
3. Can add additional manual charges
4. Marks months as paid to confirm payment
5. System updates user dues page immediately

#### User Dues Tracking
```php
Location: user_dues.php
Features:
- Monthly meal breakdown with meal counts
- Payment status indicators (Paid/Pending)
- Total due amounts in Bangladeshi Taka
- Historical dues information
- Clear payment status from admin
```

**Dues Display:**
- Month-wise meal breakdown
- Individual meal counts (breakfast, lunch, dinner)
- Total due amounts
- Payment status badges
- Admin payment confirmation indicators

### 4. Notice Management System

#### Admin Notice Management
```php
Location: admin_notices.php
Features:
- Create system-wide notices
- Edit existing notices
- Delete notices with confirmation
- Categorize notices by importance
- Real-time notice publishing
```

#### User Notice Viewing
```php
Location: user_notices.php
Features:
- View all system notices
- Chronological notice ordering
- Notice search and filtering
- Mobile-responsive notice display
```

### 5. Dashboard Systems

#### Admin Dashboard
```php
Location: admin_dashboard.php
Features:
- System statistics overview
- Total users count (Residential/Non-Residential)
- Recent notices display
- Quick access to all management functions
- System health indicators
```

**Dashboard Statistics:**
- Total Users
- Residential Users Count
- Non-Residential Users Count
- Recent System Activity
- Quick Action Buttons

#### User Dashboard
```php
Location: user_dashboard.php
Features:
- Personal meal summary
- Upcoming meals display
- Payment status overview
- Recent notices
- Quick navigation to meal selection
```

---

## API Endpoints

### Authentication Endpoints
```php
POST /login.php
- Parameters: username, password, role
- Response: Session creation, role-based redirect
- Error Handling: Invalid credentials, missing fields
```

### Admin API Endpoints
```php
POST /admin_manage_users.php
- add_user: Create new user account
- edit_user: Update existing user information
- add_due: Add manual due for user
- update_due_status: Change due payment status
- mark_month_paid: Mark entire month as paid

POST /admin_meals.php
- add_meal: Create new meal plan
- delete_meal: Remove meal plan

POST /admin_notices.php
- add_notice: Create system notice
- edit_notice: Update existing notice
- delete_notice: Remove notice
```

### User API Endpoints
```php
POST /user_meals.php
- toggle_meal: Turn meal on/off for specific date
- Parameters: meal_plan_id, meal_date, meal_type, new_status, payment_choice
- Response: Updated meal status, monthly summary recalculation

GET /admin_get_user_dues.php
- Parameters: user_id
- Response: User dues information, monthly summaries
- Used by: Admin dues management modal
```

### Data Validation
- All forms use PHP server-side validation
- JavaScript client-side validation for user experience
- SQL injection prevention with prepared statements
- XSS protection through output sanitization

---

## File Structure

```
mess_master/
├── config.php                 # Database configuration
├── init_db.sql               # Database schema and sample data
├── index.php                 # Landing page
├── login.php                 # Authentication system
├── logout.php                # Session termination
├── style.css                 # Main stylesheet
│
├── Admin Files/
│   ├── admin_dashboard.php       # Admin main dashboard
│   ├── admin_manage_users.php    # User CRUD operations
│   ├── admin_meals.php           # Meal management
│   ├── admin_notices.php         # Notice management
│   └── admin_get_user_dues.php   # Dues API endpoint
│
├── User Files/
│   ├── user_dashboard.php        # User main dashboard
│   ├── user_meals.php            # Meal selection interface
│   ├── user_dues.php             # Dues tracking
│   ├── user_notices.php          # Notice viewing
│   └── user_profile.php          # Profile management
│
└── Documentation/
    ├── README.md                 # Project overview
    ├── MESS_MASTER_DOCUMENTATION.md  # This file
    └── diagrams/                 # System diagrams
```

### File Descriptions

#### Core System Files
- **config.php**: Database connection configuration
- **index.php**: Landing page with system overview and login access
- **login.php**: Authentication system with role-based login
- **logout.php**: Secure session termination
- **style.css**: Comprehensive stylesheet with responsive design

#### Admin Module Files
- **admin_dashboard.php**: Main admin interface with statistics
- **admin_manage_users.php**: Complete user management with CRUD operations
- **admin_meals.php**: Meal planning and vacation management
- **admin_notices.php**: System-wide notice management
- **admin_get_user_dues.php**: AJAX endpoint for dues information

#### User Module Files
- **user_dashboard.php**: Personal dashboard with meal summary
- **user_meals.php**: Individual meal selection calendar
- **user_dues.php**: Monthly dues tracking and payment status
- **user_notices.php**: System notices viewing
- **user_profile.php**: Personal profile management

---

## Configuration

### Database Configuration (config.php)
```php
<?php
// Database connection settings
$servername = "localhost";     // Database server
$username = "root";            // Database username
$password = "";                // Database password
$dbname = "mess_master";       // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");
?>
```

### Session Configuration
```php
// Session settings (in each PHP file)
session_start();
session_set_cookie_params([
    'lifetime' => 86400,  // 24 hours
    'path' => '/',
    'domain' => '',
    'secure' => false,    // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

### System Settings
```php
// Timezone setting
date_default_timezone_set('Asia/Dhaka');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Currency format
$currency_symbol = 'Tk';
$currency_format = 'Tk %s';
```

### Security Configuration
```php
// Password hashing (implement in production)
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$password_verify = password_verify($password, $stored_hash);

// SQL injection prevention
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
```

---

## Troubleshooting

### Common Issues and Solutions

#### 1. Database Connection Issues
**Error:** "Connection failed: Access denied for user"
**Solution:**
- Check MySQL username and password in config.php
- Ensure MySQL service is running in XAMPP
- Verify database name exists in phpMyAdmin

#### 2. Login Issues
**Error:** "Invalid credentials" for admin login
**Solution:**
- Default admin credentials: username=`admin`, password=`admin123`
- Check if admin record exists in `admins` table
- Verify role selection in login form

#### 3. Meal Selection Issues
**Error:** "Cannot change meal preference"
**Solution:**
- Check if meal date is more than 1 day in future
- Verify meal plans exist for selected date
- Ensure user is not trying to modify past meals

#### 4. Dues Display Issues
**Error:** Monthly summary not updating
**Solution:**
- Check if `monthly_meal_summary` table exists
- Verify foreign key relationships
- Run manual summary calculation query

#### 5. Navigation Issues
**Error:** "Page not found" errors
**Solution:**
- Ensure all PHP files are in correct directory
- Check file permissions (644 for files, 755 for directories)
- Verify web server is running

#### 6. JavaScript Issues
**Error:** Modal dialogs not working
**Solution:**
- Enable JavaScript in browser
- Check browser console for errors
- Verify jQuery library is loaded

### Performance Optimization

#### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_meal_date ON meal_plans(meal_date);
CREATE INDEX idx_user_meal ON user_meal_selections(user_id, meal_date);
CREATE INDEX idx_monthly_summary ON monthly_meal_summary(user_id, month, year);
```

#### PHP Optimization
```php
// Enable output buffering
ob_start();

// Optimize database queries
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");

// Close database connections
$conn->close();
```

### Debugging Steps
1. **Check PHP Error Logs:** Look in XAMPP/logs/error.log
2. **Enable Error Display:** Set `display_errors = On` in php.ini
3. **Database Query Debugging:** Use phpMyAdmin to test queries
4. **Browser Developer Tools:** Check console for JavaScript errors
5. **Session Debugging:** Verify session variables with `var_dump($_SESSION)`

---

## Version History

### Version 2.0 (Current) - October 2025
**Major Features Added:**
- ✅ Individual meal selection (breakfast, lunch, dinner)
- ✅ Complete user edit functionality with modal interface
- ✅ Vacation day restrictions with automatic meal blocking
- ✅ Admin-controlled payment confirmation system
- ✅ Currency conversion to Bangladeshi Taka (Tk)
- ✅ Simplified language ("turn on/off" vs "opt in/out")
- ✅ Removed balance calculations for cleaner dues interface
- ✅ Enhanced navigation consistency across admin pages
- ✅ Real-time AJAX updates for dues management
- ✅ Comprehensive form validation and error handling

**Technical Improvements:**
- Fixed bind_param type mismatches
- Improved JavaScript form interactions
- Enhanced security with prepared statements
- Mobile-responsive design improvements
- Optimized database queries

### Version 1.0 - Initial Release
**Core Features:**
- Basic user authentication system
- Simple meal planning interface
- User registration and management
- Basic dues tracking
- Notice system
- Admin dashboard with statistics

### Planned Features (Future Versions)
- **Version 2.1:**
  - Email notification system
  - Advanced reporting dashboard
  - Export functionality (PDF/Excel)
  - Multi-language support (Bengali/English)

- **Version 3.0:**
  - Mobile application (Android/iOS)
  - Payment gateway integration
  - SMS notifications
  - Advanced analytics and insights
  - Cloud deployment options

---

## Support and Maintenance

### Getting Help
- **Documentation:** This file covers most common scenarios
- **GitHub Issues:** Report bugs at https://github.com/shailasnigdha/mess_master/issues
- **Email Support:** Contact developer through GitHub profile

### Regular Maintenance Tasks
1. **Database Backup:** Regular MySQL database backups
2. **Log Monitoring:** Check error logs weekly
3. **Security Updates:** Update PHP and MySQL regularly
4. **Performance Monitoring:** Monitor page load times
5. **User Feedback:** Collect and implement user suggestions

### Backup Procedures
```sql
-- Database backup command
mysqldump -u root -p mess_master > backup_YYYY-MM-DD.sql

-- Database restore command
mysql -u root -p mess_master < backup_YYYY-MM-DD.sql
```

### Security Best Practices
- Change default admin password after installation
- Use HTTPS in production environment
- Regular security updates for server software
- Monitor access logs for suspicious activity
- Implement proper backup and disaster recovery

---

## Conclusion

The Mess Master system provides a comprehensive solution for mess management operations with modern web technologies, user-friendly interfaces, and robust functionality. This documentation serves as a complete guide for installation, configuration, usage, and maintenance of the system.

For additional support or feature requests, please refer to the GitHub repository or contact the development team.

---

**Document Version:** 2.0  
**Last Updated:** October 11, 2025  
**Next Review:** November 2025

---

