<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle Add User
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']); // for now plaintext, later hash
    $name = trim($_POST['name']);
    $hall_id = trim($_POST['hall_id']);
    $room_no = trim($_POST['room_no']);
    $type = $_POST['type'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Check if username already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Username already exists. Please choose a different username.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username,password_hash,name,hall_id,room_no,type,email,phone) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss", $username, $password, $name, $hall_id, $room_no, $type, $email, $phone);
        
        if ($stmt->execute()) {
            $success = "User '$name' has been successfully added to the system.";
        } else {
            $error = "Error adding user. Please try again.";
        }
        $stmt->close();
    }
    $check_stmt->close();
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Mess Master</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .manage-users-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .add-user-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .users-table-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        .delete-btn:hover {
            background: #c0392b;
        }
        
        .back-btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 30px;
            transition: background 0.3s ease;
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
        
        .user-type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .residential {
            background: #d4edda;
            color: #155724;
        }
        
        .non-residential {
            background: #f8d7da;
            color: #721c24;
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
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="manage-users-container">
        <div class="welcome-section" style="margin-bottom: 40px;">
            <h1 style="color: white;">👥 Manage Users</h1>
            <p style="color: rgba(255,255,255,0.9);">Add new users and manage existing user accounts</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message" style="margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="add-user-form">
            <h2 style="color: #667eea; margin-bottom: 25px;">➕ Add New User</h2>
            <form method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" placeholder="Enter username" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" id="name" name="name" placeholder="Enter full name" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="type">User Type:</label>
                        <select id="type" name="type" required>
                            <option value="Residential" <?php echo (($_POST['type'] ?? '') === 'Residential') ? 'selected' : ''; ?>>Residential</option>
                            <option value="Non-Residential" <?php echo (($_POST['type'] ?? '') === 'Non-Residential') ? 'selected' : ''; ?>>Non-Residential</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="hall_id">Hall ID:</label>
                        <input type="text" id="hall_id" name="hall_id" placeholder="Enter hall ID" 
                               value="<?php echo htmlspecialchars($_POST['hall_id'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="room_no">Room Number:</label>
                        <input type="text" id="room_no" name="room_no" placeholder="Enter room number" 
                               value="<?php echo htmlspecialchars($_POST['room_no'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" placeholder="Enter email address" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone:</label>
                        <input type="text" id="phone" name="phone" placeholder="Enter phone number" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit" name="add_user" class="login-btn" style="width: auto; padding: 12px 30px;">
                    Add User
                </button>
            </form>
        </div>

        <div class="users-table-container">
            <h2 style="color: #667eea; margin-bottom: 0;">👤 All Users (<?php echo $users->num_rows; ?> total)</h2>
            
            <?php if ($users->num_rows > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Type</th>
                            <th>Hall/Room</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['id']) ?></td>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td>
                                <span class="user-type-badge <?= strtolower(str_replace('-', '-', $u['type'])) ?>">
                                    <?= htmlspecialchars($u['type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($u['hall_id'] . ($u['room_no'] ? '/' . $u['room_no'] : '')) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['phone']) ?></td>
                            <td>
                                <a href="admin_manage_users.php?delete=<?= $u['id'] ?>" 
                                   class="delete-btn"
                                   onclick="return confirm('Are you sure you want to delete user \'<?= htmlspecialchars($u['name']) ?>\'? This action cannot be undone.')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p>No users found. Add your first user using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
