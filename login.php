<?php
session_start();
require_once 'config.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: user_dashboard.php');
    }
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? '';
    
    // Check if all fields are filled
    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all fields";
    } else {
        if ($role === 'Admin') {
            // Check admin credentials
            $stmt = $conn->prepare("SELECT id, username, password_hash, name FROM admins WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                // For now, using plain text password. In production, use password_verify()
                if ($password === $admin['password_hash']) {
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['name'] = $admin['name'];
                    $_SESSION['role'] = 'Admin';
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    $error = "Invalid admin password";
                }
            } else {
                $error = "Admin not found";
            }
            $stmt->close();
        } elseif ($role === 'User') {
            // Check user credentials
            $stmt = $conn->prepare("SELECT id, username, password_hash, name FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // For now, using plain text password. In production, use password_verify()
                if ($password === $user['password_hash']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = 'User';
                    header('Location: user_dashboard.php');
                    exit();
                } else {
                    $error = "Invalid user password";
                }
            } else {
                $error = "User not found. Please contact admin to create your account.";
            }
            $stmt->close();
        } else {
            $error = "Invalid role selected";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Master Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h1>Mess Master Login</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" placeholder="Enter password">
                </div>
                
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role">
                        <option value="">Select Role</option>
                        <option value="User" <?php echo (($_POST['role'] ?? 'User') === 'User') ? 'selected' : ''; ?>>User</option>
                        <option value="Admin" <?php echo (($_POST['role'] ?? 'User') === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const role = document.getElementById('role').value;
            
            if (!username || !password || !role) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
        });
    </script>
</body>
</html>