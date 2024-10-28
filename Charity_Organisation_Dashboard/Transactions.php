<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "donateconnect";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header('Location: Transactions.php'); // Redirect to the login page
    exit();
}

// Set the username from the session
$charityUsername = $_SESSION['charityUsername'];

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();

// Fetch transactions from the database (modify the query as needed)
$transactions = [];
$sql = "SELECT * FROM transactions"; // Change this to your actual transactions table
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donateconnect - Transactions</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="charity.css" rel="stylesheet">
    <script src="auth-check.js"></script>
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
                <a href="donations.php" class="nav-link" data-page="donations">
                    <i class="fas fa-gift"></i>
                    <span>Donations</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Transactions.php" class="nav-link active" data-page="transactions">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Transactions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="Notifications.php" class="nav-link" data-page="notifications">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
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
                <span class="user-name" id="username"><?php echo htmlspecialchars($charityUsername); ?></span>
            </div>
        </div>

        <div class="content-area">
            <div class="transactions-header">
                <h2>Transactions</h2>
                <div class="transaction-actions">
                    <button class="filter-btn" id="allTransactions">All</button>
                    <button class="filter-btn" id="incomingTransactions">Incoming</button>
                    <button class="filter-btn" id="outgoingTransactions">Outgoing</button>
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
        // Sample transaction data from PHP
        const transactions = <?php echo json_encode($transactions); ?>;

        // Function to format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-KE', {
                style: 'currency',
                currency: 'KES'
            }).format(amount);
        }

        // Function to format date
        function formatDate(dateString) {
            return new Date(dateString).toLocaleString('en-KE', {
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
            printWindow.document.write('<link href="charity.css" rel="stylesheet" type="text/css">');
            printWindow.document.write('</head><body>');
            printWindow.document.write(invoiceContent);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }

        // Function to download invoice
        function downloadInvoice() {
            const invoiceContent = document.getElementById('invoiceContent').innerHTML;
            const blob = new Blob([invoiceContent], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'invoice.html';
            a.click();
            URL.revokeObjectURL(url);
        }

        // Event Listeners
        document.getElementById('searchTransactions').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const filteredTransactions = transactions.filter(transaction => {
                return transaction.donor.toLowerCase().includes(searchTerm) || transaction.recipient.toLowerCase().includes(searchTerm) || transaction.reference.toLowerCase().includes(searchTerm);
            });
            renderTransactions(filteredTransactions);
        });

        document.getElementById('allTransactions').addEventListener('click', function() {
            renderTransactions(transactions);
        });

        document.getElementById('incomingTransactions').addEventListener('click', function() {
            const filteredTransactions = transactions.filter(t => t.type === 'incoming');
            renderTransactions(filteredTransactions);
        });

        document.getElementById('outgoingTransactions').addEventListener('click', function() {
            const filteredTransactions = transactions.filter(t => t.type === 'outgoing');
            renderTransactions(filteredTransactions);
        });

        // Render all transactions on load
        renderTransactions();

        // Close modal functionality
        document.querySelector('.close-modal').addEventListener('click', function() {
            document.getElementById('invoiceModal').style.display = 'none';
        });

        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', function() {
            window.location.href = '../charity_login.php'; // 
        });
    </script>
</body>

</html>
