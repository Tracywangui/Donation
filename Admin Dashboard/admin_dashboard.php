<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['adminUsername'])) {
    header('Location: ../admin_login.php'); // Redirect to login if not authenticated
    exit();
}

// Logout functionality
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to the login page
    header('Location: ../admin_login.php');
    exit();
}

// Get the username from the session
$username = htmlspecialchars($_SESSION['adminUsername']); // Use the correct session variable

// Example of getting donor data and activities from a database or other source
// Replace these placeholders with actual database queries
$totalDonors = 0; // Placeholder for total donors
$activeCampaigns = 0; // Placeholder for active campaigns
$totalDonations = 0; // Placeholder for total donations
$monthlyGrowth = 0; // Placeholder for monthly growth rate
$recentActivities = []; // Placeholder for recent activities

// Function to format currency (assuming you have one)
function formatCurrency($amount) {
    return "KSh" . number_format($amount, 2);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Connect - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    <script src="auth-check.js"></script>
</head>

<body>
    <!-- Sidebar - Keeping existing structure -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Admin Dashboard</div>
        </div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link active" data-page="home">
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
                <a href="Transactions.php" class="nav-link" data-page="transactions">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Transactions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Charity_organisation_details.php" class="nav-link" data-page="charities">
                    <i class="fas fa-building"></i>
                    <span>Charities</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="donor_details.php" class="nav-link" data-page="donors">
                    <i class="fas fa-users"></i>
                    <span>Donors</span>
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
                <span class="user-name" id="username"><?php echo htmlspecialchars($username); ?></span>
            </div>
        </div>
        <div class="content-area" id="contentArea">
            <div class="welcome-section">
                <h2>Welcome back, <span id="welcomeUsername"><?php echo htmlspecialchars($username); ?></span>!</h2>
                <p>Here's an overview of your donation platform's performance</p>
            </div>

            <div class="dashboard-stats" id="dashboardStats">
                <div class="stat-card">
                    <i class="fas fa-users icon"></i>
                    <h3>Total Donors</h3>
                    <div class="number" id="totalDonors"><?php echo number_format($totalDonors); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hand-holding-heart icon"></i>
                    <h3>Active Campaigns</h3>
                    <div class="number" id="activeCampaigns"><?php echo number_format($activeCampaigns); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-dollar-sign icon"></i>
                    <h3>Total Donations</h3>
                    <div class="number" id="totalDonations"><?php echo formatCurrency($totalDonations); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-line icon"></i>
                    <h3>Monthly Growth</h3>
                    <div class="number" id="monthlyGrowth"><?php echo $monthlyGrowth . '%'; ?></div>
                </div>
            </div>

            <div class="recent-activity">
                <h3>Recent Activity</h3>
                <div class="activity-list" id="activityList">
                    <!-- Activities will be populated dynamically -->
                    <div class="loading-spinner"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', () => {
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = '?action=logout'; // Redirect to self with logout action
            }
        });

        // Other JavaScript functionalities remain unchanged...

        document.getElementById('registrationForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }

            // Get form data
            const formData = {
                organisation: document.querySelector('input[name="organisation"]').value,
                email: document.querySelector('input[name="email"]').value,
                phoneNo: document.querySelector('input[name="phoneNo"]').value,
                username: document.querySelector('input[name="username"]').value,
                registrationDate: new Date().toISOString(),
            };

            // Get existing donors from localStorage or initialize empty array
            const existingDonors = JSON.parse(localStorage.getItem('registeredDonors') || '[]');

            // Add new donor
            existingDonors.push(formData);

            // Save updated donors list
            localStorage.setItem('registeredDonors', JSON.stringify(existingDonors));

            // Add to activity log
            const activities = JSON.parse(localStorage.getItem('activities') || '[]');
            activities.unshift({
                type: 'registration',
                description: `New donor registration: ${formData.organisation}`,
                timestamp: new Date().toISOString()
            });
            localStorage.setItem('activities', JSON.stringify(activities));

            // Redirect to login page
            window.location.href = '../admin_login.php';
        });

        function updateDashboardStats() {
            try {
                // Get registered donors from localStorage
                const donors = JSON.parse(localStorage.getItem('registeredDonors') || '[]');

                // Update total donors count
                document.getElementById('totalDonors').textContent = donors.length.toLocaleString();

                // Calculate daily growth rate
                const today = new Date().toISOString().split('T')[0];
                const todayDonors = donors.filter(donor => {
                    const donorDate = new Date(donor.registrationDate || donor.registeredDate).toISOString().split('T')[0];
                    return donorDate === today;
                }).length;

                const growthRate = donors.length > 0
                    ? ((todayDonors / donors.length) * 100).toFixed(1)
                    : 0;

                document.getElementById('monthlyGrowth').textContent = `${growthRate}%`;

                // Update recent activity
                updateRecentActivity();

            } catch (error) {
                console.error('Error updating dashboard:', error);
                showError('Failed to update dashboard data');
            }
        }

        function updateRecentActivity() {
            const activityList = document.getElementById('activityList');

            try {
                const donors = JSON.parse(localStorage.getItem('registeredDonors') || '[]');
                const activities = donors.map(donor => ({
                    type: 'registration',
                    description: `New donor registration: ${donor.organisation}`,
                    timestamp: donor.registrationDate,
                })).sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

                // Clear existing activities
                activityList.innerHTML = '';

                activities.forEach(activity => {
                    const activityItem = document.createElement('div');
                    activityItem.classList.add('activity-item');
                    activityItem.textContent = `${activity.description} - ${new Date(activity.timestamp).toLocaleString()}`;
                    activityList.appendChild(activityItem);
                });
            } catch (error) {
                console.error('Error updating recent activity:', error);
                activityList.innerHTML = '<p>Error loading activity.</p>';
            }
        }

        // Initial dashboard update
        updateDashboardStats();
    </script>
</body>

</html>
