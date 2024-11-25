<?php
// Start session to access stored username
session_start();

// Check if the user is logged in
if (!isset($_SESSION['donorUsername'])) {
    // Redirect to login page if not logged in
    header("Location: ../donor_login.php");
    exit();
}

// Retrieve the username from the session
$donorUsername = $_SESSION['donorUsername']; // Now this will have the value set during login
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DonateConnect - Donor Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="donor.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <script src="auth-check.js"></script>
    
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Donor Dashboard</div>
        </div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="donor_dashboard.php" class="nav-link active" data-page="home">
                    <i class="fas fa-house"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="donations.php" class="nav-link" data-page="donations">
                    <i class="fas fa-heart"></i>
                    <span>My Donations</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="donationrequest.php" class="nav-link" data-page="requests">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <span>Donation Requests</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="donate.php" class="nav-link" data-page="requests">
                    <i class="fas fa-circle-dollar-to-slot"></i>
                    <span>Donate</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="transactions.php" class="nav-link" data-page="transactions">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Transactions</span>
                </a>
            </li>
            
            
        </ul>
        <div class="logout-container">
            <button class="logout-btn" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="user-info">
                <i class="fas fa-circle-user"></i>
                <span class="user-name" id="username"><?php echo htmlspecialchars($donorUsername); ?></span>
            </div>
        </div>
        <div class="content-area home-content" id="contentArea">
            <div class="welcome-section">
            <h1>Welcome, <?php echo htmlspecialchars($donorUsername); ?>!</h1>
                <p>Thank you for your continued support in making the world a better place.</p>
            </div>
        </div>
    </div>

    <div class="qr-code-container">
        
    </div>

    <script>
        // Logout handling
        document.getElementById('logoutBtn').addEventListener('click', () => {
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php'; // Changed to point to logout.php
            }
        });
    </script>
</body>
</html>
