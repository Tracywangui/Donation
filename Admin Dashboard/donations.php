<?php
require_once('../db.php');

// Modified query to match your database structure
$query = "SELECT 
            d.id,
            d.amount,
            d.created_at,
            d.status,
            d.reference,
            d.phone,
            d.email,
            c.title AS campaign_name,
            c.goal AS campaign_goal,
            c.status AS campaign_status,
            co.organization_name AS charity_name,
            u.firstname,
            u.lastname,
            u.email AS user_email
        FROM 
            donations d
            LEFT JOIN campaigns c ON d.campaign_id = c.id
            LEFT JOIN charity_organizations co ON c.charity_id = co.id
            LEFT JOIN donors dn ON d.donor_id = dn.id
            LEFT JOIN users u ON dn.user_id = u.id
        ORDER BY 
            d.created_at DESC";

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
    <title>Donor Connect - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Donor Dashboard/donor.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    <script src="auth-check.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="your-custom-script.js" defer></script>
    <style>
        .donations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            table-layout: fixed;
        }

        .donations-table th,
        .donations-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .donations-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .amount {
            font-family: monospace;
            font-weight: 500;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-completed {
            background-color: #e6f4ea;
            color: #1e7e34;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .status-failed {
            background-color: #fde7e9;
            color: #d32f2f;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 20px !important;
        }

        .donation-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-box,
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-box {
            flex: 1;
            max-width: 300px;
        }

        .export-btn {
            padding: 8px 16px;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 140px;
            position: relative;
            pointer-events: auto;
        }

        .export-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .export-btn:hover {
            background-color: #2e59d9;
        }
    </style>
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
                <a href="donations.php" class="nav-link active" data-page="donations">
                    <i class="fas fa-gift"></i>
                    <span>Donations</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="Charity_organisation_details.html" class="nav-link" data-page="charities">
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
            <h1>Donations</h1>

            <div class="donation-controls">
                <input type="text" class="search-box" placeholder="Search by donor or charity...">
                <select class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
                <button class="export-btn">
                    <i class="fas fa-file-pdf"></i>
                    Export as PDF
                </button>
            </div>

            <table class="donations-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Donor Name</th>
                        <th>Donor Email</th>
                        <th>Campaign</th>
                        <th>Charity</th>
                        <th>Amount</th>
                        <th>Phone</th>
                        <th>Reference</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $date = new DateTime($row['created_at']);
                            $donor_name = $row['firstname'] . ' ' . $row['lastname'];
                            $status_class = strtolower($row['status']);
                            ?>
                            <tr>
                                <td><?php echo $date->format('Y-m-d'); ?></td>
                                <td><?php echo $date->format('H:i'); ?></td>
                                <td><?php echo htmlspecialchars($donor_name); ?></td>
                                <td><?php echo htmlspecialchars($row['user_email']); ?></td>
                                <td><?php echo htmlspecialchars($row['campaign_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['charity_name']); ?></td>
                                <td class="amount">KSh <?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['reference']); ?></td>
                                <td><span class="status-badge status-<?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="10" class="no-data">No donations found</td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Remove any existing event listeners first
            const exportBtn = document.querySelector('.export-btn');
            if (exportBtn) {
                exportBtn.replaceWith(exportBtn.cloneNode(true));
                const newExportBtn = document.querySelector('.export-btn');
                newExportBtn.addEventListener('click', exportToPDF);
            }

            // Search functionality
            const searchBox = document.querySelector('.search-box');
            if (searchBox) {
                searchBox.value = ''; // Clear search box on page load
                searchBox.addEventListener('input', debounce(function() {
                    const searchTerm = this.value.toLowerCase().trim(); // Get the search term
                    const rows = document.querySelectorAll('.donations-table tbody tr');

                    rows.forEach(row => {
                        const donor = row.children[2]?.textContent.toLowerCase() || ''; // Donor Name
                        const charity = row.children[5]?.textContent.toLowerCase() || ''; // Charity Name
                        
                        // Debugging: Log the values being compared
                        console.log(`Searching for: "${searchTerm}", Donor: "${donor}", Charity: "${charity}"`);

                        // Check if the row should be displayed
                        if (donor.includes(searchTerm) || charity.includes(searchTerm)) {
                            row.style.display = ''; // Show row
                        } else {
                            row.style.display = 'none'; // Hide row
                        }
                    });
                }, 300));
            }

            // Filter functionality
            const filterSelect = document.querySelector('.filter-select');
            if (filterSelect) {
                filterSelect.addEventListener('change', function() {
                    const filterValue = this.value.toLowerCase();
                    const rows = document.querySelectorAll('.donations-table tbody tr');

                    rows.forEach(row => {
                        const status = row.querySelector('.status-badge')?.textContent.toLowerCase();
                        row.style.display = !filterValue || status === filterValue ? '' : 'none';
                    });
                });
            }
        });

        // Debounce function to prevent too many rapid executions
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // PDF Export function
        function exportToPDF() {
            try {
                // Create new jsPDF instance
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                // Add title to the PDF
                doc.setFontSize(16);
                doc.text('Donation History Report', 14, 15);
                doc.setFontSize(11);
                doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 14, 25);

                // Get table data
                const table = document.querySelector('.donations-table');
                if (!table) {
                    console.error('Table not found');
                    return;
                }

                const rows = Array.from(table.querySelectorAll('tbody tr'));
                
                // Prepare data for PDF
                const headers = [['Date', 'Time', 'Donor Name', 'Email', 'Campaign', 'Charity', 'Amount', 'Phone', 'Reference', 'Status']];
                
                const data = rows.map(row => {
                    if (!row.classList.contains('no-data')) {
                        return Array.from(row.children).map(cell => cell.textContent.trim());
                    }
                    return null;
                }).filter(row => row !== null);

                // Generate the table in PDF with error handling
                doc.autoTable({
                    head: headers,
                    body: data,
                    startY: 35,
                    theme: 'grid',
                    styles: {
                        fontSize: 8,
                        cellPadding: 3,
                        overflow: 'linebreak'
                    },
                    headStyles: {
                        fillColor: [78, 115, 223],
                        fontSize: 9,
                        fontStyle: 'bold',
                        halign: 'center'
                    },
                    columnStyles: {
                        0: { cellWidth: 20 }, // Date
                        1: { cellWidth: 15 }, // Time
                        2: { cellWidth: 25 }, // Donor Name
                        3: { cellWidth: 35 }, // Email
                        4: { cellWidth: 25 }, // Campaign
                        5: { cellWidth: 25 }, // Charity
                        6: { cellWidth: 20 }, // Amount
                        7: { cellWidth: 20 }, // Phone
                        8: { cellWidth: 25 }, // Reference
                        9: { cellWidth: 15 }  // Status
                    },
                    margin: { top: 30 },
                    didDrawPage: function(data) {
                        // Add page number at the bottom
                        doc.setFontSize(8);
                        doc.text(
                            `Page ${doc.internal.getCurrentPageInfo().pageNumber}`,
                            doc.internal.pageSize.width - 20,
                            doc.internal.pageSize.height - 10
                        );
                    }
                });

                // Get current date for filename
                const date = new Date();
                const formattedDate = date.toISOString().split('T')[0];

                // Save the PDF
                doc.save(`donation_history_${formattedDate}.pdf`);
            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('There was an error generating the PDF. Please try again.');
            }
        }
    </script>
</body>

</html>

