<?php
session_start();
require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['charityUsername'])) {
    header("Location: ../charity_login.php");
    exit();
}

$loggedInUsername = $_SESSION['charityUsername'];

// First get the charity organization's ID
$charityQuery = "SELECT co.id as charity_id 
                 FROM charity_organizations co
                 JOIN users u ON co.user_id = u.id
                 WHERE u.username = ?";

$stmt = $conn->prepare($charityQuery);
$stmt->bind_param("s", $loggedInUsername);
$stmt->execute();
$charityResult = $stmt->get_result();
$charityData = $charityResult->fetch_assoc();

if (!$charityData) {
    die("Charity organization not found");
}

$charityId = $charityData['charity_id'];

// Now fetch donations for this charity
$sql = "SELECT d.id, 
               d.amount,
               d.created_at,
               d.status,
               u.firstname AS donor_firstname,
               u.lastname AS donor_lastname,
               u.email AS donor_email,
               u.phoneNo AS donor_phone,
               d.reference,
               d.stripe_payment_status,
               c.title AS campaign_name
        FROM donations d
        LEFT JOIN campaigns c ON d.campaign_id = c.id
        LEFT JOIN donors dn ON d.donor_id = dn.user_id
        LEFT JOIN users u ON dn.user_id = u.id
        WHERE c.charity_id = ?
        ORDER BY d.created_at DESC";

        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $charityId);
$stmt->execute();
$result = $stmt->get_result();
$donations = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $donations[] = [
            'id' => $row['id'],
            'amount' => $row['amount'],
            'donor_name' => $row['donor_firstname'] . ' ' . $row['donor_lastname'],
            'created_at' => $row['created_at'],
            'status' => $row['status'],
            'campaign_title' => $row['campaign_name'] ?? 'General Donation',
            'reference' => $row['reference'],
            'payment_method' => 'Card',
            'stripe_payment_status' => $row['stripe_payment_status'],
            'phone' => $row['donor_phone'] ?? 'N/A',
            'email' => $row['donor_email'] ?? 'N/A'
        ];
        
    }
}

// Initialize $donations as empty array if no results
if (!isset($donations)) {
    $donations = [];
}

// Close the database connection
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

        .donations-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .donations-table th,
        .donations-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .donations-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .donations-table tr:hover {
            background-color: #f8f9fa;
        }

        .view-details-btn {
            padding: 6px 12px;
            background-color: var(--primary-light);
            color: var(--primary-color);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
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

        .status-badge.pending {
            background-color: #fef3c7;
            color: #a16207;
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
                <span class="user-name"><?php echo htmlspecialchars($loggedInUsername); ?></span>
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
                    <table class="donations-table">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Donor</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Reference</th>
                                <th>Payment Status</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donations as $donation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($donation['campaign_title']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['donor_name']); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($donation['created_at']))); ?></td>
                                    <td>Ksh <?php echo htmlspecialchars(number_format($donation['amount'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($donation['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['email']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['reference']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['stripe_payment_status']); ?></td>
                                    <td><span class="status-badge <?php echo strtolower(htmlspecialchars($donation['status'])); ?>">
                                        <?php echo ucfirst(htmlspecialchars($donation['status'])); ?>
                                    </span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', function() {
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
