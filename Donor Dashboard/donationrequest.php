<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Get donor's username from session
$donorUsername = $_SESSION['donorUsername'];

// First get the user's ID and then find their donor ID
$sql = "SELECT dr.*, u.username as donor_username 
        FROM donation_requests dr
        INNER JOIN donors d ON dr.donor_id = d.id
        INNER JOIN users u ON d.user_id = u.id
        WHERE u.username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $donorUsername);
$stmt->execute();
$result = $stmt->get_result();

// Handle accept/reject actions
if(isset($_POST['action']) && isset($_POST['request_id'])) {
    $requestId = $_POST['request_id'];
    $newStatus = ($_POST['action'] === 'accept') ? 'accepted' : 'rejected';
    $currentTime = date('Y-m-d H:i:s');
    
    $updateSql = "UPDATE donation_requests dr
                  INNER JOIN donors d ON dr.donor_id = d.id
                  INNER JOIN users u ON d.user_id = u.id
                  SET dr.status = ?, dr.updated_at = ?
                  WHERE dr.id = ? AND u.username = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ssss", $newStatus, $currentTime, $requestId, $donorUsername);
    
    if($updateStmt->execute()) {
        $message = "Request has been " . $newStatus;
    } else {
        $error = "Error updating request status";
    }
}
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
            <?php if ($result->num_rows > 0): ?>
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
                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </div>
                        
                        <p class="request-description"><?php echo htmlspecialchars($row['description']); ?></p>
                        
                        <?php if($row['status'] === 'pending' && $row['donor_username'] === $donorUsername): ?>
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
                        <?php elseif($row['status'] === 'accepted'): ?>
                            <div class="request-actions">
                                <a href="make_donation.php?request_id=<?php echo $row['id']; ?>" class="btn donate-btn">
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

<?php
$conn->close();
?>
