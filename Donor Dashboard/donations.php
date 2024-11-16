<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is not logged in
if (!isset($_SESSION['donorUsername'])) {
    header("Location: ../donor_login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "donateconnect";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Debug query
$donor_id = $_SESSION['donor_id'];

$query = "SELECT d.*, c.title as campaign_name, co.organization_name as charity_name,
          d.reference, d.stripe_payment_status, d.phone 
          FROM donations d
          LEFT JOIN campaigns c ON d.campaign_id = c.id
          LEFT JOIN charity_organizations co ON c.charity_id = co.user_id
          WHERE d.donor_id = ?
          ORDER BY d.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Donations - DonateConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="donor.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <script src="auth-check.js"></script>
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
                <a href="donations.php" class="nav-link active" data-page="donations">
                    <i class="fas fa-heart"></i>
                    <span>My Donations</span>
                </a>
            </li>
            </ul>
        <div class="logout-container">
            <button class="logout-btn" id="logoutBtn">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Logout</span>
            </button>
        </div>
    </div>
    

    <div class="main-content">
    
            
        <div class="content-header">
            <h1>My Donations</h1>
        </div>

        <div class="donations-container">
            <?php if ($result && $result->num_rows > 0) { ?>
                <table class="donations-table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Reference</th>
                            <th>Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['campaign_name'] ?? ''); ?></td>
                                <td>Ksh <?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo date('F j, Y', strtotime($row['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['reference'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['stripe_payment_status'] ?? ''); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <div class="no-donations">
                    <p>You haven't made any donations yet.</p>
                </div>
            <?php } ?>
        </div>
    </div>

    <?php 
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>
