<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Add Notice
if (isset($_POST['add_notice'])) {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);

    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO notices (title, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $desc);
        
        if ($stmt->execute()) {
            $success = "Notice '$title' has been successfully added.";
        } else {
            $error = "Error adding notice. Please try again.";
        }
        $stmt->close();
    } else {
        $error = "Notice title is required.";
    }
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notices - Mess Master</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .manage-notices-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .add-notice-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .notices-list-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .notice-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .notice-card h4 {
            color: #333;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        
        .notice-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .notice-description {
            color: #555;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .notice-actions {
            text-align: right;
        }
        
        .delete-notice-btn {
            background: #e74c3c;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        .delete-notice-btn:hover {
            background: #c0392b;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
    </style>
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

    <div class="manage-notices-container">
        <div class="welcome-section" style="margin-bottom: 40px;">
            <h1 style="color: white;">📢 Manage Notices</h1>
            <p style="color: rgba(255,255,255,0.9);">Create and manage system announcements and important notices</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message" style="margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="add-notice-form">
            <h2 style="color: #667eea; margin-bottom: 25px;">➕ Add New Notice</h2>
            <form method="post">
                <div class="form-group">
                    <label for="title">Notice Title:</label>
                    <input type="text" id="title" name="title" placeholder="Enter notice title" required 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Notice Description:</label>
                    <textarea id="description" name="description" placeholder="Enter detailed notice description..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="add_notice" class="login-btn" style="width: auto; padding: 12px 30px;">
                    📢 Add Notice
                </button>
            </form>
        </div>

        <div class="notices-list-container">
            <h2 style="color: #667eea; margin-bottom: 25px;">📋 All Notices (<?php echo $notices->num_rows; ?> total)</h2>
            
            <?php if ($notices->num_rows > 0): ?>
                <?php while($n = $notices->fetch_assoc()): ?>
                <div class="notice-card">
                    <h4><?= htmlspecialchars($n['title']) ?></h4>
                    <div class="notice-meta">
                        📅 Posted on <?= date('F j, Y \a\t g:i A', strtotime($n['created_at'])) ?>
                    </div>
                    <?php if (!empty($n['description'])): ?>
                    <div class="notice-description">
                        <?= nl2br(htmlspecialchars($n['description'])) ?>
                    </div>
                    <?php endif; ?>
                    <div class="notice-actions">
                        <a href="admin_notices.php?delete=<?= $n['id'] ?>" 
                           class="delete-notice-btn"
                           onclick="return confirm('Are you sure you want to delete the notice \'<?= htmlspecialchars($n['title']) ?>\'? This action cannot be undone.')">
                            🗑️ Delete
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p style="font-size: 1.1rem;">📋 No notices found</p>
                    <p>Create your first notice using the form above to keep users informed about important updates.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
