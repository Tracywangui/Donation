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
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE)
        AND YEAR(created_at) = YEAR(CURRENT_DATE)
    ) THIS_MONTH,
    (
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM donations 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
        AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
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
        CONCAT('New donation of KSh ', d.amount, ' for ', c.title) as description,
        d.created_at as activity_date
    FROM donations d
    LEFT JOIN campaigns c ON d.campaign_id = c.id
    ORDER BY d.created_at DESC
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

// Add these new queries right before the closing PHP tag
$donations_per_donor_query = "
    SELECT d.donor_id, 
           CONCAT(u.firstname, ' ', u.lastname) as donor_name,
           SUM(d.amount) as total_amount 
    FROM donations d
    JOIN donors dn ON d.donor_id = dn.id
    JOIN users u ON dn.user_id = u.id
    WHERE u.role = 'donor'
    GROUP BY d.donor_id
    ORDER BY total_amount DESC
    LIMIT 5";
$donations_per_donor_result = mysqli_query($conn, $donations_per_donor_query);

$donations_per_campaign_query = "
    SELECT c.title as campaign_name, SUM(d.amount) as total_amount 
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.id
    GROUP BY d.campaign_id
    ORDER BY total_amount DESC
    LIMIT 5";
$donations_per_campaign_result = mysqli_query($conn, $donations_per_campaign_query);

$donations_per_charity_query = "
    SELECT co.organization_name as charity_name, 
           SUM(d.amount) as total_amount 
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.id
    JOIN charity_organizations co ON c.charity_id = co.id
    GROUP BY co.id, co.organization_name
    ORDER BY total_amount DESC
    LIMIT 5";
$donations_per_charity_result = mysqli_query($conn, $donations_per_charity_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate Connect - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

            <div class="analytics-section">
                <h3>Donation Analytics</h3>
                <div class="charts-container">
                    <div class="chart-card">
                        <h3>Top Donors</h3>
                        <canvas id="donorsChart" width="700" height="800"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Top Campaigns</h3>
                        <canvas id="campaignsChart" width="700" height="800"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Donations by Charity</h3>
                        <canvas id="charitiesChart" width="700" height="800"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Wait for the DOM to be fully loaded
        window.onload = function() {
            // Chart Data
            <?php
            // Reset the result pointers
            mysqli_data_seek($donations_per_donor_result, 0);
            mysqli_data_seek($donations_per_campaign_result, 0);
            mysqli_data_seek($donations_per_charity_result, 0);
            
            // Fetch all the data
            $donor_data = mysqli_fetch_all($donations_per_donor_result, MYSQLI_ASSOC);
            $campaign_data = mysqli_fetch_all($donations_per_campaign_result, MYSQLI_ASSOC);
            $charity_data = mysqli_fetch_all($donations_per_charity_result, MYSQLI_ASSOC);
            ?>

            // Create the charts
            try {
                // Donors Chart
                const donorsCtx = document.getElementById('donorsChart');
                if (donorsCtx) {
                    new Chart(donorsCtx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode(array_column($donor_data, 'donor_name')); ?>,
                            datasets: [{
                                label: 'Total Donations (KSh)',
                                data: <?php echo json_encode(array_column($donor_data, 'total_amount')); ?>,
                                backgroundColor: 'rgba(54, 162, 235, 0.8)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                // Campaigns Chart
                const campaignsCtx = document.getElementById('campaignsChart');
                if (campaignsCtx) {
                    new Chart(campaignsCtx, {
                        type: 'pie',
                        data: {
                            labels: <?php echo json_encode(array_column($campaign_data, 'campaign_name')); ?>,
                            datasets: [{
                                data: <?php echo json_encode(array_column($campaign_data, 'total_amount')); ?>,
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.8)',
                                    'rgba(54, 162, 235, 0.8)',
                                    'rgba(255, 206, 86, 0.8)',
                                    'rgba(75, 192, 192, 0.8)',
                                    'rgba(153, 102, 255, 0.8)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }

                // Charities Chart
                const charitiesCtx = document.getElementById('charitiesChart');
                if (charitiesCtx) {
                    new Chart(charitiesCtx, {
                        type: 'doughnut',
                        data: {
                            labels: <?php echo json_encode(array_column($charity_data, 'charity_name')); ?>,
                            datasets: [{
                                data: <?php echo json_encode(array_column($charity_data, 'total_amount')); ?>,
                                backgroundColor: [
                                    'rgba(255, 159, 64, 0.8)',
                                    'rgba(75, 192, 192, 0.8)',
                                    'rgba(54, 162, 235, 0.8)',
                                    'rgba(153, 102, 255, 0.8)',
                                    'rgba(255, 99, 132, 0.8)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            } catch (error) {
                console.error('Error creating charts:', error);
            }

            // Logout button handler
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.onclick = function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to logout?')) {
                        window.location.href = 'admin_dashboard.php?action=logout';
                    }
                };
            }
        };
    </script>
</body>

</html>
