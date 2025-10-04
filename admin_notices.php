<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Add Notice
if (isset($_POST['add_notice'])) {
    $title = $_POST['title'];
    $desc = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO notices (title, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $desc);
    $stmt->execute();
}

// Delete Notice
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM notices WHERE id=$id");
    header("Location: admin_notices.php");
    exit();
}

// Fetch Notices
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Notices</title>
</head>
<body>
<h1>Manage Notices</h1>
<a href="admin_dashboard.php">⬅ Back to Dashboard</a>

<h2>Add Notice</h2>
<form method="post">
    <input type="text" name="title" placeholder="Notice Title" required>
    <textarea name="description" placeholder="Notice Description"></textarea>
    <button type="submit" name="add_notice">Add Notice</button>
</form>

<h2>All Notices</h2>
<ul>
<?php while($n = $notices->fetch_assoc()): ?>
    <li>
        <b><?= $n['title'] ?></b> (<?= $n['created_at'] ?>)<br>
        <?= $n['description'] ?><br>
        <a href="admin_notices.php?delete=<?= $n['id'] ?>" onclick="return confirm('Delete notice?')">Delete</a>
    </li>
<?php endwhile; ?>
</ul>
</body>
</html>
