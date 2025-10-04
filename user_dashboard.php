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
                <li><a href="#profile">Profile</a></li>
                <li><a href="#dues">Dues</a></li>
                <li><a href="#meals">Meals</a></li>
                <li><a href="#notices">Notices</a></li>
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
                <button class="view-details-btn" onclick="viewDues()">View Details</button>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">🍽️</div>
                <h3>My Meals</h3>
                <p>Coming Soon - Under Development</p>
                <button class="view-details-btn" onclick="alert('Meals section is under development by another team member')">Coming Soon</button>
            </div>

            <div class="dashboard-card">
                <div class="card-icon">📢</div>
                <h3>My Notices/Events</h3>
                <p>Stay updated with latest announcements and events</p>
                <button class="view-details-btn" onclick="viewNotices()">View Details</button>
            </div>
        </div>

        <!-- Dues Details Section (Hidden by default) -->
        <div id="duesSection" class="details-section" style="display: none;">
            <h2>My Dues</h2>
            <div class="details-content">
                <div class="dues-item">
                    <span class="dues-month">January 2025</span>
                    <span class="dues-amount paid">₹2,500 - Paid</span>
                </div>
                <div class="dues-item">
                    <span class="dues-month">February 2025</span>
                    <span class="dues-amount paid">₹2,500 - Paid</span>
                </div>
                <div class="dues-item">
                    <span class="dues-month">March 2025</span>
                    <span class="dues-amount pending">₹2,500 - Pending</span>
                </div>
                <div class="total-dues">
                    <strong>Total Pending: ₹2,500</strong>
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
                <div class="notice-item">
                    <div class="notice-title">🎉 Holi Celebration</div>
                    <div class="notice-date">March 14, 2025</div>
                    <div class="notice-content">Join us for Holi celebration in the mess premises. Special menu will be served.</div>
                </div>
                <div class="notice-item">
                    <div class="notice-title">📋 Monthly Menu Update</div>
                    <div class="notice-date">March 1, 2025</div>
                    <div class="notice-content">New menu for March has been updated. Check the notice board for details.</div>
                </div>
                <div class="notice-item">
                    <div class="notice-title">⚠️ Timing Change</div>
                    <div class="notice-date">February 28, 2025</div>
                    <div class="notice-content">Dinner timing has been changed to 7:00 PM - 9:00 PM from March 1st.</div>
                </div>
            </div>
            <button class="close-btn" onclick="closeDetails()">Close</button>
        </div>
    </div>

    <script>
        function viewDues() {
            hideAllSections();
            document.getElementById('duesSection').style.display = 'block';
            document.getElementById('duesSection').scrollIntoView({ behavior: 'smooth' });
        }

        function viewMeals() {
            hideAllSections();
            document.getElementById('mealsSection').style.display = 'block';
            document.getElementById('mealsSection').scrollIntoView({ behavior: 'smooth' });
        }

        function viewNotices() {
            hideAllSections();
            document.getElementById('noticesSection').style.display = 'block';
            document.getElementById('noticesSection').scrollIntoView({ behavior: 'smooth' });
        }

        function closeDetails() {
            hideAllSections();
        }

        function hideAllSections() {
            document.getElementById('duesSection').style.display = 'none';
            document.getElementById('mealsSection').style.display = 'none';
            document.getElementById('noticesSection').style.display = 'none';
        }

        // Navigation link functionality
        document.querySelectorAll('.nav-links a:not(.logout-link)').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.getAttribute('href').substring(1);
                
                switch(section) {
                    case 'dues':
                        viewDues();
                        break;
                    case 'meals':
                        alert('Meals section is under development by another team member');
                        break;
                    case 'notices':
                        viewNotices();
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