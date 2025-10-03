<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch statistics
$totalUsers = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$resUsers = $conn->query("SELECT COUNT(*) AS c FROM users WHERE type='Residential'")->fetch_assoc()['c'];
$nonResUsers = $conn->query("SELECT COUNT(*) AS c FROM users WHERE type='Non-Residential'")->fetch_assoc()['c'];
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Welcome Admin</h1>
    <p>Total Users: <?= $totalUsers ?></p>
    <p>Residential Users: <?= $resUsers ?></p>
    <p>Non-Residential Users: <?= $nonResUsers ?></p>

    <h2>Recent Notices</h2>
    <ul>
    <?php while($n = $notices->fetch_assoc()): ?>
        <li><b><?= $n['title'] ?></b> (<?= $n['created_at'] ?>)</li>
    <?php endwhile; ?>
    </ul>

    <a href="admin_manage_users.php">Manage Users</a> |
    <a href="admin_notices.php">Manage Notices</a> |
    <a href="admin_profile.php">Profile</a> |
    <a href="logout.php">Logout</a>
</body>
</html>
