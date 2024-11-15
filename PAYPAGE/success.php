<?php
session_start();
$transaction_id = isset($_GET['tid']) ? $_GET['tid'] : '';
$amount = isset($_GET['amount']) ? $_GET['amount'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="alert alert-success">
            <h4 class="alert-heading">Payment Successful!</h4>
            <p>Thank you for your donation of KES <?php echo htmlspecialchars($amount); ?></p>
            <p>Transaction ID: <?php echo htmlspecialchars($transaction_id); ?></p>
        </div>
        <a href="../Donor Dashboard/donate.php" class="btn btn-primary">Return to Donations</a>
    </div>
</body>
</html>