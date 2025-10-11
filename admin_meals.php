<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle Add Meal Plan
if (isset($_POST['add_meal'])) {
    $meal_date = $_POST['meal_date'];
    $meal_type = $_POST['meal_type'];
    $meal_name = trim($_POST['meal_name']);
    $meal_description = trim($_POST['meal_description']);
    $meal_price = floatval($_POST['meal_price']);
    $is_vacation = isset($_POST['is_vacation']) ? 1 : 0;
    
    if (!empty($meal_date) && !empty($meal_type) && !empty($meal_name) && $meal_price > 0) {
        // Check if there's already a vacation day on this date
        $vacation_check = $conn->prepare("SELECT id FROM meal_plans WHERE meal_date = ? AND is_vacation_day = 1 LIMIT 1");
        $vacation_check->bind_param("s", $meal_date);
        $vacation_check->execute();
        $vacation_result = $vacation_check->get_result();
        
        if ($vacation_result->num_rows > 0 && !$is_vacation) {
            // There's already a vacation day on this date and user is trying to add a regular meal
            $error = "Cannot add meals on vacation days. " . date('F j, Y', strtotime($meal_date)) . " is marked as a vacation day.";
            $vacation_check->close();
        } else {
            $vacation_check->close();
            
            // If marking as vacation, prevent adding regular meals for same date
            if ($is_vacation) {
                // Remove any existing regular meals for this date
                $cleanup_stmt = $conn->prepare("DELETE FROM meal_plans WHERE meal_date = ? AND is_vacation_day = 0");
                $cleanup_stmt->bind_param("s", $meal_date);
                $cleanup_stmt->execute();
                $cleanup_stmt->close();
                
                // Add vacation day entry (only one vacation entry per date)
                $stmt = $conn->prepare("INSERT INTO meal_plans (meal_date, meal_type, meal_name, meal_description, meal_price, is_vacation_day) VALUES (?, 'VACATION', 'Vacation Day', 'No meals available - Vacation day', 0, 1) ON DUPLICATE KEY UPDATE meal_name = 'Vacation Day', meal_description = 'No meals available - Vacation day', meal_price = 0, is_vacation_day = 1");
                $stmt->bind_param("s", $meal_date);
            } else {
                // Regular meal addition
                $stmt = $conn->prepare("INSERT INTO meal_plans (meal_date, meal_type, meal_name, meal_description, meal_price, is_vacation_day) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE meal_name = VALUES(meal_name), meal_description = VALUES(meal_description), meal_price = VALUES(meal_price), is_vacation_day = VALUES(is_vacation_day)");
                $stmt->bind_param("ssssdi", $meal_date, $meal_type, $meal_name, $meal_description, $meal_price, $is_vacation);
            }
            
            if ($stmt->execute()) {
                if ($is_vacation) {
                    $success = date('F j, Y', strtotime($meal_date)) . " has been marked as vacation day. All meals for this date have been removed.";
                } else {
                    $success = "$meal_type meal for " . date('F j, Y', strtotime($meal_date)) . " has been saved successfully!";
                }
            } else {
                $error = "Error saving meal plan. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $error = "Please fill in all required fields with valid data.";
    }
}

// Handle Delete Meal Plan
if (isset($_GET['delete_meal'])) {
    $meal_id = intval($_GET['delete_meal']);
    $stmt = $conn->prepare("DELETE FROM meal_plans WHERE id = ?");
    $stmt->bind_param("i", $meal_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_meals.php");
    exit();
}

// Fetch meal plans for next 30 days
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+30 days'));
$meal_plans = $conn->query("SELECT mp.*, 
    COUNT(ums.id) as total_opted_users,
    SUM(CASE WHEN ums.status = 'ON' THEN 1 ELSE 0 END) as users_opted_in
    FROM meal_plans mp 
    LEFT JOIN user_meal_selections ums ON mp.id = ums.meal_plan_id 
    WHERE mp.meal_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY mp.id 
    ORDER BY mp.meal_date ASC, FIELD(mp.meal_type, 'BREAKFAST', 'LUNCH', 'DINNER')");

$adminName = $_SESSION['name'] ?? $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Management - Mess Master</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .meal-management-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .add-meal-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .meals-list-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .meal-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .meal-card.vacation {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .meal-info h4 {
            color: #333;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .meal-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .meal-description {
            color: #555;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }
        
        .meal-stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .stat-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .price-badge {
            background: #e7f3ff;
            color: #0c4a6e;
        }
        
        .users-badge {
            background: #d4edda;
            color: #155724;
        }
        
        .vacation-badge {
            background: #fff3cd;
            color: #856404;
        }
        
        .meal-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .delete-meal-btn {
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
        
        .delete-meal-btn:hover {
            background: #c0392b;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        
        .quick-add-section {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .quick-add-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .quick-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s ease;
        }
        
        .quick-btn:hover {
            background: #5a67d8;
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

    <div class="meal-management-container">
        <div class="welcome-section" style="margin-bottom: 40px;">
            <h1 style="color: white;">🍽️ Meal Management</h1>
            <p style="color: rgba(255,255,255,0.9);">Plan weekly meals, set prices, and track user selections</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message" style="margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="add-meal-form">
            <h2 style="color: #667eea; margin-bottom: 25px;">🍽️ Add/Update Meal Plan</h2>
            
            <div class="quick-add-section">
                <h4 style="margin-bottom: 15px; color: #333;">📅 Quick Add This Week:</h4>
                <div class="quick-add-buttons">
                    <button type="button" class="quick-btn" onclick="setTodayDate()">Today</button>
                    <button type="button" class="quick-btn" onclick="setTomorrowDate()">Tomorrow</button>
                    <button type="button" class="quick-btn" onclick="setWeekDate(1)">Monday</button>
                    <button type="button" class="quick-btn" onclick="setWeekDate(2)">Tuesday</button>
                    <button type="button" class="quick-btn" onclick="setWeekDate(3)">Wednesday</button>
                    <button type="button" class="quick-btn" onclick="setWeekDate(4)">Thursday</button>
                    <button type="button" class="quick-btn" onclick="setWeekDate(5)">Friday</button>
                    <button type="button" class="quick-btn" onclick="setWeekDate(6)">Saturday</button>
                    <button type="button" class="quick-btn" onclick="setWeekDate(0)">Sunday</button>
                </div>
            </div>
            
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="meal_date">Meal Date:</label>
                        <input type="date" id="meal_date" name="meal_date" required 
                               min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo htmlspecialchars($_POST['meal_date'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="meal_type">Meal Type:</label>
                        <select id="meal_type" name="meal_type" required>
                            <option value="">Select Meal Type</option>
                            <option value="BREAKFAST" <?php echo (($_POST['meal_type'] ?? '') === 'BREAKFAST') ? 'selected' : ''; ?>>🌅 Breakfast</option>
                            <option value="LUNCH" <?php echo (($_POST['meal_type'] ?? '') === 'LUNCH') ? 'selected' : ''; ?>>☀️ Lunch</option>
                            <option value="DINNER" <?php echo (($_POST['meal_type'] ?? '') === 'DINNER') ? 'selected' : ''; ?>>🌙 Dinner</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="meal_price">Meal Price (Tk):</label>
                        <input type="number" id="meal_price" name="meal_price" step="0.50" min="1" placeholder="Enter price" required 
                               value="<?php echo htmlspecialchars($_POST['meal_price'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="meal_name">Meal Name:</label>
                        <input type="text" id="meal_name" name="meal_name" placeholder="e.g., Saturday Special" required 
                               value="<?php echo htmlspecialchars($_POST['meal_name'] ?? ''); ?>">
                    </div>
                </div>
                

                
                <div class="form-group">
                    <label for="meal_description">Meal Description:</label>
                    <textarea id="meal_description" name="meal_description" placeholder="e.g., Dal, Rice, Sabji, Roti, Sweet" rows="2"><?php echo htmlspecialchars($_POST['meal_description'] ?? ''); ?></textarea>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="is_vacation" name="is_vacation" <?php echo (isset($_POST['is_vacation']) ? 'checked' : ''); ?>>
                    <label for="is_vacation">🏖️ Mark as Vacation Day (No meals available - removes any existing meals for this date)</label>
                </div>
                
                <button type="submit" name="add_meal" class="login-btn" style="width: auto; padding: 12px 30px; margin-top: 20px;">
                    💾 Save Meal Plan
                </button>
            </form>
        </div>

        <div class="meals-list-container">
            <h2 style="color: #667eea; margin-bottom: 25px;">📋 Upcoming Meals (<?php echo $meal_plans->num_rows; ?> planned)</h2>
            
            <?php if ($meal_plans->num_rows > 0): ?>
                <?php while($meal = $meal_plans->fetch_assoc()): ?>
                <div class="meal-card <?php echo $meal['is_vacation_day'] ? 'vacation' : ''; ?>">
                    <div class="meal-info">
                        <h4>
                            <?php 
                            $meal_icons = ['BREAKFAST' => '🌅', 'LUNCH' => '☀️', 'DINNER' => '🌙'];
                            echo $meal_icons[$meal['meal_type']] . ' ' . htmlspecialchars($meal['meal_name']); 
                            ?>
                        </h4>
                        <div class="meal-meta">
                            📅 <?= date('l, F j, Y', strtotime($meal['meal_date'])) ?> - <?= ucfirst(strtolower($meal['meal_type'])) ?>
                        </div>
                        <?php if (!empty($meal['meal_description'])): ?>
                        <div class="meal-description">
                            🍽️ <?= htmlspecialchars($meal['meal_description']) ?>
                        </div>
                        <?php endif; ?>
                        <div class="meal-stats">
                            <span class="stat-badge price-badge">Tk <?= number_format($meal['meal_price'], 2) ?></span>
                            <?php if ($meal['is_vacation_day']): ?>
                                <span class="stat-badge vacation-badge">🏖️ Vacation Day</span>
                            <?php else: ?>
                                <span class="stat-badge users-badge">👥 <?= $meal['users_opted_in'] ?> users turned on</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="meal-actions">
                        <a href="admin_meals.php?delete_meal=<?= $meal['id'] ?>" 
                           class="delete-meal-btn"
                           onclick="return confirm('Are you sure you want to delete the meal plan for <?= htmlspecialchars($meal['meal_name']) ?> on <?= date('M j, Y', strtotime($meal['meal_date'])) ?>?')">
                            🗑️ Delete
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p style="font-size: 1.1rem;">📋 No meals planned yet</p>
                    <p>Start planning meals for your mess using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function setTodayDate() {
            document.getElementById('meal_date').value = '<?php echo date('Y-m-d'); ?>';
        }
        
        function setTomorrowDate() {
            document.getElementById('meal_date').value = '<?php echo date('Y-m-d', strtotime('+1 day')); ?>';
        }
        
        function setWeekDate(dayOfWeek) {
            // Get next occurrence of the specified day
            const today = new Date();
            const currentDay = today.getDay();
            let daysToAdd = dayOfWeek - currentDay;
            
            if (daysToAdd <= 0) {
                daysToAdd += 7; // Next week
            }
            
            const targetDate = new Date(today.getTime() + daysToAdd * 24 * 60 * 60 * 1000);
            const year = targetDate.getFullYear();
            const month = String(targetDate.getMonth() + 1).padStart(2, '0');
            const day = String(targetDate.getDate()).padStart(2, '0');
            
            document.getElementById('meal_date').value = `${year}-${month}-${day}`;
        }
        
        // Auto-fill meal name based on day of week and meal type
        function updateMealName() {
            const date = document.getElementById('meal_date').value;
            const mealType = document.getElementById('meal_type').value;
            const mealNameField = document.getElementById('meal_name');
            
            if (date && mealType && !mealNameField.value) {
                const dayDate = new Date(date);
                const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const dayName = dayNames[dayDate.getDay()];
                
                const mealTypeNames = {
                    'BREAKFAST': `${dayName} Morning`,
                    'LUNCH': `${dayName} Lunch`,
                    'DINNER': `${dayName} Dinner`
                };
                
                mealNameField.value = mealTypeNames[mealType] || `${dayName} ${mealType}`;
            }
        }
        
        document.getElementById('meal_date').addEventListener('change', updateMealName);
        document.getElementById('meal_type').addEventListener('change', updateMealName);
        
        // Handle vacation day checkbox
        function toggleVacationFields() {
            const vacationCheckbox = document.getElementById('is_vacation');
            const mealTypeField = document.getElementById('meal_type');
            const mealNameField = document.getElementById('meal_name');
            const mealDescField = document.getElementById('meal_description');
            const mealPriceField = document.getElementById('meal_price');
            
            if (vacationCheckbox.checked) {
                // Disable meal fields when vacation is checked
                mealTypeField.disabled = true;
                mealNameField.disabled = true;
                mealDescField.disabled = true;
                mealPriceField.disabled = true;
                
                // Set vacation values
                mealNameField.value = 'Vacation Day';
                mealDescField.value = 'No meals available - Vacation day';
                mealPriceField.value = '0';
                
                // Style disabled fields
                [mealTypeField, mealNameField, mealDescField, mealPriceField].forEach(field => {
                    field.style.backgroundColor = '#f5f5f5';
                    field.style.color = '#999';
                });
            } else {
                // Enable meal fields when vacation is unchecked
                mealTypeField.disabled = false;
                mealNameField.disabled = false;
                mealDescField.disabled = false;
                mealPriceField.disabled = false;
                
                // Clear vacation values
                mealNameField.value = '';
                mealDescField.value = '';
                mealPriceField.value = '';
                
                // Reset field styles
                [mealTypeField, mealNameField, mealDescField, mealPriceField].forEach(field => {
                    field.style.backgroundColor = '';
                    field.style.color = '';
                });
                
                // Update meal name based on current selections
                updateMealName();
            }
        }
        
        // Add event listener for vacation checkbox
        document.getElementById('is_vacation').addEventListener('change', toggleVacationFields);
        
        // Initialize on page load
        toggleVacationFields();
    </script>
</body>
</html>