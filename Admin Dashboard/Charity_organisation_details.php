<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
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
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    session_destroy();
    header("Location: ../admin_login.php");
    exit();
}

// Check login status
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../admin_login.php");
    exit();
}

// Get all charity organizations with their user details and campaign counts
$query = "SELECT co.*,
          u.phoneNo as phone,
          COUNT(c.id) as campaign_count,
          COALESCE(SUM(d.amount), 0) as total_donations,
          co.created_at as registration_date
          FROM charity_organizations co
          LEFT JOIN users u ON co.user_id = u.id
          LEFT JOIN campaigns c ON co.id = c.charity_id
          LEFT JOIN donations d ON c.id = d.campaign_id
          GROUP BY co.id";

$result = mysqli_query($conn, $query);
$charities = [];
while ($row = mysqli_fetch_assoc($result)) {
    $charities[] = $row;
}

// Handle AJAX requests for getting and updating charity details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_charity') {
    header('Content-Type: application/json');
    
    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            throw new Exception('Invalid charity ID');
        }
        
        // Get charity details
        $query = "SELECT * FROM charity_organizations WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
        }
        
        $result = mysqli_stmt_get_result($stmt);
        $charity = mysqli_fetch_assoc($result);
        
        if (!$charity) {
            throw new Exception("Charity not found");
        }
        
        // Ensure all necessary fields are present
        $response_data = [
            'id' => $charity['id'],
            'organization_name' => $charity['organization_name'] ?? '',
            'email' => $charity['email'] ?? '',
            'user_id' => $charity['user_id'] ?? null
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $response_data
        ]);
        
    } catch (Exception $e) {
        error_log("Error fetching charity: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_charity') {
        $id = $_POST['charity_id'];
        $name = $_POST['organization_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];

        $query = "UPDATE charity_organizations 
                 SET organization_name = ?, phone = ?, address = ?
                 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssi", $name, $phone, $address, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit();
    }
    
    if ($_POST['action'] === 'delete_charity') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        
        mysqli_begin_transaction($conn);
        try {
            // First delete associated donations
            $query = "DELETE donations FROM donations 
                     INNER JOIN campaigns ON donations.campaign_id = campaigns.id 
                     WHERE campaigns.charity_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            // Then delete associated campaigns
            $query = "DELETE FROM campaigns WHERE charity_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            // Delete from charity_organizations
            $query = "DELETE FROM charity_organizations WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            $result = mysqli_stmt_execute($stmt);

            if ($result) {
                mysqli_commit($conn);
                echo json_encode(['success' => true]);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("Delete charity error: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to delete charity: ' . $e->getMessage()
            ]);
        }
        exit();
    }
}

// Add this to handle the update_status action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $status = $data['status'];
    
    $query = "UPDATE charity_organizations SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $status, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit();
}

// Add this with your other handlers
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_charity_details') {
    $id = $_GET['id'];
    $query = "SELECT co.*,
              u.phoneNo,
              COUNT(c.id) as campaign_count,
              COALESCE(SUM(d.amount), 0) as total_donations
              FROM charity_organizations co
              LEFT JOIN users u ON co.user_id = u.id
              LEFT JOIN campaigns c ON co.id = c.charity_id
              LEFT JOIN donations d ON c.id = d.campaign_id
              WHERE co.id = ?
              GROUP BY co.id";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $charity = mysqli_fetch_assoc($result);
    
    header('Content-Type: application/json');
    echo json_encode($charity);
    exit();
}

// Update the campaign creation handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_campaign') {
    header('Content-Type: application/json');
    
    try {
        $charity_id = $_POST['charity_id'];
        $title = $_POST['campaign_name'];
        $description = $_POST['description'];
        $goal = $_POST['target_amount'];
        $endDate = $_POST['end_date'];
        $current_date = date('Y-m-d');
        
        // Validate end date
        if (strtotime($endDate) <= strtotime($current_date)) {
            throw new Exception('End date must be in the future');
        }
        
        $query = "INSERT INTO campaigns (charity_id, title, description, goal, endDate, status, createdAt) 
                  VALUES (?, ?, ?, ?, ?, 'active', NOW())";
        
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "issds", $charity_id, $title, $description, $goal, $endDate);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Failed to create campaign: ' . mysqli_error($conn));
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

