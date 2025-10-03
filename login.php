<?php
session_start();

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
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
    } elseif ($role === 'User' && $username !== 'user') {
        $error = "Invalid username for User role. Username must be 'user'";
    } elseif ($role === 'User' && $password !== 'user123') {
        $error = "Invalid password for User";
    } elseif ($role === 'Admin' && $username !== 'admin') {
        $error = "Invalid username for Admin role. Username must be 'admin'";
    } elseif ($role === 'Admin' && $password !== 'admin123') {
        $error = "Invalid password for Admin";
    } else {
        // Store role as username for display
        $_SESSION['username'] = $role;
        $_SESSION['role'] = $role;
        
        // Redirect based on role
        if ($role === 'Admin') {
            header('Location: admin_dashboard.php');
        } else {
            header('Location: user_dashboard.php');
        }
        exit();
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
                        <option value="User" <?php echo (($_POST['role'] ?? '') === 'User') ? 'selected' : ''; ?>>User</option>
                        <option value="Admin" <?php echo (($_POST['role'] ?? '') === 'Admin') ? 'selected' : ''; ?>>Admin</option>
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