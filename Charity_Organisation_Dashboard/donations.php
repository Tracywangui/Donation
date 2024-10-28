<?php
// donations.php

// Database connection
$servername = "localhost"; // Change this if your database server is different
$username = "root"; // Your MySQL username
$password = ""; // Your MySQL password
$dbname = "donateconnect"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (!isset($_SESSION['charityUsername'])) {
    // Redirect to login page if not logged in
    header("Location:donations.php");
    exit();
}

// Retrieve the username from the session
$charityUsername = $_SESSION['charityUsername'];

// Fetch donations from the database
$sql = "SELECT d.id, d.title, u.firstname AS donor, d.date, d.amount, d.description, d.status 
        FROM donations d
        JOIN users u ON d.donor_id = u.id"; // Assuming a foreign key donor_id in donations table
$result = $conn->query($sql);

$donations = [];
if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        $donations[] = $row;
    }
} else {
    $donations = [];
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DonateConnect- Donations</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="charity.css" rel="stylesheet">
    <script src="auth-check.js"></script>
    <style>
        .donations-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .filter-buttons {
            display: flex;
            gap: 1rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .donations-grid {
            display: grid;
            gap: 1.5rem;
        }

        .donation-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: start;
        }

        .donation-info h3 {
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .donation-meta {
            display: flex;
            gap: 2rem;
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .donation-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .donation-description {
            color: #666;
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-badge.accepted {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-badge.denied {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .donation-actions {
            display: flex;
            gap: 0.5rem;
        }

        .view-details-btn {
            padding: 0.5rem 1rem;
            background-color: var(--primary-light);
            color: var(--primary-color);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-details-btn:hover {
            background-color: #dbeafe;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #e5e7eb;
            margin-bottom: 1rem;
        }

        /* Sidebar styles */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #f9fafb;
            padding: 1rem;
            position: fixed;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .nav-links {
            list-style: none;
            padding: 0;
        }
        .nav-item {
            margin: 1rem 0;
        }
        .nav-link {
            text-decoration: none;
            color: #333;
            display: flex;
            align-items: center;
        }
        .nav-link.active {
            font-weight: bold;
            color: var(--primary-color);
        }
        .logout-container {
            position: absolute;
            bottom: 1rem;
            width: 100%;
            text-align: center;
        }
        .logout-btn {
            background-color: #dc2626;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            background-color: #b91c1c;
        }

        .main-content {
            margin-left: 260px; /* Offset for sidebar */
            padding: 1rem;
        }

        .top-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2rem;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-name {
            margin-left: 0.5rem;
        }
    </style>
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
                <a href="donations.php" class="nav-link active" data-page="donations">
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
                <a href="Notifications.php" class="nav-link" data-page="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <span class="notification-badge" id="notificationCount">0</span>
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
        <div class="top-bar">
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span class="user-name" id="username"><?php echo htmlspecialchars($charityUsername); ?></span> <!-- Display username from session -->
            </div>
        </div>
        <div class="content-area">
            <div class="donations-header">
                <h2>Donations</h2>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="accepted">Accepted</button>
                    <button class="filter-btn" data-filter="denied">Denied</button>
                </div>
            </div>
            <div class="donations-grid" id="donationsGrid">
                <?php if (empty($donations)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No donations found</h3>
                        <p>There are no donations matching your current filter.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($donations as $donation): ?>
                        <div class="donation-card">
                            <div class="donation-info">
                                <h3><?php echo htmlspecialchars($donation['title']); ?></h3>
                                <div class="donation-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($donation['donor']); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($donation['date']); ?></span>
                                    <span><i class="fas fa-dollar-sign"></i> <?php echo htmlspecialchars($donation['amount']); ?></span>
                                </div>
                                <p class="donation-description"><?php echo htmlspecialchars($donation['description']); ?></p>
                                <span class="status-badge <?php echo htmlspecialchars($donation['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($donation['status'])); ?>
                                </span>
                            </div>
                            <div class="donation-actions">
                                <button class="view-details-btn">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Fetching username from local storage (assuming you store it when the user logs in)
        document.getElementById('username').innerText = localStorage.getItem('username') || 'Guest';

        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', function() {
            localStorage.removeItem('username');
            // Redirect to login page
            window.location.href = '../charity_login.php';
        });

        // Filter donations functionality (this part is simplified)
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                // You would implement filtering logic here, e.g., hiding/showing donation cards
                document.querySelectorAll('.donation-card').forEach(card => {
                    const status = card.querySelector('.status-badge').innerText.toLowerCase();
                    if (filter === 'all' || status === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
