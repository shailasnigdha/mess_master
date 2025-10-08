<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo '<p style="color: #e74c3c; text-align: center;">Access denied</p>';
    exit();
}

if (!isset($_GET['user_id'])) {
    echo '<p style="color: #e74c3c; text-align: center;">No user ID provided</p>';
    exit();
}

$user_id = intval($_GET['user_id']);

// Fetch user dues
$stmt = $conn->prepare("SELECT id, amount, due_date, status FROM dues WHERE user_id = ? ORDER BY due_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$dues = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Also get monthly meal summary for additional context
$monthly_stmt = $conn->prepare("SELECT user_id, month, year, total_amount_due, total_amount_paid FROM monthly_meal_summary WHERE user_id = ? ORDER BY year DESC, month DESC LIMIT 6");
$monthly_stmt->bind_param("i", $user_id);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();
$monthly_data = $monthly_result->fetch_all(MYSQLI_ASSOC);
$monthly_stmt->close();

function formatMoney($amount) {
    return 'Tk ' . number_format((float)$amount, 2);
}

function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

function getMonthName($month) {
    return date('F', mktime(0, 0, 0, $month, 1));
}
?>

<?php if (!empty($dues) || !empty($monthly_data)): ?>
    
    <?php if (!empty($dues)): ?>
        <h4 style="color: #667eea; margin-bottom: 10px; font-size: 1rem;">📅 Manual Dues</h4>
        <table class="dues-table" style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dues as $due): ?>
                <tr>
                    <td><?= htmlspecialchars(formatDate($due['due_date'])) ?></td>
                    <td><?= htmlspecialchars(formatMoney($due['amount'])) ?></td>
                    <td>
                        <span class="status-badge <?= ($due['status'] === 'Paid') ? 'status-paid' : 'status-pending' ?>">
                            <?= htmlspecialchars($due['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($due['status'] === 'Pending'): ?>
                            <button onclick="updateDueStatus(<?= $due['id'] ?>, 'Paid')" 
                                    style="background: #28a745; color: white; border: none; padding: 3px 8px; border-radius: 3px; font-size: 11px; cursor: pointer;">
                                Mark Paid
                            </button>
                        <?php else: ?>
                            <button onclick="updateDueStatus(<?= $due['id'] ?>, 'Pending')" 
                                    style="background: #ffc107; color: #333; border: none; padding: 3px 8px; border-radius: 3px; font-size: 11px; cursor: pointer;">
                                Mark Pending
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($monthly_data)): ?>
        <h4 style="color: #667eea; margin-bottom: 10px; font-size: 1rem;">🍽️ Monthly Meal Dues</h4>
        <table class="dues-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Due Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthly_data as $month_data): ?>
                    <?php 
                    $month_name = getMonthName($month_data['month']) . ' ' . $month_data['year'];
                    $is_paid = ($month_data['total_amount_paid'] >= $month_data['total_amount_due'] && $month_data['total_amount_due'] > 0);
                    ?>
                <tr>
                    <td><?= htmlspecialchars($month_name) ?></td>
                    <td><?= htmlspecialchars(formatMoney($month_data['total_amount_due'])) ?></td>
                    <td>
                        <span class="status-badge <?= $is_paid ? 'status-paid' : 'status-pending' ?>">
                            <?= $is_paid ? 'Paid' : 'Pending' ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!$is_paid && $month_data['total_amount_due'] > 0): ?>
                            <button onclick="markMonthPaid(<?= $month_data['user_id'] ?? 'null' ?>, <?= $month_data['month'] ?>, <?= $month_data['year'] ?>, <?= $month_data['total_amount_due'] ?>)" 
                                    style="background: #28a745; color: white; border: none; padding: 3px 8px; border-radius: 3px; font-size: 11px; cursor: pointer;">
                                Mark Paid
                            </button>
                        <?php else: ?>
                            <span style="color: #28a745; font-size: 11px;">✅ Completed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php else: ?>
    <div style="text-align: center; padding: 30px; color: #666;">
        <p style="margin-bottom: 10px;">📋 No dues found for this user</p>
        <p style="font-size: 0.9rem;">Add a due using the form on the left to get started.</p>
    </div>
<?php endif; ?>