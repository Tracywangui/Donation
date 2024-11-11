<?php
session_start();
require_once('C:/xampp/htdocs/IS project coding/db.php');
require_once('../pesapal/pesapal_config.php');

// Get donation details from session
$amount = isset($_SESSION['donation_amount']) ? $_SESSION['donation_amount'] : 0;
$reference = isset($_SESSION['donation_reference']) ? $_SESSION['donation_reference'] : '';
$email = isset($_SESSION['donorUsername']) ? $_SESSION['donorUsername'] : '';

// Build the payment URL
$payment_url = "https://pay.pesapal.com/v3/payment?" . http_build_query([
    'merchant_id' => PESAPAL_CONSUMER_KEY,
    'order_id' => $reference,
    'amount' => $amount,
    'currency' => 'KES',
    'description' => 'Donation Payment',
    'email' => $email,
    'return_url' => PESAPAL_CALLBACK_URL
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payment - DonateConnect</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .payment-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 30px;
        }
        .debug-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 12px;
            text-align: left;
            word-break: break-all;
        }
        .payment-button {
            display: inline-block;
            background: #007bff;
            color: #fff;
            padding: 15px 40px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .payment-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h2>Complete Your Payment</h2>
        
        <div class="debug-info">
            <p>Generated payment URL:</p>
            <pre><?php echo htmlspecialchars($payment_url); ?></pre>
        </div>

        <!-- Using a form with POST method -->
        <form action="<?php echo htmlspecialchars($payment_url); ?>" method="POST">
            <button type="submit" class="payment-button">Proceed to Payment</button>
        </form>
    </div>

    <!-- Add JavaScript to handle the redirect -->
    <script>
        document.querySelector('.payment-button').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = '<?php echo $payment_url; ?>';
        });
    </script>
</body>
</html> 