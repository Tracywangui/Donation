<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "donateconnect";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header('Location: Transactions.php'); 
    exit();
}

// Set the username from the session
$charityUsername = $_SESSION['charityUsername'];

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();

// Get notifications from the session or initialize an empty array
$notifications = $_SESSION['notifications'] ?? [];

// Count unread notifications
$unreadCount = count(array_filter($notifications, function($notification) {
    return !$notification['read'];
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Connect - Notifications</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="charity.css" rel="stylesheet">
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
                <a href="CharityOrganisation.php" class="nav-link" data-page="home">
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
                <a href="Transactions.php" class="nav-link" data-page="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Transactions</span>
                    <span class="notification-badge" id="notificationCount"><?php echo $unreadCount; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Notifications.php" class="nav-link active" data-page="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <span class="notification-badge" id="notificationCount"><?php echo $unreadCount; ?></span>
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
                <span class="notification-badge" id="topBarNotificationCount"><?php echo $unreadCount; ?></span>
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
                                <span class="notification-date"><?php echo formatDate($notification['date']); ?></span>
                            </div>
                            <div class="notification-actions">
                                <button class="mark-read-btn" onclick="markAsRead('<?php echo $notification['id']; ?>')" <?php echo $notification['read'] ? 'disabled' : ''; ?>>
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
        document.addEventListener('DOMContentLoaded', () => {
            // Filter button handling
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    renderDonations(button.getAttribute('data-filter'));
                });
            });
        });

        // Function to format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-KE', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Function to render notifications
        function renderNotifications() {
            const notificationsList = document.getElementById('notificationsList');
            const notifications = JSON.parse(localStorage.getItem('notifications') || '[]');

            if (notifications.length === 0) {
                notificationsList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No notifications</h3>
                        <p>You're all caught up! Check back later for updates.</p>
                    </div>
                `;
                return;
            }

            notificationsList.innerHTML = notifications
                .sort((a, b) => new Date(b.date) - new Date(a.date))
                .map(notification => `
                    <div class="notification-item ${notification.read ? 'read' : 'unread'}">
                        <div class="notification-icon">
                            <i class="fas ${getNotificationIcon(notification.type)}"></i>
                        </div>
                        <div class="notification-content">
                            <p class="notification-message">${notification.message}</p>
                            <span class="notification-date">${formatDate(notification.date)}</span>
                        </div>
                        <div class="notification-actions">
                            <button class="mark-read-btn" onclick="markAsRead('${notification.id}')"
                                    ${notification.read ? 'disabled' : ''}>
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="delete-btn" onclick="deleteNotification('${notification.id}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
        }

        // Function to get notification icon based on type
        function getNotificationIcon(type) {
            switch(type) {
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

        // Function to mark notification as read
        function markAsRead(notificationId) {
            const notifications = JSON.parse(localStorage.getItem('notifications') || '[]');
            const updatedNotifications = notifications.map(notification =>
                notification.id === notificationId
                    ? {...notification, read: true}
                    : notification
            );
            localStorage.setItem('notifications', JSON.stringify(updatedNotifications));
            renderNotifications();
            updateNotificationCount();
        }

        // Function to delete notification
        function deleteNotification(notificationId) {
            const notifications = JSON.parse(localStorage.getItem('notifications') || '[]');
            const updatedNotifications = notifications.filter(
                notification => notification.id !== notificationId
            );
            localStorage.setItem('notifications', JSON.stringify(updatedNotifications));
            renderNotifications();
            updateNotificationCount();
        }

        // Function to update notification count
        function updateNotificationCount() {
            const notifications = JSON.parse(localStorage.getItem('notifications') || '[]');
            const unreadCount = notifications.filter(notification => !notification.read).length;
            document.getElementById('topBarNotificationCount').innerText = unreadCount;
            document.getElementById('notificationCount').innerText = unreadCount;
        }
    </script>
</body>
</html>
