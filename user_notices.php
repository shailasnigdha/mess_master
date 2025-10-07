<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'User') {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Guest';

$notices = [];
$error = null;

$result = $conn->query("SELECT id, title, description, created_at FROM notices ORDER BY created_at DESC");

if ($result === false) {
    $error = 'Unable to load notices at the moment. Please try again later.';
} else {
    while ($row = $result->fetch_assoc()) {
        $createdAt = !empty($row['created_at']) ? date('F j, Y g:i A', strtotime($row['created_at'])) : 'Date unavailable';
        $plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags($row['description'] ?? '')));

        $excerpt = $plainDescription;
        $limit = 220;
        if ($excerpt !== '') {
            if (function_exists('mb_strlen')) {
                if (mb_strlen($excerpt) > $limit) {
                    $short = mb_substr($excerpt, 0, $limit);
                    $lastSpace = mb_strrpos($short, ' ');
                    if ($lastSpace !== false) {
                        $short = mb_substr($short, 0, $lastSpace);
                    }
                    $excerpt = $short . '…';
                }
            } else {
                if (strlen($excerpt) > $limit) {
                    $short = substr($excerpt, 0, $limit);
                    $lastSpace = strrpos($short, ' ');
                    if ($lastSpace !== false) {
                        $short = substr($short, 0, $lastSpace);
                    }
                    $excerpt = $short . '…';
                }
            }
        }

        $notices[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'] ?? 'Untitled Notice',
            'excerpt' => $excerpt,
            'has_excerpt' => $plainDescription !== '',
            'full' => $row['description'] ?? '',
            'created_at' => $createdAt
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notices & Events - Mess Master</title>
    <link rel="stylesheet" href="style.css">
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

    <div class="notice-board-container">
        <a href="user_dashboard.php" class="back-link">⬅ Back to dashboard</a>
        <div class="notice-board-header">
            <h1>Notices & Events</h1>
            <p>Stay in the loop, <?php echo htmlspecialchars($username); ?>. Browse the latest mess announcements and hall events below.</p>
        </div>

        <?php if ($error): ?>
            <div class="empty-state">
                <h3>Something went wrong</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php elseif (empty($notices)): ?>
            <div class="empty-state">
                <h3>📋 No notices yet</h3>
                <p>Once admins post notices, you'll see them here instantly.</p>
            </div>
        <?php else: ?>
            <div class="notice-stream" aria-live="polite">
                <?php foreach ($notices as $notice): ?>
                    <article class="notice-card" data-expanded="false" id="notice-<?php echo $notice['id']; ?>">
                        <header class="notice-card-header">
                            <h2 class="notice-card-title"><?php echo htmlspecialchars($notice['title']); ?></h2>
                            <span class="notice-card-date"><?php echo htmlspecialchars($notice['created_at']); ?></span>
                        </header>
                        <p class="notice-card-summary<?php echo $notice['has_excerpt'] ? '' : ' empty'; ?>">
                            <?php echo $notice['has_excerpt'] ? htmlspecialchars($notice['excerpt']) : 'No summary was provided for this notice.'; ?>
                        </p>
                        <?php if ($notice['has_excerpt']): ?>
                            <div class="notice-card-full" id="notice-full-<?php echo $notice['id']; ?>">
                                <?php if (trim($notice['full']) !== ''): ?>
                                    <?php echo nl2br(htmlspecialchars($notice['full'])); ?>
                                <?php else: ?>
                                    <p>The admin did not include additional details for this notice.</p>
                                <?php endif; ?>
                            </div>
                            <footer class="notice-card-footer">
                                <button type="button" class="notice-card-toggle" data-target="<?php echo $notice['id']; ?>" aria-expanded="false" aria-controls="notice-full-<?php echo $notice['id']; ?>">
                                    <span class="label">Read full notice</span>
                                    <span class="icon">▼</span>
                                </button>
                            </footer>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelectorAll('.notice-card-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                const card = document.getElementById(`notice-${targetId}`);
                const fullContent = document.getElementById(`notice-full-${targetId}`);

                if (!card || !fullContent) {
                    return;
                }

                const expanded = card.getAttribute('data-expanded') === 'true';
                card.setAttribute('data-expanded', expanded ? 'false' : 'true');
                button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                button.querySelector('.label').textContent = expanded ? 'Read full notice' : 'Show less';

                if (!expanded) {
                    fullContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
