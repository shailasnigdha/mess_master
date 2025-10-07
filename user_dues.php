<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'User') {
    header('Location: login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$username = $_SESSION['name'] ?? $_SESSION['username'] ?? 'User';

// Fetch user type to determine dues mode (Residential => monthly, Non-Residential => yearly)
$userStmt = $conn->prepare("SELECT type FROM users WHERE id = ?");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userTypeRes = $userStmt->get_result();
$userTypeRow = $userTypeRes->fetch_assoc();
$userType = $userTypeRow['type'] ?? 'Residential';
$userStmt->close();

$isResidential = ($userType === 'Residential');

// Dues table already exists. We'll use it as the canonical dues info per user.
// Additionally, support optional payment history if a `payments` table exists.

// Fetch latest dues for user depending on type
if ($isResidential) {
    // For monthly dues, show current and recent months
    $duesSql = "SELECT id, amount, due_date, status FROM dues WHERE user_id = ? ORDER BY due_date DESC LIMIT 12";
} else {
    // For yearly dues, show recent years
    $duesSql = "SELECT id, amount, due_date, status FROM dues WHERE user_id = ? ORDER BY due_date DESC LIMIT 5";
}

$duesStmt = $conn->prepare($duesSql);
$duesStmt->bind_param('i', $userId);
$duesStmt->execute();
$duesRes = $duesStmt->get_result();
$dues = $duesRes->fetch_all(MYSQLI_ASSOC);
$duesStmt->close();

// Optional payment history: read if payments table exists
$paymentHistory = [];
$paymentsTableExists = false;
$checkRes = $conn->query("SHOW TABLES LIKE 'payments'");
if ($checkRes && $checkRes->num_rows > 0) {
    $paymentsTableExists = true;
    $phStmt = $conn->prepare("SELECT id, amount, paid_on, reference, notes FROM payments WHERE user_id = ? ORDER BY paid_on DESC LIMIT 20");
    $phStmt->bind_param('i', $userId);
    $phStmt->execute();
    $paymentHistory = $phStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $phStmt->close();
}

function formatMoney($v) {
    if ($v === null || $v === '') return '';
    return '₹' . number_format((float)$v, 2);
}

function formatDateNice($dateStr) {
    if (empty($dateStr)) return '—';
    $ts = strtotime($dateStr);
    return $ts ? date('F j, Y', $ts) : htmlspecialchars($dateStr);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dues - Mess Master</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dues-page-container { max-width: 1100px; margin: 0 auto; padding: 40px 20px 80px; }
        .dues-header { color: white; margin-bottom: 35px; }
        .dues-header h1 { font-size: 2.2rem; margin-bottom: 8px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
        .dues-meta { color: rgba(255,255,255,0.9); }

        .dues-card { background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 24px; }
        .dues-card h2 { color: #667eea; margin-bottom: 14px; font-size: 1.3rem; }

        .dues-table { width: 100%; border-collapse: collapse; }
        .dues-table th, .dues-table td { padding: 12px 10px; border-bottom: 1px solid #f0f0f0; text-align: left; }
        .dues-table th { font-size: 0.9rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.03em; }
        .dues-status { display: inline-block; padding: 4px 10px; border-radius: 999px; font-weight: 600; font-size: 12px; }
        .dues-status.Paid { background: #d4edda; color: #155724; }
        .dues-status.Pending { background: #fff3cd; color: #856404; }

        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #101631; text-decoration: none; font-weight: 600; margin-bottom: 20px; }
        .empty-blurb { text-align: center; color: #5f5d7a; padding: 24px; border: 1px dashed #cbd5e1; border-radius: 12px; background: rgba(255,255,255,0.95); }
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

    <div class="dues-page-container">
        <a href="user_dashboard.php" class="back-link">⬅ Back to dashboard</a>
        <div class="dues-header">
            <h1>My Dues</h1>
            <p class="dues-meta">Hi <?php echo htmlspecialchars($username); ?> — You are registered as <strong><?php echo htmlspecialchars($userType); ?></strong>. Below are your <?php echo $isResidential ? 'monthly' : 'yearly'; ?> dues.</p>
        </div>

        <div class="dues-card">
            <h2><?php echo $isResidential ? 'Monthly Dues' : 'Yearly Dues'; ?></h2>
            <?php if (empty($dues)): ?>
                <div class="empty-blurb">No dues have been posted for your account yet. Please check back later.</div>
            <?php else: ?>
                <table class="dues-table" aria-label="Dues list">
                    <thead>
                        <tr>
                            <th><?php echo $isResidential ? 'Month' : 'Year'; ?></th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dues as $row): ?>
                            <?php
                                $dueDate = $row['due_date'] ?? '';
                                $periodLabel = '—';
                                if (!empty($dueDate)) {
                                    $ts = strtotime($dueDate);
                                    if ($ts) {
                                        $periodLabel = $isResidential ? date('F Y', $ts) : date('Y', $ts);
                                    }
                                }
                                $status = $row['status'] ?? 'Pending';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($periodLabel); ?></td>
                                <td><?php echo htmlspecialchars(formatDateNice($dueDate)); ?></td>
                                <td><?php echo htmlspecialchars(formatMoney($row['amount'])); ?></td>
                                <td><span class="dues-status <?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($paymentsTableExists): ?>
            <div class="dues-card">
                <h2>Payment History</h2>
                <?php if (empty($paymentHistory)): ?>
                    <div class="empty-blurb">No payments recorded yet.</div>
                <?php else: ?>
                    <table class="dues-table" aria-label="Payment history">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentHistory as $p): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(formatDateNice($p['paid_on'])); ?></td>
                                    <td><?php echo htmlspecialchars(formatMoney($p['amount'])); ?></td>
                                    <td><?php echo htmlspecialchars($p['reference'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($p['notes'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
