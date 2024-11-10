<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'donateconnect';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

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
    <script src="auth-check.js"></script>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Admin Dashboard</div>
        </div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link" data-page="home">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="campaigns.php" class="nav-link active" data-page="campaigns">
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

    <div class="main-content">
        <div class="top-bar">
            <div class="user-info">
                <i class="fas fa-circle-user"></i>
                <span class="user-name" id="username"></span>
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
                <tbody id="campaignsTableBody">
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($campaign = mysqli_fetch_assoc($result)) {
                            // Calculate progress
                            $progress = 0;
                            if ($campaign['goal'] > 0) {
                                $donation_query = "SELECT COALESCE(SUM(amount), 0) as total 
                                                 FROM donations 
                                                 WHERE title = ?";
                                
                                $stmt = mysqli_prepare($conn, $donation_query);
                                if ($stmt) {
                                    mysqli_stmt_bind_param($stmt, 's', $campaign['title']);
                                    mysqli_stmt_execute($stmt);
                                    $donation_result = mysqli_stmt_get_result($stmt);
                                    $donation_data = mysqli_fetch_assoc($donation_result);
                                    $current_amount = $donation_data['total'];
                                    $progress = ($current_amount / $campaign['goal']) * 100;
                                    mysqli_stmt_close($stmt);
                                } else {
                                    $current_amount = 0;
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
                                    <div><?php echo number_format($progress, 1); ?>% 
                                         (Ksh <?php echo number_format($current_amount ?? 0, 2); ?>)</div>
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
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='7'>No campaigns found</td></tr>";
                    }
                    ?>
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
        // Update campaign status via AJAX
        function toggleCampaignStatus(id) {
            showModal(
                'Confirm Status Change',
                'Are you sure you want to change the status of this campaign?',
                () => {
                    fetch('campaign_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'toggle_status',
                            campaign_id: id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error updating campaign status');
                        }
                    });
                }
            );
        }

        // Delete campaign via AJAX
        function deleteCampaign(id) {
            showModal(
                'Confirm Delete',
                'Are you sure you want to delete this campaign? This action cannot be undone.',
                () => {
                    fetch('campaign_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            campaign_id: id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting campaign');
                        }
                    });
                }
            );
        }

        // Modal functionality
        const modal = document.getElementById('confirmationModal');
        const modalClose = document.querySelector('.modal-close');
        let currentActionCallback = null;

        function showModal(title, message, callback) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            currentActionCallback = callback;
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
            currentActionCallback = null;
        }

        modalClose.onclick = closeModal;
        window.onclick = (event) => {
            if (event.target === modal) {
                closeModal();
            }
        }

        document.getElementById('confirmButton').onclick = () => {
            if (currentActionCallback) {
                currentActionCallback();
            }
            closeModal();
        };

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            // Auth check
            const isLoggedIn = localStorage.getItem('isLoggedIn');
            if (!isLoggedIn) {
                window.location.href = 'campaigns.php';
                return;
            }

            // Set username
            const username = localStorage.getItem('username');
            document.getElementById('username').textContent = username;

            // Setup logout
            document.getElementById('logoutBtn').addEventListener('click', () => {
                localStorage.removeItem('isLoggedIn');
                localStorage.removeItem('username');
                window.location.href = 'campaigns.php';
            });

            // Initialize campaigns table and controls
            renderCampaigns(campaigns);
            setupSearchAndFilter();
        });

        // Add the setupSearchAndFilter function if it doesn't exist
        function setupSearchAndFilter() {
            const searchBox = document.querySelector('.search-box');
            const filterSelect = document.querySelector('.filter-select');

            if (searchBox) {
                searchBox.addEventListener('input', filterCampaigns);
            }
            if (filterSelect) {
                filterSelect.addEventListener('change', filterCampaigns);
            }
        }

        function filterCampaigns() {
            // Add your filter logic here
        }
    </script>
</body>

</html>
