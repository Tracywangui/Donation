<?php
// This must be the very first line - no whitespace before <?php
session_start();
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../db.php');

// Ensure donor is logged in
if (!isset($_SESSION['donor_id'])) {
    header("Location: donor_login.php?error=Please log in to donate");
    exit();
}

$donor_id = $_SESSION['donor_id'];
$campaign_id = $_GET['campaign_id'] ?? null;
$charity_id = $_GET['charity_id'] ?? null;

if (!$campaign_id || !$charity_id) {
    echo "Invalid campaign or charity information.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Make a Donation</title>
<script src="https://js.stripe.com/v3/"></script>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .container {
        background-color: #fff;
        padding: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
        max-width: 400px;
        width: 100%;
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
    }

    input[type="number"],
    input[type="email"],
    input[type="tel"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
    }

    #card-element {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    #card-errors {
        color: red;
        margin-top: 10px;
    }

    button {
        width: 100%;
        padding: 10px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    button:hover {
        background-color: #218838;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Donate to the Campaign</h2>
    <form id="payment-form" action="charge.php" method="POST">
        <input type="hidden" name="campaign_id" value="<?php echo htmlspecialchars($campaign_id); ?>">
        <input type="hidden" name="charity_id" value="<?php echo htmlspecialchars($charity_id); ?>">
        <div class="form-group">
            <label for="amount">Donation Amount (KES):</label>
            <input type="number" id="amount" name="amount" min="1" required>
        </div>
        <div class="form-group">
            <label for="email">Your Email:</label>
            <input type="email" id="email" name="email" value="<?php echo $_SESSION['donor_email'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="phone">Phone Number:</label>
            <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" placeholder="07XXXXXXXX" required>
        </div>
        <div class="form-group">
            <label for="card-element">Credit or Debit Card:</label>
            <div id="card-element"></div>
            <div id="card-errors" role="alert"></div>
        </div>
        <button id="submit-button" type="submit">Donate Now</button>
    </form>
</div>

<script>
    const stripe = Stripe('pk_test_51QLPQnA7eKA98adrx9yynoNnr7ZMkdtObCGtAqx1FHt73n5HK4sEtY3sanAZJ4evE7fKc9QOYlER8DBgc8RlL7mP00dgtfSVdS'); // Replace with your Stripe public key
    const elements = stripe.elements({
        locale: 'en',
        fields: {
            billingDetails: {
                address: {
                    postalCode: 'never'
                }
            }
        }
    });

    // Create an instance of the card Element with postal code disabled
    const card = elements.create('card', {
        hidePostalCode: true,
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
            }
        }
    });

    // Mount the card Element
    card.mount('#card-element');

    // Handle real-time validation errors from the card Element
    card.on('change', (event) => {
        const displayError = document.getElementById('card-errors');
        displayError.textContent = event.error ? event.error.message : '';
    });

    // Handle form submission
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        const submitButton = document.getElementById('submit-button');
        submitButton.disabled = true;
        
        try {
            const {error, paymentMethod} = await stripe.createPaymentMethod({
                type: 'card',
                card: card,
                billing_details: {
                    email: document.getElementById('email').value,
                    phone: document.getElementById('phone').value,
                }
            });

            if (error) {
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = error.message;
                submitButton.disabled = false;
                return;
            }

            // Get all form data
            const formData = new FormData(form);
            
            // Add the payment method ID to the form
            const hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'payment_method_id');
            hiddenInput.setAttribute('value', paymentMethod.id);
            form.appendChild(hiddenInput);
            
            // Submit the form normally
            form.submit();

        } catch (err) {
            console.error('Payment error:', err);
            const errorElement = document.getElementById('card-errors');
            errorElement.textContent = 'An unexpected error occurred.';
            submitButton.disabled = false;
        }
    });
</script>
</body>
</html>