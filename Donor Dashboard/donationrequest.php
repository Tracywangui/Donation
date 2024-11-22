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

// Function to get donor ID
function getDonorId($conn, $username) {
    $query = "SELECT d.id 
              FROM donors d 
              INNER JOIN users u ON d.user_id = u.id 
              WHERE u.username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }
    return null;
}

// Fetch donor ID
$donorId = getDonorId($conn, $donorUsername);
if (!$donorId) {
    echo "<p>No donor account found for the current user.</p>";
    exit();
}

// Handle form submission for accepting or rejecting requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = $_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'accept') {
        $updateQuery = "UPDATE donation_requests SET status = 'approved' WHERE id = ?";
    } elseif ($action === 'reject') {
        $updateQuery = "UPDATE donation_requests SET status = 'rejected' WHERE id = ?";
    }

    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("i", $requestId);
    if ($updateStmt->execute()) {
        echo "<script>alert('Request updated successfully!');</script>";
    } else {
        echo "<script>alert('Error updating request.');</script>";
    }
}

// Fetch donation requests
function getDonationRequests($conn, $donorId) {
    $sql = "SELECT dr.*, u.username as charity_username 
            FROM donation_requests dr
            INNER JOIN users u ON dr.charity_username = u.username
            WHERE dr.donor_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $donorId);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch data
$donationRequests = getDonationRequests($conn, $donorId);
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
    <style>
        .donate-btn {
            background-color: #007bff; /* Blue */
            color: white;
        }
    </style>
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
                <span class="user-name"><?php echo htmlspecialchars($donorUsername); ?></span>
            </div>
        </div>

        <!-- Donation Requests -->
        <div class="requests-table">
            <h2>Donation Requests</h2>
            <?php if ($donationRequests && $donationRequests->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>From</th>
                            <th>Created At</th>
                            <th>Amount (KES)</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $donationRequests->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['charity_username']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($row['amount']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="action" value="accept" class="btn accept-btn">
                                                Accept
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn reject-btn">
                                                Reject
                                            </button>
                                        </form>
                                    <?php elseif ($row['status'] === 'approved'): ?>
                                        <form method="POST" action="PAYPAGE/index.php" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn donate-btn">
                                                Donate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No donation requests found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
