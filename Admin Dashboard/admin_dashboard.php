<?php
session_start();

// Add this function at the top of your file after session_start()
function formatCurrency($amount) {
    return 'KSh ' . number_format($amount, 2);
}

// Check for logout action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page using JavaScript
    echo "<script>window.location.href = '../admin_login.php';</script>";
    exit();
}

// Regular session check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../admin_login.php");
    exit();
}

// Get the logged-in username
$loggedInUsername = $_SESSION['username'];

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'donateconnect';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get total donors
$donors_query = "SELECT COUNT(*) as total FROM donors";
$donors_result = mysqli_query($conn, $donors_query);
$total_donors = mysqli_fetch_assoc($donors_result)['total'];

// Get active campaigns
$campaigns_query = "SELECT COUNT(*) as total FROM campaigns WHERE status = 'active'";
$campaigns_result = mysqli_query($conn, $campaigns_query);
$active_campaigns = mysqli_fetch_assoc($campaigns_result)['total'];

// Get total donations
$donations_query = "SELECT COALESCE(SUM(amount), 0) as total FROM donations";
$donations_result = mysqli_query($conn, $donations_query);
$total_donations = mysqli_fetch_assoc($donations_result)['total'];

// Calculate monthly growth
$monthly_growth_query = "
    SELECT 
        COALESCE(
            ((THIS_MONTH.total - LAST_MONTH.total) / NULLIF(LAST_MONTH.total, 0) * 100),
            0
        ) as growth_rate
    FROM (
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM donations 
        WHERE MONTH(date) = MONTH(CURRENT_DATE)
        AND YEAR(date) = YEAR(CURRENT_DATE)
    ) THIS_MONTH,
    (
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM donations 
        WHERE MONTH(date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
        AND YEAR(date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
    ) LAST_MONTH";
$growth_result = mysqli_query($conn, $monthly_growth_query);
$monthly_growth = mysqli_fetch_assoc($growth_result)['growth_rate'];

// Get total charity organizations
$charity_orgs_query = "SELECT COUNT(*) as total FROM charity_organizations";
$charity_orgs_result = mysqli_query($conn, $charity_orgs_query);
$total_charity_orgs = mysqli_fetch_assoc($charity_orgs_result)['total'];

// Get recent activities
$activities_query = "
    (SELECT 
        'donation' as type,
        CONCAT('New donation of KSh ', amount, ' for ', title) as description,
        date as activity_date
    FROM donations
    ORDER BY date DESC
    LIMIT 5)
    UNION
    (SELECT 
        'campaign' as type,
        CONCAT('New campaign: ', title) as description,
        createdAt as activity_date
    FROM campaigns
    ORDER BY createdAt DESC
    LIMIT 5)
    ORDER BY activity_date DESC
    LIMIT 10";
$activities_result = mysqli_query($conn, $activities_query);
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
                    <span>Charity Organisations</span>
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
            <a href="?action=logout" class="logout-btn" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span class="user-name"><?php echo htmlspecialchars($loggedInUsername); ?></span>
            </div>
        </div>
        <div class="content-area" id="contentArea">
            <div class="welcome-section">
                <h2>Welcome back, <span id="welcomeUsername"><?php echo htmlspecialchars($loggedInUsername); ?></span>!</h2>
                <p>Here's an overview of your donation platform's performance</p>
            </div>

            <div class="dashboard-stats" id="dashboardStats">
                <div class="stat-card">
                    <i class="fas fa-users icon"></i>
                    <h3>Total Donors</h3>
                    <div class="number" id="totalDonors"><?php echo number_format($total_donors); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-building icon"></i>
                    <h3>Total Charity Organizations</h3>
                    <div class="number" id="totalCharityOrgs"><?php echo number_format($total_charity_orgs); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hand-holding-heart icon"></i>
                    <h3>Active Campaigns</h3>
                    <div class="number" id="activeCampaigns"><?php echo number_format($active_campaigns); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-dollar-sign icon"></i>
                    <h3>Total Donations</h3>
                    <div class="number" id="totalDonations"><?php echo formatCurrency($total_donations); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-line icon"></i>
                    <h3>Monthly Growth</h3>
                    <div class="number" id="monthlyGrowth"><?php echo number_format($monthly_growth, 1) . '%'; ?></div>
                </div>
            </div>

            <!-- Add a graph for monthly donations -->
            <div class="monthly-donations-chart">
                <h3>Monthly Donations Trend</h3>
                <canvas id="donationsChart"></canvas>
            </div>

            <!-- Recent Activities Section -->
            <div class="recent-activity">
                <h3><i class="fas fa-history"></i> Recent Activities</h3>
                <div class="activity-list" id="activityList">
                    <?php
                    if (mysqli_num_rows($activities_result) > 0) {
                        while ($activity = mysqli_fetch_assoc($activities_result)) {
                            $icon_class = $activity['type'] === 'donation' ? 'fa-dollar-sign' : 'fa-hand-holding-heart';
                            $activity_class = $activity['type'] === 'donation' ? 'donation' : 'campaign';
                            ?>
                            <div class="activity-item <?php echo $activity_class; ?>">
                                <div class="activity-icon">
                                    <i class="fas <?php echo $icon_class; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="activity-text"><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <span class="activity-date">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('M d, Y h:i A', strtotime($activity['activity_date'])); ?>
                                    </span>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="no-activity">
                            <i class="fas fa-info-circle"></i>
                            <p>No recent activities found</p>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default link behavior
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'admin_dashboard.php?action=logout';
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
