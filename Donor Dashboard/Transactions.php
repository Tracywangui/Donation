<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is not logged in
if (!isset($_SESSION['donorUsername'])) {
    header('Location: ../donor_login.php');
    exit();
}

// Get the donor's username and ID from session
$donorUsername = $_SESSION['donorUsername'];
$donor_id = $_SESSION['donor_id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "donateconnect";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get donor's transactions
$query = "SELECT t.*, c.organization_name as charity_name 
          FROM transactions t
          LEFT JOIN charity_organizations c ON t.charity_id = c.id
          WHERE t.donor_id = ?
          ORDER BY t.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Connect - Transactions</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
    
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Donor Dashboard</div>
        </div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="donor_dashboard.php" class="nav-link" data-page="home">
                    <i class="fas fa-house"></i>
                    <span>Home</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="transactions.php" class="nav-link active" data-page="transactions">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Transactions</span>
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
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span class="user-name"><?php echo htmlspecialchars($donorUsername); ?></span>
            </div>
        </div>

        <div class="content-area">
            <div class="transactions-header">
                <h2>Transactions</h2>
                <div class="filters">
                    <button class="filter-btn active" onclick="filterTransactions('all')">All</button>
                    <button class="filter-btn" onclick="filterTransactions('pending')">Pending</button>
                    <button class="filter-btn" onclick="filterTransactions('completed')">Completed</button>
                    <input type="text" class="search-input" placeholder="Search transactions..." oninput="searchTransactions()">
                </div>
            </div>

            <div class="table-responsive">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th width="15%">Date</th>
                            <th width="25%">Charity</th>
                            <th width="15%">Amount (KSH)</th>
                            <th width="15%">Payment Method</th>
                            <th width="15%">Status</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td class="date-cell"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                <td class="charity-cell"><?php echo htmlspecialchars($row['charity_name'] ?? 'N/A'); ?></td>
                                <td class="amount-cell">KSH <?php echo number_format($row['amount'], 2); ?></td>
                                <td class="payment-cell"><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                <td class="status-cell">
                                    <span class="status-badge <?php echo strtolower($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <button class="view-btn" onclick="viewTransactionDetails(<?php echo $row['id']; ?>)">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="transactionDetails"></div>
            <div class="modal-actions">
                <button onclick="downloadInvoice()" class="download-btn">
                    <i class="fas fa-download"></i> Download Invoice
                </button>
            </div>
        </div>
    </div>

    <style>
    .content-area {
        padding: 24px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .transactions-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .transactions-header h2 {
        font-size: 24px;
        color: #1a1a1a;
        margin: 0;
    }

    .filters {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .filter-btn {
        padding: 8px 16px;
        border: 1px solid #e0e0e0;
        border-radius: 20px;
        background: white;
        color: #666;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .filter-btn.active {
        background-color: #1a73e8;
        color: white;
        border-color: #1a73e8;
    }

    .search-input {
        padding: 8px 16px;
        border: 1px solid #e0e0e0;
    }

    .view-btn {
        padding: 6px 12px;
        background-color: #1a73e8;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .view-btn:hover {
        background-color: #1557b0;
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
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: 8px;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: black;
    }

    .modal-actions {
        margin-top: 20px;
        text-align: right;
    }

    .download-btn {
        padding: 8px 16px;
        background-color: #1a73e8;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .download-btn:hover {
        background-color: #1557b0;
    }

    .transactions-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 12px; /* Adds vertical space between rows */
        margin: 20px 0;
    }

    .transactions-table th,
    .transactions-table td {
        padding: 20px 24px; /* Increased padding */
        text-align: left;
        background: white;
    }

    .transactions-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #444;
        border-bottom: 2px solid #e0e0e0;
    }

    .transactions-table tr {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease;
    }

    .transactions-table tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .transactions-table td {
        border: none;
        background-color: white;
    }

    /* Cell-specific styling */
    .date-cell {
        font-weight: 500;
        color: #444;
    }

    .charity-cell {
        font-weight: 500;
        color: #1a73e8;
    }

    .amount-cell {
        font-weight: 600;
        color: #2e7d32;
    }

    .payment-cell {
        color: #666;
    }

    .status-cell {
        text-align: center;
    }

    .action-cell {
        text-align: center;
    }

    .status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        display: inline-block;
        min-width: 100px;
        text-align: center;
    }

    .status-badge.pending {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-badge.completed {
        background-color: #d4edda;
        color: #155724;
    }

    .view-btn {
        padding: 8px 20px;
        background-color: #1a73e8;
        color: white;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .view-btn:hover {
        background-color: #1557b0;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Header styling */
    .transactions-header {
        margin-bottom: 32px;
        padding: 0 24px;
    }

    .filters {
        gap: 16px;
    }

    .filter-btn {
        padding: 10px 24px;
        font-weight: 500;
    }

    .search-input {
        padding: 10px 24px;
        width: 300px;
        border-radius: 20px;
        border: 1px solid #e0e0e0;
        font-size: 14px;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .transactions-table th,
        .transactions-table td {
            padding: 16px 20px;
        }
        
        .search-input {
            width: 250px;
        }
    }

    @media (max-width: 768px) {
        .transactions-table {
            border-spacing: 0 8px;
        }

        .transactions-table th,
        .transactions-table td {
            padding: 12px 16px;
        }
        
        .status-badge {
            padding: 6px 12px;
            min-width: 80px;
        }
        
        .view-btn {
            padding: 6px 16px;
        }
    }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

    <script>
    let currentTransactionData = null;
    let currentFilter = 'all';

    function viewTransactionDetails(transactionId) {
        const modal = document.getElementById('transactionModal');
        const detailsDiv = document.getElementById('transactionDetails');
        detailsDiv.innerHTML = 'Loading...';
        modal.style.display = "block";

        fetch(`./get_transaction_details.php?id=${transactionId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(data);
                if (data.error) {
                    throw new Error(data.error);
                }

                // Assuming you want the first transaction in the array
                const transaction = data[0]; // Access the first transaction

                // Check if each property exists before accessing it
                const date = transaction.transaction_date || 'N/A';
                const statusClass = transaction.status ? transaction.status.toLowerCase() : 'unknown';
                const donorEmail = transaction.donor_email || 'N/A'; // Ensure this field exists in your response
                const paymentMethod = transaction.payment_method || 'N/A';
                const charityName = transaction.charity_name || 'N/A';
                const amount = transaction.amount ? `KSH ${parseFloat(transaction.amount).toFixed(2)}` : 'KSH 0.00';

                detailsDiv.innerHTML = `
                    <div class="invoice-header">
                        <h2>Transaction Details</h2>
                        <p class="invoice-number">Invoice #: INV-${transaction.id || 'undefined'}</p>
                    </div>
                    <div class="invoice-details">
                        <div class="detail-row">
                            <p><strong>Date:</strong> ${date}</p>
                            <p><strong>Status:</strong> <span class="status-badge ${statusClass}">${transaction.status || 'Unknown'}</span></p>
                        </div>
                        <div class="detail-row">
                            <p><strong>Donor Email:</strong> ${donorEmail}</p>
                            <p><strong>Payment Method:</strong> ${paymentMethod}</p>
                        </div>
                        <div class="detail-row">
                            <p><strong>Charity:</strong> ${charityName}</p>
                        </div>
                        <div class="amount-section">
                            <h3>Amount</h3>
                            <p class="amount">${amount}</p>
                        </div>
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
                detailsDiv.innerHTML = `
                    <div class="error-message">
                        Error loading transaction details: ${error.message}
                        <br>
                        <small>Please try again or contact support if the problem persists.</small>
                    </div>
                `;
            });
    }

    // Close modal when clicking the X
    document.querySelector('.close').onclick = function() {
        document.getElementById('transactionModal').style.display = "none";
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('transactionModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    function downloadInvoice() {
        if (!currentTransactionData) return;

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        doc.setFontSize(20);
        doc.text('DonateConnect', 105, 20, { align: 'center' });
        
        doc.setFontSize(16);
        doc.text('DONATION INVOICE', 105, 35, { align: 'center' });

        doc.setFontSize(12);
        doc.text(`Invoice Number: INV-${currentTransactionData.id}`, 20, 50);
        doc.text(`Date: ${currentTransactionData.created_at}`, 20, 60);
        
        doc.text('From:', 20, 75);
        doc.text(currentTransactionData.donor_email, 20, 85);
        
        doc.text('To:', 120, 75);
        doc.text(currentTransactionData.charity_name, 120, 85);

        const tableData = [
            ['Description', 'Amount'],
            ['Donation', `KSH ${currentTransactionData.amount}`]
        ];

        doc.autoTable({
            startY: 100,
            head: [['Description', 'Details']],
            body: [
                ['Date', currentTransactionData.created_at],
                ['Charity', currentTransactionData.charity_name],
                ['Amount', `KSH ${currentTransactionData.amount}`],
                ['Payment Method', currentTransactionData.payment_method],
                ['Status', currentTransactionData.status]
            ],
            theme: 'grid',
            headStyles: { fillColor: [26, 115, 232] },
            margin: { top: 100 }
        });

        const pageHeight = doc.internal.pageSize.height;
        doc.text('Thank you for your donation!', 105, pageHeight - 30, { align: 'center' });
        doc.text('DonateConnect', 105, pageHeight - 20, { align: 'center' });

        doc.save(`donation-invoice-${currentTransactionData.id}.pdf`);
    }

    function filterTransactions(filter) {
        currentFilter = filter;
        fetchTransactions();
    }

    function searchTransactions() {
        const searchTerm = document.querySelector('.search-input').value.toLowerCase();
        fetchTransactions(searchTerm);
    }

    function fetchTransactions(searchTerm = '') {
        const url = `./get_transaction_details.php?filter=${currentFilter}&search=${encodeURIComponent(searchTerm)}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateTransactionTable(data);
            })
            .catch(error => {
                console.error('Error fetching transactions:', error);
            });
    }

    function updateTransactionTable(data) {
        const tbody = document.querySelector('.transactions-table tbody');
        tbody.innerHTML = ''; // Clear existing rows

        data.forEach(row => {
            const statusClass = row.status ? row.status.toLowerCase() : 'unknown'; // Check if status is defined
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="date-cell">${new Date(row.created_at).toLocaleDateString()}</td>
                <td class="charity-cell">${row.charity_name || 'N/A'}</td>
                <td class="amount-cell">KSH ${parseFloat(row.amount).toFixed(2)}</td>
                <td class="payment-cell">${row.payment_method || 'N/A'}</td>
                <td class="status-cell">
                    <span class="status-badge ${statusClass}">${row.status || 'Unknown'}</span>
                </td>
                <td class="action-cell">
                    <button class="view-btn" onclick="viewTransactionDetails(${row.id})">View Details</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }
    </script>

    <style>
    .invoice-header {
        border-bottom: 2px solid #eee;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .invoice-number {
        color: #666;
        font-size: 14px;
    }

    .invoice-details {
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .amount-section {
        text-align: right;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .amount {
        font-size: 24px;
        font-weight: 600;
        color: #2e7d32;
    }

    .modal-actions {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        text-align: right;
    }

    .download-btn {
        padding: 10px 20px;
        background-color: #1a73e8;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }

    .download-btn:hover {
        background-color: #1557b0;
    }
    </style>
</body>

</html>

