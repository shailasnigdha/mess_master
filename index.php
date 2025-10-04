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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Master - Home</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .hero-section {
            text-align: center;
            color: white;
            padding: 100px 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero-section h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.8s ease-out;
        }
        
        .hero-section p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.9;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }
        
        .cta-button {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            color: #5a67d8;
        }
        
        .features-section {
            background: white;
            margin: 60px auto;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            animation: fadeInUp 1s ease-out 0.6s both;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .feature-card {
            text-align: center;
            padding: 30px 20px;
            border-radius: 15px;
            background: #f8f9fa;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            background: #e9ecef;
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }
        
        .feature-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.5;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .hero-section p {
                font-size: 1.1rem;
            }
            
            .features-section {
                margin: 40px 20px;
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <h1>🍽️ Mess Master</h1>
        <p>Your complete mess management solution. Track meals, manage dues, and stay updated with notices - all in one place.</p>
        <a href="login.php" class="cta-button">Get Started</a>
    </div>
    
    <div class="features-section">
        <div style="text-align: center; margin-bottom: 40px;">
            <h2 style="color: #667eea; font-size: 2.2rem; margin-bottom: 15px;">Why Choose Mess Master?</h2>
            <p style="color: #666; font-size: 1.1rem;">Streamline your mess operations with our comprehensive management system</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <span class="feature-icon">💰</span>
                <h3>Dues Management</h3>
                <p>Track pending payments, view dues history, and manage financial records efficiently.</p>
            </div>
            
            <div class="feature-card">
                <span class="feature-icon">🍽️</span>
                <h3>Meal Tracking</h3>
                <p>Monitor daily meals, track attendance, and manage meal preferences seamlessly.</p>
            </div>
            
            <div class="feature-card">
                <span class="feature-icon">📢</span>
                <h3>Notice Board</h3>
                <p>Stay updated with important announcements, events, and mess-related information.</p>
            </div>
            
            <div class="feature-card">
                <span class="feature-icon">👥</span>
                <h3>User Management</h3>
                <p>Comprehensive user profiles for both residential and non-residential members.</p>
            </div>
            
            <div class="feature-card">
                <span class="feature-icon">⚙️</span>
                <h3>Admin Control</h3>
                <p>Powerful admin dashboard to manage users, notices, and overall mess operations.</p>
            </div>
            
            <div class="feature-card">
                <span class="feature-icon">📊</span>
                <h3>Analytics</h3>
                <p>Get insights into mess usage, payments, and member activity with detailed reports.</p>
            </div>
        </div>
        
        <div style="margin-top: 40px; text-align: center;">
            <a href="login.php" class="cta-button" style="margin: 0; background: #667eea; color: white;">Login to Your Account</a>
        </div>
    </div>
    
    <div style="text-align: center; color: white; padding: 40px 20px; opacity: 0.8;">
        <p>&copy; 2025 Mess Master. Built for efficient mess management.</p>
    </div>
</body>
</html>