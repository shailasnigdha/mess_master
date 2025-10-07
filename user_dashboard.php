<?php
session_start();

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

$username = $_SESSION['name'] ?? $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Mess Master</title>
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

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Manage your mess activities from your dashboard</p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-icon">💰</div>
                <h3>My Dues</h3>
                <p>Check your pending payments and dues history</p>
                <a href="user_dues.php" class="view-details-btn">View Dues</a>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">🍽️</div>
                <h3>My Meals</h3>
                <p>View meal calendar, toggle meals ON/OFF, and manage dining preferences</p>
                <a href="user_meals.php" class="view-details-btn">Manage Meals</a>
            </div>

                <div class="dashboard-card">
                    <div class="card-icon">📢</div>
                    <h3>My Notices/Events</h3>
                    <p>Stay updated with latest announcements and events</p>
                    <a href="user_notices.php" class="view-details-btn">Browse Notices</a>
                </div>
        </div>

        <!-- Dues Details Section (Hidden by default) -->
        <div id="duesSection" class="details-section" style="display: none;">
            <h2>My Dues</h2>
            <div class="details-content">
                <div class="construction-message" style="text-align: center; padding: 40px;">
                    <div class="construction-icon">💰</div>
                    <h3>View Your Dues</h3>
                    <p>Open the dedicated dues page to see your latest fee details and payment history.</p>
                    <a href="user_dues.php" class="view-details-btn" style="display: inline-block; margin-top: 15px;">Open Dues Page</a>
                </div>
            </div>
            <button class="close-btn" onclick="closeDetails()">Close</button>
        </div>

        <!-- Meals Details Section (Hidden by default) -->
        <div id="mealsSection" class="details-section" style="display: none;">
            <h2>My Meals - Coming Soon</h2>
            <div class="details-content">
                <div class="construction-message" style="text-align: center; padding: 40px;">
                    <div class="construction-icon">🚧</div>
                    <h3>Under Development</h3>
                    <p>This section is being developed by another team member.</p>
                </div>
            </div>
            <button class="close-btn" onclick="closeDetails()">Close</button>
        </div>

        <!-- Notices Details Section (Hidden by default) -->
        <div id="noticesSection" class="details-section" style="display: none;">
            <h2>Notices & Events</h2>
            <div class="details-content">
                <div class="construction-message" style="text-align: center; padding: 40px;">
                    <div class="construction-icon">�</div>
                    <h3>View Latest Notices</h3>
                    <p>Browse all hall announcements, events, and updates on the dedicated notices page.</p>
                    <a href="user_notices.php" class="view-details-btn" style="display: inline-block; margin-top: 15px;">Open Notices Page</a>
                </div>
            </div>
            <button class="close-btn" onclick="closeDetails()">Close</button>
        </div>
    </div>

    <script>
        function viewDues() {
            hideAllSections();
            const dues = document.getElementById('duesSection');
            if (dues) {
                dues.style.display = 'block';
                dues.scrollIntoView({ behavior: 'smooth' });
            }
        }

        function viewMeals() {
            hideAllSections();
            document.getElementById('mealsSection').style.display = 'block';
            document.getElementById('mealsSection').scrollIntoView({ behavior: 'smooth' });
        }

        function closeDetails() {
            hideAllSections();
        }

        function hideAllSections() {
            ['duesSection', 'mealsSection', 'noticesSection'].forEach(id => {
                const section = document.getElementById(id);
                if (section) {
                    section.style.display = 'none';
                }
            });
        }

        // Navigation link functionality
        document.querySelectorAll('.nav-links a:not(.logout-link)').forEach(link => {
            const target = link.getAttribute('href');
            if (!target || !target.startsWith('#')) {
                return;
            }

            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = target.substring(1);

                switch(section) {
                    case 'dues':
                        viewDues();
                        break;
                    case 'meals':
                        alert('Meals section is under development by another team member');
                        break;
                    case 'notices':
                        hideAllSections();
                        const notices = document.getElementById('noticesSection');
                        if (notices) {
                            notices.style.display = 'block';
                            notices.scrollIntoView({ behavior: 'smooth' });
                        }
                        break;
                    case 'profile':
                        alert('Profile section - Coming Soon!');
                        break;
                }
            });
        });
    </script>
</body>
</html>