// Update the delete handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action']) && $data['action'] === 'delete_charity') {
        header('Content-Type: application/json');
        
        $id = $data['id'];
        
        // First, delete the user associated with this charity
        $query = "DELETE FROM users WHERE id = (SELECT user_id FROM charity_organizations WHERE id = ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Then delete the charity organization
            $query = "DELETE FROM charity_organizations WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error deleting charity: ' . mysqli_error($conn)
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting associated user: ' . mysqli_error($conn)
            ]);
        }
        exit();
    }
}

// Update the charity update handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_charity') {
    header('Content-Type: application/json');
    ob_clean();
    
    try {
        if (empty($_POST['charity_id']) || empty($_POST['organization_name']) || 
            empty($_POST['email']) || empty($_POST['phoneNo'])) {
            throw new Exception('All fields are required');
        }
        
        $charity_id = (int)$_POST['charity_id'];
        $organization_name = mysqli_real_escape_string($conn, $_POST['organization_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phoneNo = mysqli_real_escape_string($conn, $_POST['phoneNo']);
        
        mysqli_begin_transaction($conn);
        
        // Get user_id from charity_organizations
        $query = "SELECT user_id FROM charity_organizations WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "i", $charity_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $charity = mysqli_fetch_assoc($result);
        
        if (!$charity) {
            throw new Exception('Charity not found');
        }
        
        // Update charity_organizations table
        $query = "UPDATE charity_organizations SET 
                 organization_name = ?,
                 email = ?
                 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "ssi", $organization_name, $email, $charity_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_error($conn));
        }
        
        // Update users table with the correct column names
        $query = "UPDATE users SET 
                 email = ?,
                 phoneNo = ?
                 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "ssi", $email, $phoneNo, $charity['user_id']);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_error($conn));
        }
        
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'Organization updated successfully'
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Connect - Charity Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Donor Dashboard/donor.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    <!-- <script src="auth-check.js"></script> -->
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
                <a href="Charity_organisation_details.php" class="nav-link active" data-page="charities">
                    <i class="fas fa-building"></i>
                    <span>Charities</span>
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
            <h1>Charity Organizations</h1>
            
            <div class="table-container">
                <table class="charity-table">
                    <thead>
                        <tr>
                            <th>Organization Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registration Date</th>
                            <th>Total Campaigns</th>
                            <th>Total Donations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($charities as $charity): ?>
                        <tr data-id="<?php echo $charity['id']; ?>">
                            <td><?php echo htmlspecialchars($charity['organization_name']); ?></td>
                            <td><?php echo htmlspecialchars($charity['email']); ?></td>
                            <td><?php echo htmlspecialchars($charity['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($charity['created_at'])); ?></td>
                            <td><?php echo $charity['campaign_count']; ?></td>
                            <td>KSh <?php echo number_format($charity['total_donations'], 2); ?></td>
                            <td class="actions">
                                <button class="btn btn-primary" onclick="editCharity(<?php echo $charity['id']; ?>, 
                                    '<?php echo htmlspecialchars($charity['organization_name']); ?>', 
                                    '<?php echo htmlspecialchars($charity['email']); ?>',
                                    '<?php echo htmlspecialchars($charity['phoneNo']); ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-delete" onclick="deleteCharity(<?php echo $charity['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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

    <!-- View Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close-view">&times;</span>
            <h2>Charity Organization Details</h2>
            <div class="charity-details">
                <div class="detail-group">
                    <label>Organization Name:</label>
                    <p id="viewName"></p>
                </div>
                <div class="detail-group">
                    <label>Email:</label>
                    <p id="viewEmail"></p>
                </div>
                <div class="detail-group">
                    <label>Phone Number:</label>
                    <p id="viewPhone"></p>
                </div>
                <div class="detail-group">
                    <label>Registration Date:</label>
                    <p id="viewRegistrationDate"></p>
                </div>
                <div class="detail-group">
                    <label>Total Campaigns:</label>
                    <p id="viewCampaigns"></p>
                </div>
                <div class="detail-group">
                    <label>Total Donations:</label>
                    <p id="viewDonations"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Organization Details</h5>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editCharityForm">
                    <input type="hidden" id="editCharityId" name="charity_id">
                    
                    <div class="form-group">
                        <label for="organizationName">Organization Name:</label>
                        <input type="text" id="organizationName" name="organization_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phoneNo">Phone Number:</label>
                        <input type="text" id="phoneNo" name="phoneNo" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .charity-details-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 20px;
        }

        .charity-profile {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .profile-image {
            font-size: 64px;
            color: #4a90e2;
            margin-right: 20px;
        }

        .profile-info h2 {
            margin: 0;
            color: #333;
        }

        .profile-info p {
            margin: 5px 0;
            color: #666;
        }

        .charity-details, .charity-verification {
            margin-bottom: 30px;
        }

        .details-row {
            display: flex;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .details-label {
            flex: 0 0 200px;
            font-weight: 600;
            color: #555;
        }

        .details-value {
            flex: 1;
            color: #333;
        }

        .document-list {
            margin-top: 15px;
        }

        .document-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            margin: 5px 0;
            border-radius: 4px;
        }

        .document-item i {
            margin-right: 10px;
            color: #dc3545;
        }

        .document-item span {
            flex: 1;
        }

        .btn-view {
            padding: 5px 15px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-view:hover {
            background: #357abd;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .status-active {
            background: #28a745;
            color: white;
        }

        .status-suspended {
            background: #ffc107;
            color: #000;
        }

        .status-banned {
            background: #dc3545;
            color: white;
        }

        .table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 20px;
            overflow-x: auto;
        }

        .charity-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .charity-table th,
        .charity-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .charity-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .charity-table tr:hover {
            background-color: #f8f9fa;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }

        .btn-view {
            background-color: #4a90e2;
        }

        .btn-edit {
            background-color: #28a745;
        }

        .btn-delete {
            background-color: #dc3545;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            max-width: 500px;
            border-radius: 5px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-header {
            padding-bottom: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .modal-footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewModal = document.getElementById('viewModal');
            const editModal = document.getElementById('editModal');
            
            // View Charity Details
            window.viewCharityDetails = function(id) {
                fetch(`?action=get_charity_details&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('viewName').textContent = data.organization_name;
                        document.getElementById('viewEmail').textContent = data.email;
                        document.getElementById('viewPhone').textContent = data.phoneNo || 'N/A';
                        document.getElementById('viewRegistrationDate').textContent = 
                            new Date(data.created_at).toLocaleDateString();
                        document.getElementById('viewCampaigns').textContent = data.campaign_count;
                        document.getElementById('viewDonations').textContent = 
                            `KSh ${parseFloat(data.total_donations).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            })}`;
                        viewModal.style.display = "block";
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error fetching charity details');
                    });
            }

            // Edit Charity
            window.editCharity = function(id, name, email, phoneNo) {
                document.getElementById('editCharityId').value = id;
                document.getElementById('organizationName').value = name;
                document.getElementById('email').value = email;
                document.getElementById('phoneNo').value = phoneNo;
                
                // Show the modal/form
                $('#editModal').modal('show'); // If using Bootstrap modal
            }

            // Delete Charity
            window.deleteCharity = function(id) {
                if (confirm('Are you sure you want to delete this charity organization? This action cannot be undone.')) {
                    fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete_charity',
                            id: id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Charity organization deleted successfully!');
                            window.location.reload();
                        } else {
                            alert(data.message || 'Error deleting charity organization');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting charity organization');
                    });
                }
            }

            // Close button functionality
            document.querySelector('.close').addEventListener('click', function() {
                document.getElementById('editModal').style.display = 'none';
            });

            // Click outside modal to close
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('editModal');
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });

            // Form submission
            document.getElementById('editCharityForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'update_charity');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Raw server response:', text);
                        throw new Error('Invalid server response');
                    }
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating organization: ' + error.message);
                });
            });

            // Handle campaign form submission
            const addCampaignForm = document.getElementById('addCampaignForm');
            if (addCampaignForm) {
                addCampaignForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Validate form data
                    const endDate = new Date(this.end_date.value);
                    const today = new Date();
                    
                    if (endDate <= today) {
                        alert('End date must be in the future');
                        return;
                    }
                    
                    const formData = new FormData(this);
                    formData.append('action', 'create_campaign');
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Campaign created successfully!');
                            location.reload();
                        } else {
                            alert('Error creating campaign: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error creating campaign. Please try again.');
                    });
                });
            }

            // Close modals when clicking outside
            window.onclick = function(event) {
                if (event.target == editModal || event.target == viewModal) {
                    editModal.style.display = "none";
                    viewModal.style.display = "none";
                }
            }

            // Close buttons for modals
            document.querySelectorAll('.close, .close-view').forEach(button => {
                button.onclick = function() {
                    editModal.style.display = "none";
                    viewModal.style.display = "none";
                }
            });

            // Close button
            const closeBtn = document.querySelector('.close');
            if (closeBtn) {
                closeBtn.onclick = function() {
                    document.getElementById('editModal').style.display = "none";
                }
            }

            // Click outside modal
            window.onclick = function(event) {
                const modal = document.getElementById('editModal');
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        });
    </script>
</body>
</html>
