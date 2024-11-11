<?php
session_start();
if (!isset($_SESSION['donorUsername'])) {
    header("Location: ../donor_login.php");
    exit();
}

require_once('C:/xampp/htdocs/IS project coding/db.php');

$campaign_id = isset($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;

$sql = "SELECT c.*, co.organization_name 
        FROM campaigns c 
        INNER JOIN charity_organizations co ON c.charity_id = co.id 
        WHERE c.id = ? AND c.status = 'active'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $campaign_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$campaign = mysqli_fetch_assoc($result);

if (!$campaign) {
    header("Location: donate.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Donation</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="main-content">
        <h1>Make a Donation</h1>
        
        <div class="donation-form-container">
            <h2><?php echo htmlspecialchars($campaign['organization_name']); ?></h2>
            <h3><?php echo htmlspecialchars($campaign['title']); ?></h3>
            
            <form action="process_donation.php" method="POST">
                <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                
                <div class="form-group">
                    <label>Amount (KES)</label>
                    <input type="number" name="amount" required min="1">
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" id="payment_method" required>
                        <option value="mpesa">M-Pesa</option>
                        <option value="card">Card</option>
                    </select>
                </div>

                <div id="mpesa_fields" class="payment-fields">
                    <div class="form-group">
                        <label>M-Pesa Phone Number</label>
                        <input type="text" name="mpesa_phone" placeholder="254XXXXXXXXX">
                    </div>
                </div>

                <div id="card_fields" class="payment-fields" style="display:none;">
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" name="card_number" placeholder="XXXX XXXX XXXX XXXX">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="text" name="card_expiry" placeholder="MM/YY">
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="text" name="card_cvv" placeholder="XXX">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Make Donation</button>
            </form>
        </div>
    </div>

    <style>
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .donation-form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 20px auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-group.half {
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
        }

        small {
            color: #666;
            font-size: 12px;
            margin-top: 4px;
            display: block;
        }

        .payment-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .donate-btn {
            background: #3498db;
            color: white;
            padding: 14px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .donate-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .donate-btn:not(:disabled):hover {
            background: #2980b9;
        }

        .card-input-wrapper {
            position: relative;
        }

        .card-type-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
    </style>

    <script>
        function showPaymentFields() {
            const paymentMethod = document.getElementById('payment_method').value;
            const mpesaFields = document.getElementById('mpesaFields');
            const cardFields = document.getElementById('cardFields');
            const submitBtn = document.getElementById('submitBtn');
            
            // Hide all payment fields first
            mpesaFields.style.display = 'none';
            cardFields.style.display = 'none';
            
            // Show the selected payment fields
            if (paymentMethod === 'mpesa') {
                mpesaFields.style.display = 'block';
                submitBtn.disabled = false;
            } else if (paymentMethod === 'card') {
                cardFields.style.display = 'block';
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Format card number with spaces
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(.{4})/g, '$1 ').trim();
            e.target.value = value;
        });

        // Format expiry date
        document.getElementById('expiry_date').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2);
            }
            e.target.value = value;
        });

        // Validate M-Pesa PIN
        document.getElementById('mpesa_pin').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
        });

        // Form validation before submit
        document.getElementById('donationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const paymentMethod = document.getElementById('payment_method').value;
            
            if (paymentMethod === 'mpesa') {
                const phone = document.getElementById('mpesa_phone').value;
                const pin = document.getElementById('mpesa_pin').value;
                
                if (phone.length < 10 || pin.length !== 4) {
                    alert('Please enter valid M-Pesa details');
                    return;
                }
            } else if (paymentMethod === 'card') {
                const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
                const expiry = document.getElementById('expiry_date').value;
                const cvv = document.getElementById('cvv').value;
                
                if (cardNumber.length !== 16 || !expiry.includes('/') || cvv.length !== 3) {
                    alert('Please enter valid card details');
                    return;
                }
            }
            
            // If validation passes, submit the form
            this.submit();
        });
    </script>
</body>
</html> 