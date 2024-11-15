<?php
session_start();
if (!isset($_SESSION['donorUsername'])) {
    header("Location: ../donor_login.php");
    exit();
}

require_once('C:/xampp/htdocs/IS project coding/db.php');

// Updated query with correct column name 'endDate'
$sql = "SELECT c.*, co.organization_name 
        FROM campaigns c 
        INNER JOIN charity_organizations co ON c.charity_id = co.id 
        WHERE c.status = 'active'";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <link href="donor.css" rel="stylesheet">
    <title>Active Campaigns</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        .charity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .charity-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .charity-info h3 {
            font-size: 20px;
            color: #333;
            margin: 0 0 5px 0;
        }

        .charity-info h4 {
            font-size: 18px;
            color: #666;
            margin: 0 0 15px 0;
        }

        .charity-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .campaign-details {
            margin: 20px 0;
        }

        .progress-stats {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            color: #666;
            font-size: 14px;
        }

        .date-info {
            color: #666;
            font-size: 14px;
            margin: 15px 0;
        }

        .donate-btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            margin-top: 10px;
        }

        .donate-btn i {
            margin-right: 5px;
        }

        .donate-btn:hover {
            background: #2980b9;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
        }

        .progress-fill {
            height: 100%;
            background-color: #4CAF50;
            border-radius: 4px;
        }
    </style>
</head>
<body>
   <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Donor Dashboard</div>
        </div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="donor_dashboard.php" class="nav-link" data-page="home">
                    <i class="fas fa-house"></i>
                    <span>Home</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="donate.php" class="nav-link active" data-page="requests">
                    <i class="fas fa-circle-dollar-to-slot"></i>
                    <span>Donate</span>
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
        <h1>Active Campaigns</h1>
        
        <div class="charity-grid">
            <?php while ($campaign = mysqli_fetch_assoc($result)): ?>
                <div class="charity-card">
                    <div class="charity-info">
                        <h3><?php echo htmlspecialchars($campaign['organization_name']); ?></h3>
                        <h4><?php echo htmlspecialchars($campaign['title']); ?></h4>
                    </div>
                    
                    <p class="charity-description">
                        <?php echo htmlspecialchars($campaign['description']); ?>
                    </p>

                    <div class="campaign-details">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo round(($campaign['current_amount'] / $campaign['goal']) * 100); ?>%"></div>
                        </div>
                        <div class="progress-stats">
                            <span>Raised: KES <?php echo number_format($campaign['current_amount'], 2); ?></span>
                            <span>Goal: KES <?php echo number_format($campaign['goal'], 2); ?></span>
                            <span><?php echo round(($campaign['current_amount'] / $campaign['goal']) * 100); ?>% Complete</span>
                        </div>
                    </div>

                    <?php if (isset($campaign['endDate'])): ?>
                        <div class="date-info">
                            End Date: <?php echo date('M d, Y', strtotime($campaign['endDate'])); ?>
                        </div>
                    <?php endif; ?>

                    <a href="../PAYPAGE/index.php?campaign_id=<?php echo $campaign['id']; ?>&charity_id=<?php echo $campaign['charity_id']; ?>&campaign_title=<?php echo urlencode($campaign['title']); ?>" class="donate-btn">
                        <i class="fas fa-heart"></i> Donate Now
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
