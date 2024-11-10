<?php
session_start();
require_once('C:/xampp/htdocs/IS project coding/db.php');

// Fetch campaigns with charity organization details
$query = "SELECT c.*, co.organization_name, u.username 
          FROM campaigns c 
          JOIN charity_organizations co ON c.charity_id = co.id 
          JOIN users u ON co.user_id = u.id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate Connect - Admin Campaigns</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Donor Dashboard/donor.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Admin Dashboard</div>
        </div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="campaigns.php" class="nav-link active">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span>Campaigns</span>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div class="user-info">
                <i class="fas fa-circle-user"></i>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
            </div>
        </div>

        <div class="content-area">
            <h1>Campaign Management</h1>
            
            <table class="campaigns-table">
                <thead>
                    <tr>
                        <th>Campaign Name</th>
                        <th>Charity Organization</th>
                        <th>Goal Amount</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($campaign = mysqli_fetch_assoc($result)) { 
                        // Calculate progress
                        $progress = 0;
                        if ($campaign['goal'] > 0) {
                            $donation_query = "SELECT COALESCE(SUM(amount), 0) as total 
                                             FROM donations 
                                             WHERE campaign_id = ?";
                            
                            $stmt = mysqli_prepare($conn, $donation_query);
                            if ($stmt) {
                                mysqli_stmt_bind_param($stmt, 'i', $campaign['id']);
                                mysqli_stmt_execute($stmt);
                                $donation_result = mysqli_stmt_get_result($stmt);
                                $donation_data = mysqli_fetch_assoc($donation_result);
                                $current_amount = $donation_data['total'];
                                $progress = ($current_amount / $campaign['goal']) * 100;
                                mysqli_stmt_close($stmt);
                            }
                        }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($campaign['title']); ?></td>
                            <td><?php echo htmlspecialchars($campaign['organization_name']); ?></td>
                            <td>Ksh <?php echo number_format($campaign['goal'], 2); ?></td>
                            <td>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <div><?php echo number_format($progress, 1); ?>%</div>
                            </td>
                            <td><span class="status-badge status-<?php echo strtolower($campaign['status']); ?>">
                                <?php echo ucfirst($campaign['status']); ?></span></td>
                            <td><?php echo $campaign['endDate']; ?></td>
                            <td class="campaign-actions">
                                <button class="btn btn-view" onclick="viewCampaign(<?php echo $campaign['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-toggle-status" 
                                        onclick="toggleCampaignStatus(<?php echo $campaign['id']; ?>)"
                                        title="<?php echo $campaign['status'] === 'active' ? 'Deactivate' : 'Activate'; ?> Campaign">
                                    <i class="fas <?php echo $campaign['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                </button>
                                <button class="btn btn-delete" onclick="deleteCampaign(<?php echo $campaign['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2 id="modalTitle">Confirm Action</h2>
            <p id="modalMessage">Are you sure you want to proceed with this action?</p>
            <div class="modal-actions">
                <button class="btn" onclick="closeModal()">Cancel</button>
                <button id="confirmButton" class="btn btn-delete">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup logout handler
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetch('../logout.php', {
                        method: 'POST',
                        credentials: 'same-origin'
                    })
                    .then(() => {
                        window.location.href = '../login.php';
                    })
                    .catch(error => console.error('Logout error:', error));
                });
            }

            // Setup campaign action handlers
            function handleCampaignAction(action, campaignId, successMessage) {
                fetch('campaign_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: action,
                        campaign_id: campaignId
                    }),
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(successMessage);
                        window.location.reload();
                    } else {
                        alert(data.message || 'Action failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }

            // Campaign status toggle
            window.toggleCampaignStatus = function(id) {
                showModal(
                    'Confirm Status Change',
                    'Are you sure you want to change the status of this campaign?',
                    () => handleCampaignAction('toggle_status', id, 'Status updated successfully')
                );
            }

            // Campaign deletion
            window.deleteCampaign = function(id) {
                showModal(
                    'Confirm Delete',
                    'Are you sure you want to delete this campaign? This action cannot be undone.',
                    () => handleCampaignAction('delete', id, 'Campaign deleted successfully')
                );
            }

            // Modal functionality
            const modal = document.getElementById('confirmationModal');
            if (!modal) return;

            const closeModal = () => {
                modal.style.display = 'none';
                window.currentActionCallback = null;
            };

            window.showModal = (title, message, callback) => {
                const modalTitle = document.getElementById('modalTitle');
                const modalMessage = document.getElementById('modalMessage');
                if (modalTitle) modalTitle.textContent = title;
                if (modalMessage) modalMessage.textContent = message;
                window.currentActionCallback = callback;
                modal.style.display = 'block';
            };

            // Modal close button
            const modalClose = modal.querySelector('.modal-close');
            if (modalClose) {
                modalClose.onclick = closeModal;
            }

            // Close modal on outside click
            window.onclick = (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            };

            // Confirm button handler
            const confirmButton = document.getElementById('confirmButton');
            if (confirmButton) {
                confirmButton.onclick = () => {
                    if (window.currentActionCallback) {
                        window.currentActionCallback();
                    }
                    closeModal();
                };
            }
        });
    </script>
</body>

</html>
