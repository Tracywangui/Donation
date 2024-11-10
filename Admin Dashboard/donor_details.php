<?php
session_start();
require_once('C:/xampp/htdocs/IS project coding/db.php');

// Fetch donors with user information
$query = "SELECT d.id as donor_id, d.user_id, d.created_at, 
          u.firstname, u.lastname, u.email, u.phoneNo, u.username
          FROM donors d
          JOIN users u ON d.user_id = u.id
          ORDER BY d.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate Connect - Donor Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Donor Dashboard/donor.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    <style>
        .content-area {
            padding: 30px;
            background: #f8f9fa;
        }

        .content-area h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2em;
            font-weight: 600;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .data-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            color: #444;
        }

        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .data-table .actions {
            white-space: nowrap;
        }

        .btn-action {
            padding: 8px;
            border: none;
            border-radius: 4px;
            margin: 0 2px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background-color: #3498db;
            color: white;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }

        /* Modal Styles */
        .modal {
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .modal-footer {
            margin-top: 25px;
            text-align: right;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
            margin-right: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
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
                <a href="donor_details.php" class="nav-link active">
                    <i class="fas fa-users"></i>
                    <span>Donors</span>
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
        <div class="content-area">
            <h1>Donor Details</h1>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Donor ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Username</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($result) > 0) {
                            while ($donor = mysqli_fetch_assoc($result)) { 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($donor['donor_id']); ?></td>
                                <td><?php echo htmlspecialchars($donor['firstname'] . ' ' . $donor['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($donor['email']); ?></td>
                                <td><?php echo htmlspecialchars($donor['phoneNo']); ?></td>
                                <td><?php echo htmlspecialchars($donor['username']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($donor['created_at'])); ?></td>
                                <td class="actions">
                                    <button class="btn btn-primary edit-btn" onclick="editDonor(<?php echo $donor['donor_id']; ?>, <?php echo $donor['user_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-delete" onclick="deleteDonor(<?php echo $donor['donor_id']; ?>, <?php echo $donor['user_id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="7" class="no-data">No donors found</td>
                            </tr>
                        <?php 
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Donor</h2>
            <form id="editDonorForm">
                <input type="hidden" id="editDonorId" name="donor_id">
                <input type="hidden" id="editUserId" name="user_id">
                
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="phoneNo">Phone Number</label>
                    <input type="text" id="phoneNo" name="phoneNo" class="form-control" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this donor? This action cannot be undone.</p>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let currentDonorId = null;
        let currentUserId = null;

        function editDonor(donorId, userId) {
            currentDonorId = donorId;
            currentUserId = userId;
            
            console.log('Fetching donor details for:', donorId, userId);

            fetch(`get_donor_details.php?donor_id=${donorId}&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Received data:', data);
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    document.getElementById('editDonorId').value = donorId;
                    document.getElementById('editUserId').value = userId;
                    document.getElementById('firstname').value = data.firstname;
                    document.getElementById('lastname').value = data.lastname;
                    document.getElementById('email').value = data.email;
                    document.getElementById('phoneNo').value = data.phoneNo;
                    
                    document.getElementById('editModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error fetching donor details: ' + error.message);
                });
        }

        document.getElementById('editDonorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');

            const formData = new FormData(this);
            
            // Debug log
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            fetch('handle_donor_updates.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Response:', data);
                
                if (data.success) {
                    alert('Donor updated successfully');
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Update failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating donor: ' + error.message);
            });
        });

        function deleteDonor(donorId, userId) {
            console.log('Delete function called with:', donorId, userId); // Debug log
            
            if (confirm('Are you sure you want to delete this donor?')) {
                fetch('handle_donor_updates.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        donor_id: donorId,
                        user_id: userId
                    })
                })
                .then(response => {
                    console.log('Response received:', response); // Debug
                    
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response:', data); // Debug log
                    
                    if (data.success) {
                        alert('Donor deleted successfully');
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'Delete failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting donor: ' + error.message);
                });
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }
    </script>
</body>

</html>
