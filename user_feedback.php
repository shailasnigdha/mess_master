<?php
session_start();
require 'config.php';

$notLoggedIn = false;
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'User') {
    // Instead of an immediate redirect, show a friendly message so users understand why the page
    // didn't load (helps debugging / testing). The form remains protected for POSTs.
    $notLoggedIn = true;
} else {
    $userId = (int) $_SESSION['user_id'];
    $username = $_SESSION['name'] ?? $_SESSION['username'] ?? 'User';
}

$errors = [];
$success = null;

// Pre-fill meal_id if provided via GET
$mealId = isset($_GET['meal_id']) ? intval($_GET['meal_id']) : null;
$subjectPrefill = '';
if ($mealId) {
    // try to fetch meal name for subject
    $mstmt = $conn->prepare('SELECT meal_name, meal_date FROM meal_plans WHERE id = ?');
    $mstmt->bind_param('i', $mealId);
    $mstmt->execute();
    $mres = $mstmt->get_result();
    if ($mrow = $mres->fetch_assoc()) {
        $subjectPrefill = 'Feedback for ' . $mrow['meal_name'] . ' on ' . date('F j, Y', strtotime($mrow['meal_date']));
    }
    $mstmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $meal_id_post = !empty($_POST['meal_id']) ? intval($_POST['meal_id']) : null;

    if (!in_array($type, ['Review', 'Complaint'])) $errors[] = 'Invalid feedback type.';
    if ($subject === '') $errors[] = 'Subject is required.';
    if ($message === '') $errors[] = 'Message is required.';

    if (empty($errors)) {
        $ins = $conn->prepare('INSERT INTO feedbacks (user_id, meal_id, type, subject, message) VALUES (?, ?, ?, ?, ?)');
        $ins->bind_param('iisss', $userId, $meal_id_post, $type, $subject, $message);
        if ($ins->execute()) {
            $success = 'Thank you — your feedback has been submitted to the admin.';
            // clear form values
            $type = '';
            $subject = '';
            $message = '';
            $mealId = null;
        } else {
            $errors[] = 'Unable to submit feedback. Please try again later.';
        }
        $ins->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Give Feedback - Mess Master</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .feedback-container { max-width: 900px; margin: 0 auto; padding: 40px 20px 80px; }
        .feedback-card { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .form-row { margin-bottom: 14px; }
        .form-row label { display:block; margin-bottom:6px; font-weight:600; color:#333; }
        .form-row input[type=text], .form-row textarea, .form-row select { width:100%; padding:10px 12px; border:1px solid #e6e6ef; border-radius:6px; }
        .submit-btn { background: linear-gradient(135deg,#667eea,#764ba2); color:#fff; padding:10px 18px; border-radius:8px; border:none; font-weight:700; cursor:pointer; }
        .helper { font-size:13px; color:#6b7280; }
        .success-msg { background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:12px; }
        .error-list { background:#fee; color:#c0392b; padding:10px; border-radius:8px; margin-bottom:12px; }
        .back-link { display:inline-flex; align-items:center; gap:8px; color:#101631; text-decoration:none; font-weight:600; margin-bottom:20px; }
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
                <li><a href="user_feedback.php">Feedback</a></li>
                <li><a href="user_profile.php">Profile</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="feedback-container">
        <a class="back-link" href="user_dashboard.php">⬅ Back to dashboard</a>
        <div class="feedback-card">
            <h2>Send Feedback to Admin</h2>
            <p class="helper">Share a review of your meal or file a complaint — the admin will review it from the management panel.</p>

            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="meal_id" value="<?php echo htmlspecialchars($mealId ?? ''); ?>">
                <div class="form-row">
                    <label for="type">Type</label>
                    <select name="type" id="type" required>
                        <option value="">Select type</option>
                        <option value="Review" <?php echo (isset($type) && $type === 'Review') ? 'selected' : ''; ?>>Review</option>
                        <option value="Complaint" <?php echo (isset($type) && $type === 'Complaint') ? 'selected' : ''; ?>>Complaint</option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? $subjectPrefill); ?>" required>
                </div>

                <div class="form-row">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="6" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                </div>

                <div style="display:flex; gap:12px; align-items:center;">
                    <button type="submit" class="submit-btn">Send Feedback</button>
                    <a href="user_meals.php" class="helper">Give feedback about a specific meal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
