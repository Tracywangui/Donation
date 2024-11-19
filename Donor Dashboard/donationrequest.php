<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if the donor is not logged in
if (!isset($_SESSION['donorUsername'])) {
    header("Location: ../donor_login.php");
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

// Get the donor's username from session
$donorUsername = $_SESSION['donorUsername'];

// Debug: Print the username
echo "Current donor username: " . htmlspecialchars($donorUsername) . "<br>";

// First get the donor's ID
$donorIdQuery = "SELECT d.id 
                 FROM donors d 
                 INNER JOIN users u ON d.user_id = u.id 
                 WHERE u.username = ?";
$stmt = $conn->prepare($donorIdQuery);
$stmt->bind_param("s", $donorUsername);
$stmt->execute();
$donorResult = $stmt->get_result();

// Debug: Check if donor was found
if ($donorResult->num_rows === 0) {
    echo "No donor found with username: " . htmlspecialchars($donorUsername) . "<br>";
    exit;
}

$donor = $donorResult->fetch_assoc();
$donorId = $donor['id'];

// Debug: Print the donor ID
echo "Donor ID found: " . $donorId . "<br>";

// Now fetch ALL donation requests
$sql = "SELECT dr.*, u.username as charity_username 
        FROM donation_requests dr
        INNER JOIN users u ON dr.charity_username = u.username
        WHERE dr.donor_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $donorId);
$stmt->execute();
$result = $stmt->get_result();

// Debug: Print the SQL query result
echo "Number of requests found: " . $result->num_rows . "<br>";

// Debug: Print all requests
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Requests</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="donor.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
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
                <a href="donations.php" class="nav-link" data-page="donations">
                    <i class="fas fa-heart"></i>
                    <span>My Donations</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="donationrequest.php" class="nav-link active" data-page="requests">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <span>Donation Requests</span>
                </a>
            </li>
           
        </ul>
        <div class="logout-container">
            <button class="logout-btn" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['donorUsername']); ?></span>
            </div>
        </div>

        <h2>Donation Requests</h2>
        
        <?php if(isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="requests-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div>
                                <h3 class="request-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <div class="request-meta">
                                    <span><i class="fas fa-user"></i> From: <?php echo htmlspecialchars($row['charity_username']); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($row['created_at']); ?></span>
                                    <span><i class="fas fa-dollar-sign"></i> Amount: KES <?php echo htmlspecialchars($row['amount']); ?></span>
                                </div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($row['status'] ?? 'pending'); ?>">
                                <?php echo ucfirst($row['status'] ?? 'Pending'); ?>
                            </span>
                        </div>
                        
                        <p class="request-description"><?php echo htmlspecialchars($row['description']); ?></p>
                        
                        <?php if($row['status'] === 'pending'): ?>
                            <div class="request-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="action" value="accept" class="btn accept-btn">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn reject-btn">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </div>
                        <?php elseif($row['status'] === 'accepted' && $row['donor_id'] == $donorId): ?>
                            <div class="request-actions">
                                <a href="../PAYPAGE/index.php?request_id=<?php echo $row['id']; ?>" class="btn donate-btn">
                                    <i class="fas fa-hand-holding-heart"></i> Donate Now
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No donation requests found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <style>
/* Add or update these styles */
.donate-btn {
    background-color: #2196f3;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.donate-btn:hover {
    background-color: #1976d2;
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.donate-btn i {
    font-size: 1.1em;
}
</style>

    <script>
        // Optional: Add confirmation before accepting/rejecting
        document.querySelectorAll('.request-actions form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = e.submitter.value;
                if (!confirm(`Are you sure you want to ${action} this donation request?`)) {
                    e.preventDefault();
                }
            });
        });

        // Update active button
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                const cards = document.querySelectorAll('.request-card');
                
                cards.forEach(card => {
                    const status = card.querySelector('.status-badge').textContent.toLowerCase();
                    if (filter === 'all' || status === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
