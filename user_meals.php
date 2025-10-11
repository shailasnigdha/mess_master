<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'User') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['name'] ?? $_SESSION['username'];

// Handle meal toggle
if (isset($_POST['toggle_meal'])) {
    $meal_plan_id = intval($_POST['meal_plan_id']);
    $meal_date = $_POST['meal_date'];
    $meal_type = $_POST['meal_type'];
    $new_status = $_POST['new_status'];
    $payment_choice = $_POST['payment_choice'] ?? 'DUE';
    
    // Check if it's more than 1 day before meal date
    $meal_timestamp = strtotime($meal_date);
    $tomorrow_timestamp = strtotime('+1 day');
    
    if ($meal_timestamp > $tomorrow_timestamp) {
        // Insert or update user meal selection
        $stmt = $conn->prepare("INSERT INTO user_meal_selections (user_id, meal_plan_id, meal_date, meal_type, status, payment_status) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), payment_status = VALUES(payment_status), updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("iissss", $user_id, $meal_plan_id, $meal_date, $meal_type, $new_status, $payment_choice);
        
        if ($stmt->execute()) {
            $success = ucfirst(strtolower($meal_type)) . " meal updated successfully!";
            
            // Update monthly summary
            $month = date('n', strtotime($meal_date));
            $year = date('Y', strtotime($meal_date));
            
            // Recalculate monthly totals
            $summary_query = "
                SELECT 
                    COUNT(CASE WHEN ums.status = 'ON' AND ums.meal_type = 'BREAKFAST' THEN 1 END) as breakfast_opted,
                    COUNT(CASE WHEN ums.status = 'ON' AND ums.meal_type = 'LUNCH' THEN 1 END) as lunch_opted,
                    COUNT(CASE WHEN ums.status = 'ON' AND ums.meal_type = 'DINNER' THEN 1 END) as dinner_opted,
                    SUM(CASE WHEN ums.status = 'ON' AND ums.payment_status = 'DUE' THEN mp.meal_price ELSE 0 END) as total_due,
                    SUM(CASE WHEN ums.status = 'ON' AND ums.payment_status = 'PAID' THEN mp.meal_price ELSE 0 END) as total_paid
                FROM user_meal_selections ums
                JOIN meal_plans mp ON ums.meal_plan_id = mp.id
                WHERE ums.user_id = ? AND MONTH(ums.meal_date) = ? AND YEAR(ums.meal_date) = ?
            ";
            
            $summary_stmt = $conn->prepare($summary_query);
            $summary_stmt->bind_param("iii", $user_id, $month, $year);
            $summary_stmt->execute();
            $summary_result = $summary_stmt->get_result()->fetch_assoc();
            
            // Update or insert monthly summary
            $update_summary = $conn->prepare("INSERT INTO monthly_meal_summary (user_id, month, year, total_breakfast_opted, total_lunch_opted, total_dinner_opted, total_amount_due, total_amount_paid) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE total_breakfast_opted = VALUES(total_breakfast_opted), total_lunch_opted = VALUES(total_lunch_opted), total_dinner_opted = VALUES(total_dinner_opted), total_amount_due = VALUES(total_amount_due), total_amount_paid = VALUES(total_amount_paid)");
            $update_summary->bind_param("iiiiiidd", $user_id, $month, $year, $summary_result['breakfast_opted'], $summary_result['lunch_opted'], $summary_result['dinner_opted'], $summary_result['total_due'], $summary_result['total_paid']);
            $update_summary->execute();
            
        } else {
            $error = "Error updating meal. Please try again.";
        }
        $stmt->close();
    } else {
        $error = "Cannot change meal. You can only modify meals that are more than 1 day away.";
    }
}

// Fetch meal plans for current month grouped by date
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

$meal_plans_query = "
    SELECT 
        mp.*,
        ums.status as user_status,
        ums.payment_status,
        CASE 
            WHEN mp.is_vacation_day = 1 THEN 'VACATION'
            WHEN DATE(mp.meal_date) <= CURDATE() THEN 'PAST'
            WHEN DATE(mp.meal_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'TOMORROW'
            WHEN DATE(mp.meal_date) <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'TOO_LATE'
            ELSE 'AVAILABLE'
        END as availability_status
    FROM meal_plans mp
    LEFT JOIN user_meal_selections ums ON mp.id = ums.meal_plan_id AND ums.user_id = ? AND ums.meal_type = mp.meal_type
    WHERE mp.meal_date BETWEEN ? AND ?
    ORDER BY mp.meal_date ASC, FIELD(mp.meal_type, 'BREAKFAST', 'LUNCH', 'DINNER')
";

$stmt = $conn->prepare($meal_plans_query);
$stmt->bind_param("iss", $user_id, $current_month_start, $current_month_end);
$stmt->execute();
$meal_plans_result = $stmt->get_result();

// Group meals by date
$meals_by_date = [];
while ($meal = $meal_plans_result->fetch_assoc()) {
    $meals_by_date[$meal['meal_date']][] = $meal;
}

// Get monthly summary
$current_month = date('n');
$current_year = date('Y');
$monthly_summary_query = "SELECT * FROM monthly_meal_summary WHERE user_id = ? AND month = ? AND year = ?";
$summary_stmt = $conn->prepare($monthly_summary_query);
$summary_stmt->bind_param("iii", $user_id, $current_month, $current_year);
$summary_stmt->execute();
$monthly_summary = $summary_stmt->get_result()->fetch_assoc();

if (!$monthly_summary) {
    $monthly_summary = [
        'total_breakfast_opted' => 0, 
        'total_lunch_opted' => 0, 
        'total_dinner_opted' => 0, 
        'total_amount_due' => 0, 
        'total_amount_paid' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Meals - Mess Master</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .meals-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .monthly-summary {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .summary-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 1.8rem;
        }
        
        .summary-card p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .meals-calendar {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .meal-day-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .meal-day-card.opted-in {
            background: #d4edda;
            border-color: #28a745;
        }
        
        .meal-day-card.vacation {
            background: #fff3cd;
            border-color: #ffc107;
        }
        
        .meal-day-card.past {
            background: #f8f9fa;
            border-color: #dee2e6;
            opacity: 0.7;
        }
        
        .meal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .meal-date-info h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 1.1rem;
        }
        
        .meal-date {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .meal-price {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1rem;
        }
        
        .meal-status {
            text-align: right;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 10px;
            display: inline-block;
        }
        
        .status-on { background: #d4edda; color: #155724; }
        .status-off { background: #f8d7da; color: #721c24; }
        .status-vacation { background: #fff3cd; color: #856404; }
        .status-past { background: #e2e3e5; color: #6c757d; }
        
        .meal-description {
            color: #555;
            font-size: 0.95rem;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .meal-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .toggle-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .payment-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .toggle-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .toggle-btn.opt-in {
            background: #28a745;
            color: white;
        }
        
        .toggle-btn.opt-out {
            background: #dc3545;
            color: white;
        }
        
        .toggle-btn:hover {
            transform: translateY(-2px);
        }
        
        .toggle-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .restriction-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #856404;
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

    <div class="meals-container">
        <div class="welcome-section" style="margin-bottom: 40px;">
            <h1 style="color: white;">🍽️ My Meals</h1>
            <p style="color: rgba(255,255,255,0.9);">Plan your meals and manage your dining preferences</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message" style="margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

                                            <!-- Feedback button for this meal -->
                                            <a href="user_feedback.php?meal_id=<?php echo $meal['id']; ?>" class="submit-btn" style="margin-left:8px; padding:6px 12px; font-size:0.9rem;">📝 Give feedback</a>
        <div class="restriction-note">
            <strong>📋 Important:</strong> You can only change your meals for meals that are more than 1 day away. 
            Vacation days are set by admin and cannot be modified.
        </div>

        <div class="monthly-summary">
            <h2 style="color: #667eea; margin-bottom: 0;">📊 <?php echo date('F Y'); ?> Summary</h2>
            <div class="summary-grid">
                <div class="summary-card">
                    <h3><?php echo $monthly_summary['total_breakfast_opted']; ?></h3>
                    <p>🌅 Breakfast</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo $monthly_summary['total_lunch_opted']; ?></h3>
                    <p>☀️ Lunch</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo $monthly_summary['total_dinner_opted']; ?></h3>
                    <p>🌙 Dinner</p>
                </div>
                <div class="summary-card">
                    <h3>Tk <?php echo number_format($monthly_summary['total_amount_paid'], 2); ?></h3>
                    <p>💳 Amount Paid</p>
                </div>
                <div class="summary-card">
                    <h3>Tk <?php echo number_format($monthly_summary['total_amount_due'], 2); ?></h3>
                    <p>💰 Amount Due</p>
                </div>
                <div class="summary-card">
                    <h3>Tk <?php echo number_format($monthly_summary['total_amount_due'] + $monthly_summary['total_amount_paid'], 2); ?></h3>
                    <p>💸 Total Amount</p>
                </div>
            </div>
        </div>

        <div class="meals-calendar">
            <h2 style="color: #667eea; margin-bottom: 25px;">🗓️ Meal Calendar - <?php echo date('F Y'); ?></h2>
            
            <?php if (!empty($meals_by_date)): ?>
                <?php foreach($meals_by_date as $date => $daily_meals): ?>
                    <div class="meal-day-card">
                        <div style="border-bottom: 2px solid #e9ecef; padding-bottom: 15px; margin-bottom: 20px;">
                            <h3 style="color: #667eea; margin: 0;"><?php echo date('l, F j, Y', strtotime($date)); ?></h3>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <?php foreach($daily_meals as $meal): ?>
                                <?php
                                $is_opted_in = ($meal['user_status'] === 'ON');
                                $availability = $meal['availability_status'];
                                $can_toggle = ($availability === 'AVAILABLE');
                                $meal_icons = ['BREAKFAST' => '🌅', 'LUNCH' => '☀️', 'DINNER' => '🌙'];
                                ?>
                                <div class="individual-meal" style="background: <?php 
                                    if ($is_opted_in) echo '#d4edda';
                                    elseif ($availability === 'VACATION') echo '#fff3cd';
                                    elseif ($availability === 'PAST') echo '#f8f9fa';
                                    else echo '#f8f9fa';
                                ?>; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;">
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                        <div>
                                            <h5 style="margin: 0; color: #333;">
                                                <?php echo $meal_icons[$meal['meal_type']] . ' ' . htmlspecialchars($meal['meal_name']); ?>
                                            </h5>
                                            <div style="font-size: 0.9rem; color: #666; margin: 5px 0;">
                                                <?php echo ucfirst(strtolower($meal['meal_type'])); ?>
                                            </div>
                                            <div style="font-weight: bold; color: #667eea;">
                                                Tk <?php echo number_format($meal['meal_price'], 2); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="status-badge <?php 
                                            if ($availability === 'VACATION') echo 'status-vacation';
                                            elseif ($availability === 'PAST') echo 'status-past';
                                            elseif ($is_opted_in) echo 'status-on';
                                            else echo 'status-off';
                                        ?>" style="font-size: 0.75rem; padding: 4px 8px;">
                                            <?php 
                                            if ($availability === 'VACATION') echo '🏖️ Vacation';
                                            elseif ($availability === 'PAST') echo '⏰ Past';
                                            elseif ($is_opted_in) echo '✅ Turned ON';
                                            else echo '❌ Turned OFF';
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($meal['meal_description'])): ?>
                                    <div style="font-size: 0.85rem; color: #555; margin-bottom: 10px; font-style: italic;">
                                        <?php echo htmlspecialchars($meal['meal_description']); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_opted_in && $meal['payment_status']): ?>
                                    <div class="status-badge" style="background: <?php echo ($meal['payment_status'] === 'PAID') ? '#d4edda' : '#fff3cd'; ?>; color: <?php echo ($meal['payment_status'] === 'PAID') ? '#155724' : '#856404'; ?>; font-size: 0.75rem; margin-bottom: 10px;">
                                        <?php echo ($meal['payment_status'] === 'PAID') ? '💳 Paid' : '💰 Due'; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="meal-actions">
                                        <?php if ($availability === 'VACATION'): ?>
                                            <span style="color: #856404; font-size: 0.8rem;">🏖️ Vacation - Not available</span>
                                        <?php elseif ($availability === 'PAST'): ?>
                                            <span style="color: #6c757d; font-size: 0.8rem;">⏰ Past date</span>
                                        <?php elseif ($availability === 'TOO_LATE' || $availability === 'TOMORROW'): ?>
                                            <span style="color: #dc3545; font-size: 0.8rem;">⚠️ Too late to change</span>
                                        <?php elseif ($can_toggle): ?>
                                            <form method="post" class="toggle-form" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                                <input type="hidden" name="meal_plan_id" value="<?php echo $meal['id']; ?>">
                                                <input type="hidden" name="meal_date" value="<?php echo $meal['meal_date']; ?>">
                                                <input type="hidden" name="meal_type" value="<?php echo $meal['meal_type']; ?>">
                                                
                                                <?php if (!$is_opted_in): ?>
                                                    <select name="payment_choice" class="payment-select" required style="font-size: 0.8rem; padding: 4px 8px;">
                                                        <option value="">Payment?</option>
                                                        <option value="PAID">💳 Pay Now</option>
                                                        <option value="DUE">💰 Add to Dues</option>
                                                    </select>
                                                    <input type="hidden" name="new_status" value="ON">
                                                    <button type="submit" name="toggle_meal" class="toggle-btn opt-in" style="font-size: 0.8rem; padding: 4px 12px;">
                                                        ✅ Turn ON
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="OFF">
                                                    <button type="submit" name="toggle_meal" class="toggle-btn opt-out" style="font-size: 0.8rem; padding: 4px 12px;">
                                                        ❌ Turn OFF
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p style="font-size: 1.1rem;">📋 No meals planned for this month</p>
                    <p>Meal plans will appear here once the admin adds them.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-submit when payment choice is selected for opt-in
        document.querySelectorAll('.payment-select').forEach(select => {
            select.addEventListener('change', function() {
                if (this.value) {
                    // Enable the opt-in button
                    const button = this.parentNode.querySelector('.toggle-btn.opt-in');
                    button.disabled = false;
                }
            });
        });
        
        // Initially disable opt-in buttons until payment choice is selected
        document.querySelectorAll('.toggle-btn.opt-in').forEach(button => {
            const select = button.parentNode.querySelector('.payment-select');
            if (select && !select.value) {
                button.disabled = true;
            }
        });
    </script>
</body>
</html>