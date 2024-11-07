<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['charityUsername'])) {
    header("Location: ../charity_login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "donateconnect";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $donor_id = $_POST['donor_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $title = $_POST['title'];
    
    // Insert the donation request into the database
    $sql = "INSERT INTO donation_requests (donor_id, charity_username, title, amount, description, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issds", $donor_id, $_SESSION['charityUsername'], $title, $amount, $description);
    
    if ($stmt->execute()) {
        $success_message = "Donation request sent successfully!";
    } else {
        $error_message = "Error sending request: " . $conn->error;
    }
}

// Fetch list of donors
$sql = "SELECT id, email FROM donors ORDER BY email";
$result = $conn->query($sql);
$donors = [];
while ($row = $result->fetch_assoc()) {
    $donors[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Donation - Charity Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="charity.css" rel="stylesheet">
    
       
</head>
<body>
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
    <ul class="nav-links">
        <!-- ... other navigation items ... -->
        
        <li class="nav-item">
            <a href="#" class="nav-link" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

    
    <div class="main-content">
        <div class="top-bar">
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['charityUsername']); ?></span>
            </div>
        </div>

        <div class="request-form">
            <h2>Request Donation</h2>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="donor">Select Donor</label>
                    <select name="donor_id" id="donor" required>
                        <option value="">Choose a donor...</option>
                        <?php foreach ($donors as $donor): ?>
                            <option value="<?php echo $donor['id']; ?>">
                                <?php echo htmlspecialchars($donor['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Request Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="amount">Requested Amount ($)</label>
                    <input type="number" id="amount" name="amount" min="1" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>

                <button type="submit" class="submit-btn">Send Request</button>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        if(confirm('Are you sure you want to logout?')) {
            window.location.href = '../charity_login.php';
            
            // You can also make an AJAX call to destroy the session
            fetch('../logout.php')
                .then(response => {
                    window.location.href = '../charity_login.php';
                });
        }
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>
