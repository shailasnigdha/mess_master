<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Handle Add User
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password']; // for now plaintext, later hash
    $name = $_POST['name'];
    $hall_id = $_POST['hall_id'];
    $room_no = $_POST['room_no'];
    $type = $_POST['type'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("INSERT INTO users (username,password_hash,name,hall_id,room_no,type,email,phone) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssss", $username, $password, $name, $hall_id, $room_no, $type, $email, $phone);
    $stmt->execute();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: admin_manage_users.php");
    exit();
}

// Fetch users
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
</head>
<body>
<h1>Manage Users</h1>
<a href="admin_dashboard.php">⬅ Back to Dashboard</a>

<h2>Add New User</h2>
<form method="post">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="text" name="hall_id" placeholder="Hall ID">
    <input type="text" name="room_no" placeholder="Room No">
    <select name="type">
        <option value="Residential">Residential</option>
        <option value="Non-Residential">Non-Residential</option>
    </select>
    <input type="email" name="email" placeholder="Email">
    <input type="text" name="phone" placeholder="Phone">
    <button type="submit" name="add_user">Add User</button>
</form>

<h2>All Users</h2>
<table border="1" cellpadding="5">
<tr>
    <th>ID</th><th>Username</th><th>Name</th><th>Type</th><th>Email</th><th>Phone</th><th>Action</th>
</tr>
<?php while($u = $users->fetch_assoc()): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= $u['username'] ?></td>
    <td><?= $u['name'] ?></td>
    <td><?= $u['type'] ?></td>
    <td><?= $u['email'] ?></td>
    <td><?= $u['phone'] ?></td>
    <td>
        <a href="admin_manage_users.php?delete=<?= $u['id'] ?>" onclick="return confirm('Delete user?')">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
