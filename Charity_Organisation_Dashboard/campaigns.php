<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['charityUsername'])) {
    header("Location: ../charity_login.php");
    exit();
}

// Get the logged-in username
$charityUsername = $_SESSION['charityUsername'];

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "donateconnect";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$campaigns = [];
$message = "";

// Fetch campaigns
$result = $conn->query("SELECT * FROM campaigns WHERE charity_id = (SELECT id FROM users WHERE username = '$charityUsername')");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $campaigns[] = $row;
    }
}

// Add new campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $goal = $_POST['goal'];
    $endDate = $_POST['endDate'];
    $status = 'active'; // Default status for new campaigns

    // Fetch the charity ID based on the session username
    $charity_id_result = $conn->query("SELECT id FROM users WHERE username = '$charityUsername'");
    if ($charity_id_result && $charity_id_result->num_rows > 0) {
        $charity_id = $charity_id_result->fetch_assoc()['id'];
        
        // Updated INSERT query to include all necessary fields
        $stmt = $conn->prepare("INSERT INTO campaigns (charity_id, title, description, goal, endDate, createdAt, updatedAt, status) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)");
        $stmt->bind_param("issdss", $charity_id, $title, $description, $goal, $endDate, $status);

        if ($stmt->execute()) {
            $message = "Campaign added successfully";
            header("Location: campaigns.php?message=" . urlencode($message));
            exit();
        } else {
            die("Error adding campaign: " . $stmt->error);
        }
    } else {
        die("Charity ID not found for username: " . htmlspecialchars($charityUsername));
    }
}

// Delete campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['campaignId'];
    $stmt = $conn->prepare("DELETE FROM campaigns WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $message = "Campaign deleted successfully";
        header("Location: campaigns.php?message=" . urlencode($message));
        exit();
    }
}

// Edit campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['campaignId'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $goal = $_POST['goal'];
    $endDate = $_POST['endDate'];

    $stmt = $conn->prepare("UPDATE campaigns SET title = ?, description = ?, goal = ?, endDate = ?, updatedAt = NOW() WHERE id = ?");
    $stmt->bind_param("ssdsi", $title, $description, $goal, $endDate, $id);

    if ($stmt->execute()) {
        $message = "Campaign updated successfully";
        header("Location: campaigns.php?message=" . urlencode($message));
        exit();
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DonateConnect - Campaigns</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="charity.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo"> Charity Dashboard</div>
        </div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="/IS PROJECT CODING/Charity_Organisation_Dashboard/CharityOrganisation.php" class="nav-link" data-page="home">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/IS PROJECT CODING/Charity_Organisation_Dashboard/campaigns.php" class="nav-link active" data-page="requests">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span>Campaigns</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/IS PROJECT CODING/Charity_Organisation_Dashboard/donations.php" class="nav-link" data-page="donations">
                    <i class="fas fa-gift"></i>
                    <span>Donations</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/IS PROJECT CODING/Charity_Organisation_Dashboard/transactions.php" class="nav-link" data-page="transactions">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Transactions</span>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span class="user-name" id="username"><?php echo htmlspecialchars($charityUsername); ?></span>
            </div>
        </div>
        <div class="content-area">
            <?php if (!empty($message)) : ?>
                <p class="message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <div class="campaigns-header">
                <h2>Campaigns</h2>
                <button class="add-campaign-btn" onclick="openModal('add')">
                    <i class="fas fa-plus"></i>
                    <span>Add Campaign</span>
                </button>
            </div>
            <div class="campaigns-grid">
                <?php foreach ($campaigns as $campaign): ?>
                    <div class="campaign-card">
                        <h3 class="campaign-title"><?php echo htmlspecialchars($campaign['title']); ?></h3>
                        <p class="campaign-description"><?php echo htmlspecialchars($campaign['description']); ?></p>
                        <div class="campaign-meta">
                            <span>Goal: Ksh <?php echo htmlspecialchars($campaign['goal']); ?></span>
                            <span>Ends: <?php echo htmlspecialchars($campaign['endDate']); ?></span>
                        </div>
                        <div class="campaign-actions">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="campaignId" value="<?php echo $campaign['id']; ?>">
                                <input type="hidden" name="action" value="edit">
                                <button type="button" class="campaign-btn edit-btn" onclick="openModal('edit', '<?php echo $campaign['id']; ?>', '<?php echo htmlspecialchars($campaign['title']); ?>', '<?php echo htmlspecialchars($campaign['description']); ?>', '<?php echo htmlspecialchars($campaign['goal']); ?>', '<?php echo $campaign['endDate']; ?>')">Edit</button>
                            </form>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="campaignId" value="<?php echo $campaign['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="campaign-btn delete-btn" type="submit">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Campaign Modal -->
    <div class="modal" id="campaignModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Campaign</h2>
            <form id="campaignForm" method="post">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="campaignId" id="campaignId" value="">
                <div class="form-group">
                    <label for="title">Campaign Title</label>
                    <input type="text" name="title" id="title" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="goal">Goal (Ksh)</label>
                    <input type="number" name="goal" id="goal" required>
                </div>
                <div class="form-group">
                    <label for="endDate">End Date</label>
                    <input type="date" name="endDate" id="endDate" required>
                </div>
                <button type="submit" class="submit-btn">Submit</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, id = '', title = '', description = '', goal = '', endDate = '') {
            document.getElementById('formAction').value = action;
            document.getElementById('campaignId').value = id;
            document.getElementById('title').value = title;
            document.getElementById('description').value = description;
            document.getElementById('goal').value = goal;
            document.getElementById('endDate').value = endDate;
            document.getElementById('modalTitle').innerText = action === 'edit' ? 'Edit Campaign' : 'Add New Campaign';
            document.getElementById('campaignModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('campaignModal').style.display = 'none';
        }

        document.getElementById('logoutBtn').onclick = function() {
            window.location.href = 'logout.php';
        };
    </script>
</body>
</html>
