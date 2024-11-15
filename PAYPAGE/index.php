<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug session
error_log("Session data in index.php: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['donorUsername'])) {
    // Store the current URL for redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // Redirect to donor_login.php in the root directory
    header("Location: ../donor_login.php");
    exit();
}

// Get donor details
require_once(__DIR__ . '/../db.php');

$username = $_SESSION['donorUsername'];
$stmt = $conn->prepare("SELECT d.*, u.email FROM donors d 
                       INNER JOIN users u ON d.user_id = u.id 
                       WHERE u.username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$donor = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Make a Donation</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: bold;
        }
        label {
            display: block;
            margin: 15px 0 5px;
            color: #555;
        }
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: #f8f9fa;
        }
        #card-element {
            padding: 10px;
            border: 1px solid #
    </style>
</head>
<body>
    <div class="container">
        <?php
        // Decode the campaign title from URL
        $campaign_title = isset($_GET['campaign_title']) ? urldecode($_GET['campaign_title']) : 'Make a Donation';
        ?>
        <h1><?php echo htmlspecialchars($campaign_title); ?></h1>

        <form id="payment-form">
            <div class="form-group">
                <label for="amount">Amount (KES)</label>
                <input type="number" id="amount" name="amount" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="card-element">Credit or Debit Card</label>
                <div id="card-element">
                    <!-- Stripe Elements will create input here -->
                </div>
                <div id="card-errors" role="alert"></div>
            </div>

            <button type="submit" id="submit-button">Make Payment</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Stripe with your publishable key
            const stripe = Stripe('pk_test_51QLPQnA7eKA98adrx9yynoNnr7ZMkdtObCGtAqx1FHt73n5HK4sEtY3sanAZJ4evE7fKc9QOYlER8DBgc8RlL7mP00dgtfSVdS', {
                apiVersion: '2023-10-16'
            });

            // Create card element with specific options
            const elements = stripe.elements();
            const card = elements.create('card', {
                hidePostalCode: true,
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#32325d',
                        '::placeholder': {
                            color: '#aab7c4'
                        }
                    }
                }
            });

            card.mount('#card-element');

            // Handle form submission
            const form = document.getElementById('payment-form');
            const errorElement = document.getElementById('card-errors');
            const submitButton = document.getElementById('submit-button');

            form.addEventListener('submit', async function(event) {
                event.preventDefault();
                submitButton.disabled = true;
                errorElement.textContent = '';

                try {
                    // Create payment method instead of token
                    const {paymentMethod, error} = await stripe.createPaymentMethod({
                        type: 'card',
                        card: card,
                    });

                    if (error) {
                        throw error;
                    }

                    const formData = new FormData();
                    formData.append('paymentMethodId', paymentMethod.id);
                    formData.append('amount', document.getElementById('amount').value);
                    formData.append('email', document.getElementById('email').value);
                    formData.append('phone', document.getElementById('phone').value);

                    // Get URL parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    formData.append('campaign_id', urlParams.get('campaign_id'));
                    formData.append('charity_id', urlParams.get('charity_id'));

                    const response = await fetch('charge.php?' + urlParams.toString(), {
                        method: 'POST',
                        body: formData
                    });

                    const responseText = await response.text();
                    
                    if (responseText.includes('success.php')) {
                        window.location.href = responseText;
                    } else {
                        throw new Error(responseText);
                    }

                } catch (error) {
                    console.error('Payment error:', error);
                    errorElement.textContent = error.message || 'Payment failed. Please try again.';
                } finally {
                    submitButton.disabled = false;
                }
            });

            // Real-time validation
            card.addEventListener('change', ({error}) => {
                errorElement.textContent = error ? error.message : '';
            });
        });
    </script>
</body>
</html>