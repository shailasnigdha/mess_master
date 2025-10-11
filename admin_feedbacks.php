<?php
session_start();
require 'config.php';

// Only admins
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: login.php');
    exit();
}

// Handle actions: mark_read, mark_unread, delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);

    if ($action === 'mark_read') {
        $stmt = $conn->prepare("UPDATE feedbacks SET status = 'Read' WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'mark_unread') {
        $stmt = $conn->prepare("UPDATE feedbacks SET status = 'Unread' WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM feedbacks WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: admin_feedbacks.php');
    exit();
}

// Fetch feedbacks (latest first)
$sql = "SELECT f.*, u.username, u.name AS user_name, mp.meal_name
        FROM feedbacks f
        LEFT JOIN users u ON f.user_id = u.id
        LEFT JOIN meal_plans mp ON f.meal_id = mp.id
        ORDER BY f.created_at DESC";
$res = $conn->query($sql);
$feedbacks = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Inbox - Feedbacks</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container { max-width: 1100px; margin: 0 auto; padding: 40px 20px; }
        .inbox-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .feedback-list { display: flex; flex-direction: column; gap: 12px; margin-top: 16px; }
        .feedback-item { padding: 14px; border-radius: 10px; border: 1px solid #eef2ff; background: #fbfdff; }
        .feedback-item.unread { border-left: 4px solid #ffcc00; }
        .feedback-item.read { opacity: 0.85; }
        .feedback-row { display:flex; justify-content:space-between; align-items:center; gap:12px; }
        .feedback-meta { color: #6b7280; font-size: 0.9rem; }
        .feedback-subject { font-weight:700; color: #1f2937; }
        .actions { display:flex; gap:8px; }
        .btn { padding:6px 10px; border-radius:8px; border:none; cursor:pointer; font-weight:600; }
        .btn.read { background:#10b981; color:white; }
        .btn.unread { background:#f59e0b; color:white; }
        .btn.delete { background:#ef4444; color:white; }
        .message { margin-top:10px; background:#f8fafc; padding:12px; border-radius:8px; border:1px solid #eef2ff; }
        a.nav-link { text-decoration:none; color:#667eea; font-weight:700; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2 class="nav-title">Mess Master - Admin</h2>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_manage_users.php">Manage Users</a></li>
                <li><a href="admin_meals.php">Meals</a></li>
                <li><a href="admin_notices.php">Notices</a></li>
                <li><a href="admin_feedbacks.php" class="nav-link">Feedback Inbox</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="admin-container">
        <div class="inbox-card">
            <h1>Feedback Inbox</h1>
            <p class="feedback-meta">View user reviews and complaints. Mark items as read when reviewed.</p>

            <?php if (empty($feedbacks)): ?>
                <div style="padding:20px; margin-top:12px; background:#fff8f0; border-radius:8px;">No feedbacks yet.</div>
            <?php else: ?>
                <div class="feedback-list">
                    <?php foreach ($feedbacks as $f): ?>
                        <div class="feedback-item <?php echo ($f['status'] === 'Unread') ? 'unread' : 'read'; ?>" id="fb-<?php echo $f['id']; ?>">
                            <div class="feedback-row">
                                <div>
                                    <div class="feedback-subject"><?php echo htmlspecialchars($f['subject']); ?></div>
                                    <div class="feedback-meta">From: <?php echo htmlspecialchars($f['user_name'] ?: $f['username'] ?? 'Unknown'); ?> &middot; Type: <?php echo htmlspecialchars($f['type']); ?> <?php if (!empty($f['meal_name'])) echo '&middot; Meal: ' . htmlspecialchars($f['meal_name']); ?></div>
                                </div>

                                <div class="actions">
                                    <?php if ($f['status'] === 'Unread'): ?>
                                        <a class="btn read" href="admin_feedbacks.php?action=mark_read&id=<?php echo $f['id']; ?>">Mark read</a>
                                    <?php else: ?>
                                        <a class="btn unread" href="admin_feedbacks.php?action=mark_unread&id=<?php echo $f['id']; ?>">Mark unread</a>
                                    <?php endif; ?>

                                    <a class="btn delete" href="admin_feedbacks.php?action=delete&id=<?php echo $f['id']; ?>" onclick="return confirm('Delete this feedback permanently?');">Delete</a>
                                </div>
                            </div>

                            <div class="message">
                                <?php echo nl2br(htmlspecialchars($f['message'])); ?>
                                <div style="font-size:12px; color:#6b7280; margin-top:8px;">Posted on: <?php echo htmlspecialchars(date('F j, Y, g:i A', strtotime($f['created_at']))); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>