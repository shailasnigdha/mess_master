<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Update Profile
if (isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE admins SET name=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $email, $admin_id);
    $stmt->execute();
}

// Update Password
if (isset($_POST['update_pass'])) {
    $pass = $_POST['password'];
    $stmt = $conn->prepare("UPDATE admins SET password_hash=? WHERE id=?");
    $stmt->bind_param("si", $pass, $admin_id);
    $stmt->execute();
}

// Fetch Current Admin Data
$stmt = $conn->prepare("SELECT * FROM admins WHERE id=?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Profile</title>
</head>
<body>
<h1>Admin Profile</h1>
<a href="admin_dashboard.php">⬅ Back to Dashboard</a>

<h2>Update Profile</h2>
<form method="post">
    <input type="text" name="name" value="<?= $admin['name'] ?>" placeholder="Name">
    <input type="email" name="email" value="<?= $admin['email'] ?>" placeholder="Email">
    <button type="submit" name="update_profile">Update Profile</button>
</form>

<h2>Change Password</h2>
<form method="post">
    <input type="password" name="password" placeholder="New Password">
    <button type="submit" name="update_pass">Update Password</button>
</form>
</body>
</html>
