<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Connect - Transactions</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
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
                <span class="user-name" id="username">Tracy Wangui</span>
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

            <div class="export-buttons">
                <h3>Export Transactions</h3>
                <form action="export_transactions.php" method="GET">
                    <div class="date-range">
                        <label>
                            From:
                            <input type="date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                        </label>
                        <label>
                            To:
                            <input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>">
                        </label>
                    </div>
                    <button type="submit" name="format" value="excel" class="export-btn excel">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                    <button type="submit" name="format" value="pdf" class="export-btn pdf">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                </form>
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
            // Implementation would require a PDF library
            alert('PDF download functionality will be implemented with a PDF generation library');
        }

        // Event Listeners
        ocument.addEventListener('DOMContentLoaded', () => {
            // Check login status
            const isLoggedIn = localStorage.getItem('isLoggedIn');
            if (!isLoggedIn) {
                window.location.href = '../charity_login.html';
                return;
            }

            // Update username from localStorage
            const username = localStorage.getItem('username');
            if (username) {
                document.getElementById('username').textContent = username;
            }

            // Render transactions
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
                window.location.href = '../donor_login.php';
            }
        });
    </script>
</body>

</html>
