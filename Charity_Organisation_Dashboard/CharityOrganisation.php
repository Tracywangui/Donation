<?php
// Start session to access stored username
session_start();

// Check if the user is logged in
if (!isset($_SESSION['charityUsername'])) {
    // Redirect to login page if not logged in
    header("Location: ../charity_login.php");
    exit();
}

// Retrieve the username from the session
$charityUsername = $_SESSION['charityUsername'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donateconnect - Charity Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="charity.css" rel="stylesheet">
    <script src="auth-check.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Charity Dashboard</div>
        </div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="CharityOrganisation.php" class="nav-link active" data-page="home">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="campaigns.php" class="nav-link " data-page="campaigns">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span>Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="donations.php" class="nav-link " data-page="donations">
                    <i class="fas fa-gift"></i>
                    <span>Donations</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Transactions.php" class="nav-link " data-page="transactions">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Transactions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Notifications.php" class="nav-link " data-page="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <span class="notification-badge" id="notificationCount">0</span>
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
                <i class="fas fa-user"></i>
                <span class="user-name" id="username"><?php echo htmlspecialchars($charityUsername); ?></span> <!-- Display username from session -->
            </div>
        </div>
        <div class="content-area" id="contentArea">
            <!-- Content will be loaded here -->
            <h2>Welcome to Charity Organisation Dashboard</h2>
            <p>Select a menu option to get started.</p>
        </div>
    </div>

    <script>
        // Check if user is logged in
        document.addEventListener('DOMContentLoaded', () => {
            const isLoggedIn = localStorage.getItem('isLoggedIn');
            if (!isLoggedIn) {
                window.location.href = 'CharityOrganisation.php'; // Redirect to login if not logged in
                return;
            }
        });

        // Navigation handling
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                if (href && href !== '#') {
                    return; // Allow normal navigation for links with real URLs
                }

                e.preventDefault();
                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                link.classList.add('active');

                // Update content based on page
                const page = link.getAttribute('data-page');
                const contentArea = document.getElementById('contentArea');

                // Only handle content updates for pages without dedicated HTML files
                switch(page) {
                    case 'home':
                        contentArea.innerHTML = `
                            <h2>Welcome to Donor Connect Dashboard</h2>
                            <p>Select a menu option to get started.</p>
                        `;
                        break;
                }
            });
        });

        // Logout handling
        document.getElementById('logoutBtn').addEventListener('click', () => {
            if(confirm('Are you sure you want to logout?')) {
                localStorage.clear();
                window.location.href = '../charity_login.php'; // Redirect to login page after logout
            }
        });
    </script>
</body>
</html>
