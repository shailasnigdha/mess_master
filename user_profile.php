<?php
session_start();
require 'config.php';

// Auth: Only logged-in Users can access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'User') {
    header('Location: login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];

// Fetch user profile data
$stmt = $conn->prepare("SELECT username, name, hall_id, room_no, type, email, phone FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
$u = $res->fetch_assoc();
$stmt->close();

if (!$u) {
    // If somehow not found, force logout
    header('Location: logout.php');
    exit();
}

function safe($v) {
    return htmlspecialchars($v ?? '—', ENT_QUOTES, 'UTF-8');
}

$username = $_SESSION['name'] ?? $_SESSION['username'] ?? $u['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Mess Master</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-page-container { max-width: 1100px; margin: 0 auto; padding: 40px 20px 80px; }
        .profile-header { color: white; margin-bottom: 30px; }
        .profile-header h1 { font-size: 2.2rem; margin-bottom: 8px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
        .profile-meta { color: rgba(255,255,255,0.9); }

        .profile-card { background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 24px; }
        .profile-row { display: grid; grid-template-columns: 160px 1fr; gap: 24px; align-items: center; }
        .avatar { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 42px; font-weight: 800; box-shadow: 0 10px 25px rgba(102,126,234,0.35); }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap: 16px; margin-top: 10px; }
        .info-item { background: #f8f9ff; padding: 14px 16px; border-radius: 10px; border: 1px solid rgba(102,126,234,0.15); }
        .info-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; display: block; }
        .info-value { color: #2d2c3c; font-weight: 600; }

        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #101631; text-decoration: none; font-weight: 600; margin-bottom: 20px; }

        @media (max-width: 768px) {
            .profile-row { grid-template-columns: 1fr; }
            .avatar { margin: 0 auto; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h2 class="nav-title">Mess Master</h2>
            <ul class="nav-links">
                <li><a href="user_dashboard.php">Dashboard</a></li>
                <li><a href="user_meals.php">My Meals</a></li>
                <li><a href="user_dues.php">Dues</a></li>
                <li><a href="user_notices.php">Notices</a></li>
                <li><a href="user_profile.php">Profile</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="profile-page-container">
        <a href="user_dashboard.php" class="back-link">⬅ Back to dashboard</a>

        <div class="profile-header">
            <h1>My Profile</h1>
            <p class="profile-meta">Welcome, <?php echo safe($username); ?>. Review your account details below.</p>
        </div>

        <div class="profile-card">
            <div class="profile-row">
                <div class="avatar" aria-hidden="true">
                    <?php echo strtoupper(substr(trim(($u['name'] ?? $u['username'] ?? 'U')), 0, 1)); ?>
                </div>
                <div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Full Name</span>
                            <div class="info-value"><?php echo safe($u['name']); ?></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Username</span>
                            <div class="info-value"><?php echo safe($u['username']); ?></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">User Type</span>
                            <div class="info-value"><?php echo safe($u['type']); ?></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Hall ID</span>
                            <div class="info-value"><?php echo safe($u['hall_id']); ?></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Room No</span>
                            <div class="info-value"><?php echo safe($u['room_no']); ?></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <div class="info-value"><?php echo safe($u['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <div class="info-value"><?php echo safe($u['phone']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
