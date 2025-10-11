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

// Handle Edit User
if (isset($_POST['edit_user'])) {
    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $name = trim($_POST['name']);
    $hall_id = trim($_POST['hall_id']);
    $room_no = trim($_POST['room_no']);
    $type = $_POST['type'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Check if username already exists for other users
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $check_stmt->bind_param("si", $username, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Username already exists. Please choose a different username.";
    } else {
        // Update user with or without password
        if (!empty($password)) {
            $stmt = $conn->prepare("UPDATE users SET username=?, password_hash=?, name=?, hall_id=?, room_no=?, type=?, email=?, phone=? WHERE id=?");
            $stmt->bind_param("ssssssssi", $username, $password, $name, $hall_id, $room_no, $type, $email, $phone, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, name=?, hall_id=?, room_no=?, type=?, email=?, phone=? WHERE id=?");
            $stmt->bind_param("sssssssi", $username, $name, $hall_id, $room_no, $type, $email, $phone, $user_id);
        }
        
        if ($stmt->execute()) {
            $success = "User '$name' has been successfully updated.";
        } else {
            $error = "Error updating user. Please try again.";
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Handle Add/Update Dues
if (isset($_POST['add_due'])) {
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $due_date = $_POST['due_date'];
    $status = $_POST['status'] ?? 'Pending';
    
    $stmt = $conn->prepare("INSERT INTO dues (user_id, amount, due_date, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $user_id, $amount, $due_date, $status);
    
    if ($stmt->execute()) {
        $success = "Due added successfully!";
    } else {
        $error = "Error adding due. Please try again.";
    }
    $stmt->close();
}

// Handle Update Due Status
if (isset($_POST['update_due_status'])) {
    $due_id = intval($_POST['due_id']);
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE dues SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $due_id);
    
    if ($stmt->execute()) {
        $success = "Due status updated successfully!";
    } else {
        $error = "Error updating due status.";
    }
    $stmt->close();
}

// Handle Mark Month as Paid
if (isset($_POST['mark_month_paid'])) {
    $user_id = intval($_POST['user_id']);
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    $amount = floatval($_POST['amount']);
    
    // Update monthly_meal_summary to mark as fully paid
    $stmt = $conn->prepare("UPDATE monthly_meal_summary SET total_amount_paid = total_amount_due WHERE user_id = ? AND month = ? AND year = ?");
    $stmt->bind_param("iii", $user_id, $month, $year);
    
    if ($stmt->execute()) {
        $success = "Month marked as paid successfully!";
    } else {
        $error = "Error marking month as paid.";
    }
    $stmt->close();
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
        
        .manage-dues-btn {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            margin-right: 5px;
            transition: background 0.3s ease;
        }
        
        .manage-dues-btn:hover {
            background: #5a67d8;
        }
        
        .edit-user-btn {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            margin-right: 5px;
            transition: background 0.3s ease;
        }
        
        .edit-user-btn:hover {
            background: #218838;
        }
        
        .dues-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .dues-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .dues-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .dues-history {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .dues-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .dues-table th,
        .dues-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        
        .dues-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
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
                                <li><a href="admin_feedbacks.php" class="nav-link">Feedback Inbox</a></li>
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
                            <th>Actions</th>
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
                                <button class="edit-user-btn" onclick="openEditModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', '<?= htmlspecialchars($u['name']) ?>', '<?= htmlspecialchars($u['hall_id']) ?>', '<?= htmlspecialchars($u['room_no']) ?>', '<?= htmlspecialchars($u['type']) ?>', '<?= htmlspecialchars($u['email']) ?>', '<?= htmlspecialchars($u['phone']) ?>')">
                                    ✏️ Edit
                                </button>
                                <button class="manage-dues-btn" onclick="openDuesModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name']) ?>', '<?= htmlspecialchars($u['username']) ?>')">
                                    💰 Manage Dues
                                </button>
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

    <!-- Dues Management Modal -->
    <div id="duesModal" class="dues-modal">
        <div class="dues-modal-content">
            <span class="close" onclick="closeDuesModal()">&times;</span>
            <h2 id="modalTitle" style="color: #667eea; margin-bottom: 20px;">💰 Manage Dues</h2>
            
            <div class="dues-grid">
                <!-- Add New Due Form -->
                <div>
                    <h3 style="color: #333; margin-bottom: 15px;">➕ Add New Due</h3>
                    <form method="post" id="addDueForm">
                        <input type="hidden" id="modalUserId" name="user_id" value="">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount">Amount (Tk):</label>
                                <input type="number" id="amount" name="amount" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                            <div class="form-group">
                                <label for="due_date">Due Date:</label>
                                <input type="date" id="due_date" name="due_date" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status">
                                <option value="Pending">Pending</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="add_due" class="login-btn" style="width: 100%; margin-top: 10px;">
                            Add Due
                        </button>
                    </form>
                </div>
                
                <!-- Existing Dues -->
                <div>
                    <h3 style="color: #333; margin-bottom: 15px;">📋 Existing Dues</h3>
                    <div id="userDuesContainer" class="dues-history">
                        <p style="color: #666; text-align: center; padding: 20px;">Select a user to view their dues</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="dues-modal">
        <div class="dues-modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2 style="color: #667eea; margin-bottom: 20px;">✏️ Edit User</h2>
            
            <form method="post" id="editUserForm">
                <input type="hidden" id="editUserId" name="user_id" value="">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="editUsername">Username:</label>
                        <input type="text" id="editUsername" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editPassword">Password:</label>
                        <input type="password" id="editPassword" name="password" placeholder="Leave blank to keep current password">
                        <small style="color: #666; font-size: 0.8rem;">Leave empty to keep current password</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editName">Full Name:</label>
                        <input type="text" id="editName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editType">User Type:</label>
                        <select id="editType" name="type" required>
                            <option value="Residential">Residential</option>
                            <option value="Non-Residential">Non-Residential</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editHallId">Hall ID:</label>
                        <input type="text" id="editHallId" name="hall_id">
                    </div>
                    
                    <div class="form-group">
                        <label for="editRoomNo">Room Number:</label>
                        <input type="text" id="editRoomNo" name="room_no">
                    </div>
                    
                    <div class="form-group">
                        <label for="editEmail">Email:</label>
                        <input type="email" id="editEmail" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="editPhone">Phone:</label>
                        <input type="text" id="editPhone" name="phone">
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="edit_user" class="login-btn" style="flex: 1;">
                        Update User
                    </button>
                    <button type="button" onclick="closeEditModal()" class="login-btn" style="flex: 1; background: #6c757d;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set default due date to next month
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
            const dateStr = nextMonth.toISOString().split('T')[0];
            document.getElementById('due_date').value = dateStr;
        });

        function openDuesModal(userId, userName, username) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalTitle').textContent = `💰 Manage Dues - ${userName} (${username})`;
            document.getElementById('duesModal').style.display = 'block';
            
            // Load user's existing dues
            loadUserDues(userId);
        }

        function closeDuesModal() {
            document.getElementById('duesModal').style.display = 'none';
            document.getElementById('addDueForm').reset();
            
            // Reset due date to next month
            const today = new Date();
            const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
            const dateStr = nextMonth.toISOString().split('T')[0];
            document.getElementById('due_date').value = dateStr;
        }

        function loadUserDues(userId) {
            const container = document.getElementById('userDuesContainer');
            container.innerHTML = '<p style="text-align: center; color: #666;">Loading dues...</p>';
            
            // Make AJAX request to fetch user dues
            fetch(`admin_get_user_dues.php?user_id=${userId}`)
                .then(response => response.text())
                .then(data => {
                    container.innerHTML = data;
                })
                .catch(error => {
                    container.innerHTML = '<p style="color: #e74c3c; text-align: center;">Error loading dues</p>';
                });
        }

        function updateDueStatus(dueId, newStatus) {
            if (confirm(`Are you sure you want to mark this due as ${newStatus.toLowerCase()}?`)) {
                const formData = new FormData();
                formData.append('update_due_status', '1');
                formData.append('due_id', dueId);
                formData.append('new_status', newStatus);
                
                fetch('admin_manage_users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    // Reload the dues for the current user
                    const userId = document.getElementById('modalUserId').value;
                    loadUserDues(userId);
                })
                .catch(error => {
                    alert('Error updating due status');
                });
            }
        }

        function markMonthPaid(userId, month, year, amount) {
            const monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
            const monthName = monthNames[month] + ' ' + year;
            
            if (confirm(`Mark ${monthName} meal dues (Tk ${amount}) as paid?`)) {
                const formData = new FormData();
                formData.append('mark_month_paid', '1');
                formData.append('user_id', userId);
                formData.append('month', month);
                formData.append('year', year);
                formData.append('amount', amount);
                
                fetch('admin_manage_users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    // Reload the dues for the current user
                    loadUserDues(userId);
                })
                .catch(error => {
                    alert('Error marking month as paid');
                });
            }
        }

        // Edit User Modal Functions
        function openEditModal(userId, username, name, hallId, roomNo, type, email, phone) {
            document.getElementById('editUserId').value = userId;
            document.getElementById('editUsername').value = username;
            document.getElementById('editName').value = name;
            document.getElementById('editHallId').value = hallId;
            document.getElementById('editRoomNo').value = roomNo;
            document.getElementById('editType').value = type;
            document.getElementById('editEmail').value = email;
            document.getElementById('editPhone').value = phone;
            document.getElementById('editPassword').value = ''; // Always start empty
            
            document.getElementById('editUserModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
            document.getElementById('editUserForm').reset();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const duesModal = document.getElementById('duesModal');
            const editModal = document.getElementById('editUserModal');
            
            if (event.target == duesModal) {
                closeDuesModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
