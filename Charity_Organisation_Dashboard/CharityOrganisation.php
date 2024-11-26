<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['userRole'] !== 'charity') {
    header("Location: ../charity_login.php");
    exit();
}


// If we get here, user is logged in
error_log("Charity user logged in successfully: " . $_SESSION['charityUsername']);

// Get the username from session
$username = $_SESSION['charityUsername'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DonateConnect - Charity Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="charity.css" rel="stylesheet">
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
                <a href="campaigns.php" class="nav-link" data-page="campaigns">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span>Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="donations.php" class="nav-link" data-page="donations">
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
                <a href="request_donation.php" class="nav-link" data-page="request-donation">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Request Donation</span>
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
                <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
            </div>
        </div>
        <div class="content-area" id="contentArea">
            <!-- Content will be loaded here -->
            <h2>Welcome to the Charity Organization Dashboard</h2>
            <p>Select a menu option to get started.</p>
        </div>
    </div>

    <script>
        // Check if user is logged in
        document.addEventListener('DOMContentLoaded', () => {
            const isLoggedIn = localStorage.getItem('isLoggedIn');
            if (!isLoggedIn) {
                window.location.href = 'CharityOrganisation.php';
                return;
            }

            // Update username from localStorage
            const username = localStorage.getItem('username');
            document.getElementById('username').textContent = username;
        });

        // Navigation handling
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                if (href && href !== '#') {
                    // Allow normal navigation for links with real URLs
                    return;
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
                            <h2>Welcome to Donate Connect Dashboard</h2>
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
                window.location.href = '../charity_login.php';
            }
        });
    </script>
</body>
</html>

