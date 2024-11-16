<?php
session_start();
require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['charityUsername'])) {
    header("Location: ../charity_login.php");
    exit();
}

$loggedInUsername = $_SESSION['charityUsername'];


// Fetch transactions with donor names from users table and campaign titles
$sql = "SELECT t.id, 
               t.amount,
               t.donor_id,
               t.created_at,
               t.payment_method,
               t.status,
               CONCAT(u.firstname, ' ', u.lastname) as donor_name,
               c.title as campaign_name
        FROM transactions t
        LEFT JOIN donors d ON t.donor_id = d.id
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN campaigns c ON t.charity_id = c.id
        ORDER BY t.created_at DESC";
        
$result = $conn->query($sql);
$transactions = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $transactions[] = [
            'id' => $row['id'],
            'type' => 'incoming',
            'amount' => $row['amount'],
            'donor' => $row['donor_name'] ?? 'Anonymous',
            'date' => $row['created_at'],
            'status' => $row['status'],
            'campaign' => $row['campaign_name'] ?? 'General Donation',
            'reference' => 'TRX' . str_pad($row['id'], 6, '0', STR_PAD_LEFT),
            'payment_method' => $row['payment_method']
        ];
    }
}

// Convert PHP array to JSON for JavaScript
$transactionsJson = json_encode($transactions);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Connect - Transactions</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="charity.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
                <a href="Transactions.php" class="nav-link active" data-page="transactions">
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
                <span class="user-name"><?php echo htmlspecialchars($loggedInUsername); ?></span>
            </div>
        </div>

        <div class="content-area">
            <div class="transactions-header">
                <h2>Transactions</h2>
                <div class="transaction-actions">
                    <button class="filter-btn" id="allTransactions">All</button>
                    <button class="filter-btn" id="incomingTransactions">Incoming</button>
                    <button class="filter-btn" id="outgoingTransactions">Outgoing</button>
                    <button class="download-btn" onclick="downloadTransactionsPDF()">
                        <i class="fas fa-download"></i> Download PDF
                    </button>
                    <div class="search-container">
                        <input type="text" id="searchTransactions" placeholder="Search transactions..."
                            class="search-input">
                    </div>
                </div>
            </div>

            <div class="transactions-list" id="transactionsList">
                <!-- Transactions will be populated here -->
            </div>

            <!-- Invoice Modal -->
            <div id="invoiceModal" class="modal">
                <div class="modal-content invoice-modal">
                    <span class="close-modal">&times;</span>
                    <div class="invoice-content" id="invoiceContent">
                        <!-- Invoice content will be populated here -->
                    </div>
                    <div class="invoice-actions">
                        <button class="print-btn" onclick="printInvoice()">
                            <i class="fas fa-print"></i> Print Invoice
                        </button>
                        <button class="download-btn" onclick="downloadInvoice()">
                            <i class="fas fa-download"></i> Download PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sample transaction data
        const transactions = [
            {
                id: 'TRX001',
                type: 'incoming',
                amount: 1000.00,
                donor: 'John Smith',
                date: '2024-03-15T14:30:00',
                status: 'completed',
                campaign: 'Education Fund',
                reference: 'DON123456'
            },
            {
                id: 'TRX002',
                type: 'outgoing',
                amount: 500.00,
                recipient: 'Local School',
                date: '2024-03-14T10:15:00',
                status: 'completed',
                purpose: 'School Supplies',
                reference: 'EXP123456'
            }
        ];

        // Function to format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        }

        // Function to format date
        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Function to render transactions
        function renderTransactions(filteredTransactions = transactions) {
            const transactionsList = document.getElementById('transactionsList');

            if (filteredTransactions.length === 0) {
                transactionsList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3>No transactions found</h3>
                        <p>There are no transactions matching your criteria.</p>
                    </div>
                `;
                return;
            }

            transactionsList.innerHTML = filteredTransactions.map(transaction => `
                <div class="transaction-card ${transaction.type}">
                    <div class="transaction-info">
                        <div class="transaction-header">
                            <h3>${transaction.type === 'incoming' ? 'Donation Received' : 'Payment Made'}</h3>
                            <span class="transaction-amount ${transaction.type}">
                                ${transaction.type === 'incoming' ? '+' : '-'}${formatCurrency(transaction.amount)}
                            </span>
                        </div>
                        <div class="transaction-details">
                            <span><i class="fas fa-user"></i> ${transaction.type === 'incoming' ? transaction.donor : transaction.recipient}</span>
                            <span><i class="fas fa-calendar"></i> ${formatDate(transaction.date)}</span>
                            <span><i class="fas fa-hashtag"></i> ${transaction.reference}</span>
                        </div>
                    </div>
                    <div class="transaction-actions">
                        <button class="view-invoice-btn" onclick="viewInvoice('${transaction.id}')">
                            <i class="fas fa-file-invoice"></i> View Invoice
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Function to view invoice
        function viewInvoice(transactionId) {
            const transaction = transactions.find(t => t.id === transactionId);
            const modal = document.getElementById('invoiceModal');
            const invoiceContent = document.getElementById('invoiceContent');

            invoiceContent.innerHTML = `
                <div class="invoice-header">
                    <div class="organization-info">
                        <h2>Charity Organization</h2>
                        <p>123 Charity Street</p>
                        <p>City, State 12345</p>
                    </div>
                    <div class="invoice-details">
                        <h3>Invoice #${transaction.reference}</h3>
                        <p>Date: ${formatDate(transaction.date)}</p>
                    </div>
                </div>
                <div class="invoice-body">
                    <div class="party-info">
                        <div class="from">
                            <h4>${transaction.type === 'incoming' ? 'From:' : 'To:'}</h4>
                            <p>${transaction.type === 'incoming' ? transaction.donor : transaction.recipient}</p>
                        </div>
                    </div>
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>${transaction.type === 'incoming' ? 'Donation' : 'Payment'} - ${transaction.campaign || transaction.purpose}</td>
                                <td>${formatCurrency(transaction.amount)}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><strong>Total</strong></td>
                                <td><strong>${formatCurrency(transaction.amount)}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;

            modal.style.display = 'block';
        }

        // Function to print invoice
        function printInvoice() {
            const invoiceContent = document.getElementById('invoiceContent').innerHTML;
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Invoice</title>');
            printWindow.document.write('<link href="charity.css" rel="stylesheet">');
            printWindow.document.write('</head><body>');
            printWindow.document.write(invoiceContent);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }

        // Function to download invoice as PDF
        function downloadInvoice() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const invoiceContent = document.getElementById('invoiceContent');
            
            // Add content to PDF
            doc.setFontSize(20);
            doc.text('Invoice', 20, 20);
            
            // Get transaction details
            const reference = invoiceContent.querySelector('.invoice-details h3').textContent;
            const date = invoiceContent.querySelector('.invoice-details p').textContent;
            const amount = invoiceContent.querySelector('.invoice-table tfoot strong').textContent;
            
            doc.setFontSize(12);
            doc.text(`Reference: ${reference}`, 20, 40);
            doc.text(`Date: ${date}`, 20, 50);
            doc.text(`Amount: ${amount}`, 20, 60);
            
            // Save the PDF
            doc.save('invoice.pdf');
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Get transactions from PHP
            const transactions = <?php echo $transactionsJson; ?>;
            
            // Update username
            const username = localStorage.getItem('username');
            if (username) {
                document.getElementById('username').textContent = username;
            }

            // Replace the transactions-list div with a table
            const transactionsList = document.getElementById('transactionsList');
            
            function renderTransactions(filteredTransactions = transactions) {
                if (filteredTransactions.length === 0) {
                    transactionsList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <h3>No transactions found</h3>
                            <p>There are no transactions matching your criteria.</p>
                        </div>
                    `;
                    return;
                }

                transactionsList.innerHTML = `
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th class="reference-col">Reference</th>
                                <th class="type-col">Type</th>
                                <th class="amount-col">Amount</th>
                                <th class="donor-col">Donor</th>
                                <th class="campaign-col">Campaign</th>
                                <th class="date-col">Date</th>
                                <th class="status-col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${filteredTransactions.map(transaction => `
                                <tr>
                                    <td class="reference-col">${transaction.reference}</td>
                                    <td class="type-col">${transaction.type}</td>
                                    <td class="amount-col">
                                        <span class="amount">${formatCurrency(transaction.amount)}</span>
                                    </td>
                                    <td class="donor-col">${transaction.donor}</td>
                                    <td class="campaign-col">${transaction.campaign}</td>
                                    <td class="date-col">${formatDate(transaction.date)}</td>
                                    <td class="status-col">
                                        <span class="status-badge ${transaction.status}">${transaction.status}</span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            // Initial render
            renderTransactions();
            
            // Filter buttons
            document.getElementById('allTransactions').addEventListener('click', () => renderTransactions());
            document.getElementById('incomingTransactions').addEventListener('click', () =>
                renderTransactions(transactions.filter(t => t.type === 'incoming'))
            );
            document.getElementById('outgoingTransactions').addEventListener('click', () =>
                renderTransactions(transactions.filter(t => t.type === 'outgoing'))
            );
            // Search functionality
            document.getElementById('searchTransactions').addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const filtered = transactions.filter(t =>
                    t.reference.toLowerCase().includes(searchTerm) ||
                    (t.donor || t.recipient).toLowerCase().includes(searchTerm)
                );
                renderTransactions(filtered);
            });

            // Close modal
            const modal = document.getElementById('invoiceModal');
            document.querySelector('.close-modal').addEventListener('click', () => {
                modal.style.display = 'none';
            });
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
        // Logout handling
        document.getElementById('logoutBtn').addEventListener('click', () => {
            if (confirm('Are you sure you want to logout?')) {
                localStorage.clear();
                window.location.href = 'charity_login.php';
            }
        });

        // Add this in your script section
        function downloadTransactionsPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(20);
            doc.text('Transactions Report', 20, 20);
            
            // Add date
            doc.setFontSize(12);
            doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 20, 30);
            
            // Get table data
            const table = document.querySelector('.transactions-table');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            // Set initial y position for table
            let y = 40;
            
            // Add headers
            const headers = Array.from(rows[0].querySelectorAll('th')).map(th => th.textContent);
            doc.setFontSize(10);
            doc.setFont(undefined, 'bold');
            headers.forEach((header, index) => {
                doc.text(header, 20 + (index * 35), y);
            });
            
            // Add data rows
            doc.setFont(undefined, 'normal');
            rows.slice(1).forEach(row => {
                y += 10;
                if (y >= 280) { // Check if we need a new page
                    doc.addPage();
                    y = 20;
                }
                
                const cells = Array.from(row.querySelectorAll('td'));
                cells.forEach((cell, index) => {
                    let text = cell.textContent;
                    // Truncate text if too long
                    if (text.length > 15) {
                        text = text.substring(0, 15) + '...';
                    }
                    doc.text(text, 20 + (index * 35), y);
                });
            });
            
            // Save the PDF
            doc.save('transactions-report.pdf');
        }
    </script>
</body>

</html>
