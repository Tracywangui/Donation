<?php
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = ""; // Use your database password here
$dbname = "donateconnect";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || !$_SESSION['isLoggedIn']) {
    header("Location: Notifications.php");
    exit();
}

// Get username from session
$donorUsername = $_SESSION['donorUsername'];

// Connect to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database $db :" . $e->getMessage());
}

// Fetch notifications
$query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute(['user_id' => $_SESSION['user_id']]); // Assuming user_id is stored in session
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M d, Y h:i A');
}

// Function to get notification icon based on type
function getNotificationIcon($type) {
    switch($type) {
        case 'donation':
            return 'fa-gift';
        case 'request':
            return 'fa-hand-holding-heart';
        case 'campaign':
            return 'fa-bullhorn';
        default:
            return 'fa-bell';
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['id']) && isset($data['read'])) {
        // Mark as read
        $updateQuery = "UPDATE notifications SET read = :read WHERE id = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute(['read' => $data['read'], 'id' => $data['id']]);
        echo json_encode(['success' => true]);
        exit();
    }

    if (isset($data['id'])) {
        // Delete notification
        $deleteQuery = "DELETE FROM notifications WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute(['id' => $data['id']]);
        echo json_encode(['success' => true]);
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Connect - Notifications</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <script src="auth-check.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Charity Dashboard</div>
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
                <a href="donationrequest.php" class="nav-link" data-page="requests">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <span>Donation Requests</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="donate.php" class="nav-link" data-page="requests">
                    <i class="fas fa-circle-dollar-to-slot"></i>
                    <span>Donate</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Notifications.php" class="nav-link active" data-page="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <span class="notification-badge" id="notificationCount"><?php echo count($notifications); ?></span>
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
            <div class="notifications-icon">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="topBarNotificationCount"><?php echo count($notifications); ?></span>
            </div>
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span class="user-name" id="username"><?php echo htmlspecialchars($username); ?></span>
            </div>
        </div>
        <div class="content-area">
            <div class="notifications-header">
                <h2>Notifications</h2>
                <div class="notification-actions">
                    <button class="mark-all-read-btn" id="markAllReadBtn">
                        <i class="fas fa-check-double"></i>
                        Mark all as read
                    </button>
                    <button class="clear-all-btn" id="clearAllBtn">
                        <i class="fas fa-trash"></i>
                        Clear all
                    </button>
                </div>
            </div>
            <div class="notifications-list" id="notificationsList">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No notifications</h3>
                        <p>You're all caught up! Check back later for updates.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['read'] ? 'read' : 'unread'; ?>">
                            <div class="notification-icon">
                                <i class="fas <?php echo getNotificationIcon($notification['type']); ?>"></i>
                            </div>
                            <div class="notification-content">
                                <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <span class="notification-date"><?php echo formatDate($notification['created_at']); ?></span>
                            </div>
                            <div class="notification-actions">
                                <button class="mark-read-btn" onclick="markAsRead('<?php echo $notification['id']; ?>')"
                                        <?php echo $notification['read'] ? 'disabled' : ''; ?>>
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="delete-btn" onclick="deleteNotification('<?php echo $notification['id']; ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Function to mark notification as read
        function markAsRead(notificationId) {
            // Make an AJAX request to update notification status in the database
            fetch('Notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: notificationId, read: true })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload(); // Refresh the page after updating
                }
            });
        }

        // Function to delete notification
        function deleteNotification(notificationId) {
            // Make an AJAX request to delete notification from the database
            fetch('Notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload(); // Refresh the page after deletion
                }
            });
        }

        // Logout handling
        document.getElementById('logoutBtn').addEventListener('click', () => {
            if (confirm('Are you sure you want to logout?')) {
                localStorage.clear();
                window.location.href = '../donor_login.php';
            }
        });
    </script>
</body>
</html>
