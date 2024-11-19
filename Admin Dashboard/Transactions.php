<?php
require_once('../db.php');

// Modified query to use correct column relationships
$query = "SELECT DISTINCT
    t.*,
    d.amount as donation_amount,
    d.campaign_id,
    d.donor_id,
    d.email as donor_email,
    d.phone,
    d.reference,
    d.status as donation_status,
    c.title as campaign_title,
    co.organization_name,
    CONCAT(u.firstname, ' ', u.lastname) as donor_name
FROM 
    transactions t
    INNER JOIN campaigns c ON t.charity_id = c.charity_id
    INNER JOIN donations d ON d.campaign_id = c.id
    INNER JOIN charity_organizations co ON t.charity_id = co.id
    LEFT JOIN donors dn ON d.donor_id = dn.id
    LEFT JOIN users u ON dn.user_id = u.id
WHERE 
    d.id IS NOT NULL
ORDER BY 
    t.created_at DESC";

$result = mysqli_query($conn, $query);

// Check if query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Connect - Admin Transactions</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Donor Dashboard/donor.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <link href="admin.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.6/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<style>
.status-pending {
            background-color: #fff3e0;
            color: #f57c00;
        }
.status-completed {
            background-color: #c8e6c9;
            color: #388e3c;
        }

.export-btn {
    padding: 8px 16px;
    background-color: #2ecc71;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.export-btn:hover {
    background-color: #27ae60;
}

.export-btn i {
    font-size: 1.1em;
}
</style>

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
                <a href="Transactions.php" class="nav-link active" data-page="transactions">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Transactions</span>
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
            <h1>Transaction History</h1>

            <div class="transaction-controls">
                <input type="text" id="searchInput" class="search-box" placeholder="Search transactions...">
                <select id="statusFilter" class="filter-select">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
                <button onclick="generatePDF()" class="export-btn">
                    <i class="fas fa-file-pdf"></i>
                    Export as PDF
                </button>
            </div>

            <div class="table-container">
                <table class="donations-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th>Donor Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Organization</th>
                            <th>Campaign</th>
                            <th>Amount</th>
                            <th>Reference</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $date = new DateTime($row['created_at']);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo $date->format('Y-m-d H:i'); ?></td>
                                    <td><?php echo htmlspecialchars($row['donor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['donor_email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['organization_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['campaign_title']); ?></td>
                                    <td>KSh <?php echo number_format($row['donation_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['reference']); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($row['donation_status']); ?>">
                                        <?php echo ucfirst($row['donation_status']); ?>
                                    </span></td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="10" class="no-data">No transactions found</td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function generatePDF() {
        // Create new jsPDF instance
        const doc = new jsPDF();
        
        // Get the table element
        const table = document.querySelector('.donations-table');
        
        doc.autoTable({
            html: '.donations-table',
            startY: 20,
            theme: 'grid',
            styles: {
                fontSize: 8
            },
            headStyles: {
                fillColor: [41, 128, 185]
            }
        });

        // Save the PDF
        doc.save('transactions.pdf');
    }

    // Test if libraries are loaded correctly
    document.addEventListener('DOMContentLoaded', function() {
        console.log('jsPDF loaded:', typeof jsPDF !== 'undefined');
        console.log('autoTable loaded:', typeof jsPDF.API.autoTable !== 'undefined');
    });
    </script>
</body>

</html>
