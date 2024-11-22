<?php
// Start the session
session_start();

// Include the database connection file
include '../db.php';

// Check if the charity organization is logged in
if (!isset($_SESSION['charity_id'])) {
    echo "You must be logged in as a charity organization to view transactions.";
    exit;
}

// Get the logged-in charity's ID from the session
$charity_id = $_SESSION['charity_id'];

// Ensure $charityUsername is defined
$charityUsername = isset($_SESSION['charityUsername']) ? $_SESSION['charityUsername'] : 'Guest'; // Default to 'Guest' or any other placeholder

// Fetch transactions for the logged-in charity
$sql = "SELECT * FROM transactions WHERE charity_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $charity_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Display the transactions in a table
    echo "<table border='1'>
            <tr>
                <th>ID</th>
                <th>Amount</th>
                <th>Donor ID</th>
                <th>Payment Method</th>
                <th>Status</th>
                <th>Transaction Date</th>
            </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row['id'] . "</td>
                <td>" . $row['amount'] . "</td>
                <td>" . $row['donor_id'] . "</td>
                <td>" . $row['payment_method'] . "</td>
                <td>" . $row['status'] . "</td>
                <td>" . $row['transaction_date'] . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No transactions found for your organization.";
}

// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charity Dashboard - Transactions</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../Charity_Organisation_Dashboard/charity.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">Charity Organization Dashboard</div>
        </div>
        <ul class="nav-links">
            <li class="nav-item">
                <a href="charityOrganisation.php" class="nav-link" data-page="home">
                    <i class="fas fa-house"></i>
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
                <span class="user-name"><?php echo htmlspecialchars($charityUsername); ?></span>
            </div>
        </div>

        <div class="content-area">
            <div class="transactions-header">
                <h2>Transactions</h2>
                <div class="filters">
                    <button class="filter-btn active">All</button>
                    <button class="filter-btn">Pending</button>
                    <button class="filter-btn">Completed</button>
                    <input type="text" class="search-input" placeholder="Search transactions...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th width="15%">Date</th>
                            <th width="25%">Donor</th>
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
                                <td class="donor-cell"><?php echo htmlspecialchars($row['donor_firstname'] . " " . $row['donor_lastname']); ?></td>
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

    <!-- Scripts (same as before) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script>
    let currentTransactionData = null;

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
                if (data.error) {
                    throw new Error(data.error);
                }
                
                currentTransactionData = data;
                
                detailsDiv.innerHTML = `
                    <div class="invoice-header">
                        <h2>Transaction Details</h2>
                        <p class="invoice-number">Invoice #: INV-${data.id}</p>
                    </div>
                    <div class="invoice-details">
                        <div class="row"><strong>Donor:</strong> ${data.donor_firstname} ${data.donor_lastname}</div>
                        <div class="row"><strong>Amount:</strong> KSH ${data.amount}</div>
                        <div class="row"><strong>Status:</strong> ${data.status}</div>
                        <div class="row"><strong>Payment Method:</strong> ${data.payment_method}</div>
                        <div class="row"><strong>Date:</strong> ${data.created_at}</div>
                    </div>`;
            })
            .catch(error => {
                detailsDiv.innerHTML = `<p>Error: ${error.message}</p>`;
            });
    }
    </script>
</body>

</html>
