<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Fetch statistics
$totalUsers = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$resUsers = $conn->query("SELECT COUNT(*) AS c FROM users WHERE type='Residential'")->fetch_assoc()['c'];
$nonResUsers = $conn->query("SELECT COUNT(*) AS c FROM users WHERE type='Non-Residential'")->fetch_assoc()['c'];
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 5");

$adminName = $_SESSION['name'] ?? $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mess Master</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2 class="nav-title">Mess Master - Admin</h2>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_manage_users.php">Manage Users</a></li>
                <li><a href="admin_notices.php">Notices</a></li>
                <li><a href="admin_meals.php">Meals</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1>Welcome, <?php echo htmlspecialchars($adminName); ?>!</h1>
            <p>Manage your mess system from the admin dashboard</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon">👥</div>
                <h3>Total Users</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #667eea; margin: 15px 0;"><?= $totalUsers ?></p>
                <a href="admin_manage_users.php" class="view-details-btn">Manage Users</a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">🏠</div>
                <h3>Residential Users</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #667eea; margin: 15px 0;"><?= $resUsers ?></p>
                <a href="admin_manage_users.php" class="view-details-btn">View Details</a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">🚶</div>
                <h3>Non-Residential Users</h3>
                <p style="font-size: 2rem; font-weight: bold; color: #667eea; margin: 15px 0;"><?= $nonResUsers ?></p>
                <a href="admin_manage_users.php" class="view-details-btn">View Details</a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">📢</div>
                <h3>Notices</h3>
                <p>Manage system announcements and important notices</p>
                <a href="admin_notices.php" class="view-details-btn">Manage Notices</a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">🍽️</div>
                <h3>Meal Management</h3>
                <p>Plan weekly meals, set prices, and track user selections</p>
                <a href="admin_meals.php" class="view-details-btn">Manage Meals</a>
            </div>
        </div>

        <?php if ($notices->num_rows > 0): ?>
        <div class="details-section">
            <h2>📢 Recent Notices</h2>
            <div class="details-content">
                <?php while($n = $notices->fetch_assoc()): ?>
                <div class="notice-item">
                    <div class="notice-title"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="notice-date"><?= date('F j, Y g:i A', strtotime($n['created_at'])) ?></div>
                    <div class="notice-content"><?= htmlspecialchars($n['description']) ?></div>
                </div>
                <?php endwhile; ?>
            </div>
            <div style="text-align: center;">
                <a href="admin_notices.php" class="view-details-btn">View All Notices</a>
            </div>
        </div>
        <?php else: ?>
        <div class="details-section">
            <h2>📢 Recent Notices</h2>
            <div style="text-align: center; padding: 40px; color: #666;">
                <p>No notices found. <a href="admin_notices.php" style="color: #667eea;">Create your first notice</a></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
