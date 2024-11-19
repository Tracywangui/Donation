<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../db.php');

// Ensure donor is logged in
if (!isset($_SESSION['donor_id'])) {
    header("Location: donor_login.php?error=Please log in to make a donation");
    exit();
}

$donor_id = $_SESSION['donor_id'];
$campaign_id = $_POST['campaign_id'] ?? null;
$charity_id = $_POST['charity_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$email = $_POST['email'] ?? null;
$phone = $_POST['phone'] ?? null;
$payment_method_id = $_POST['payment_method_id'] ?? null;

try {
    // First verify the donor exists
    $verify_stmt = $conn->prepare("SELECT id FROM donors WHERE id = ?");
    $verify_stmt->bind_param("i", $donor_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        throw new Exception("Invalid donor ID");
    }
    $verify_stmt->close();

    // Start database transaction
    $conn->begin_transaction();

    // Generate reference
    $reference = 'DON-' . time() . '-' . $donor_id;

    // Insert into donations table
    $donation_sql = "INSERT INTO donations (amount, campaign_id, donor_id, email, phone, reference, status, stripe_payment_status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending')";
    $stmt = $conn->prepare($donation_sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("diisss", 
        $amount, 
        $campaign_id, 
        $donor_id, 
        $email, 
        $phone, 
        $reference
    );

    if (!$stmt->execute()) {
        error_log("Donation insert error: " . $stmt->error);
        throw new Exception("Failed to save donation");
    }
    $donation_id = $conn->insert_id;
    $stmt->close();

    // Insert into transactions table
    $transaction_sql = "INSERT INTO transactions (amount, charity_id, donor_id, payment_method, status, transaction_date) 
                       VALUES (?, ?, ?, 'stripe', 'pending', NOW())";
    $stmt = $conn->prepare($transaction_sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("dii", 
        $amount, 
        $charity_id, 
        $donor_id
    );

    if (!$stmt->execute()) {
        error_log("Transaction insert error: " . $stmt->error);
        throw new Exception("Failed to save transaction");
    }
    $transaction_id = $conn->insert_id;
    $stmt->close();

    // Commit the database transaction first
    $conn->commit();

    $payment_success = false;
    $payment_error = '';

    // Try to process the payment
    try {
        \Stripe\Stripe::setApiKey('sk_test_51QLPQnA7eKA98adrQcsyHGyvqQORhJU1ZKsFti4dpWkLDOzwMQuK8Mboy6zUyFpQU84vX58TlpxKhHKEOx7iJZby00kyuHILxr');

        // Get the full URL for the success page
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $success_url = $protocol . $host . "/PAYPAGE/success.php?tid=" . $transaction_id . "&amount=" . $amount . "&donor_id=" . $donor_id;

        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount * 100,
            'currency' => 'kes',
            'payment_method' => $payment_method_id,
            'confirmation_method' => 'manual',
            'confirm' => true,
            'return_url' => $success_url,
            'metadata' => [
                'donation_id' => $donation_id,
                'transaction_id' => $transaction_id
            ]
        ]);

        // Update statuses based on payment result
        if ($paymentIntent->status === 'succeeded') {
            // Update status to succeeded (not completed)
            $update_sql = "UPDATE donations SET status = 'succeeded', stripe_payment_status = 'succeeded' WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $donation_id);
            $stmt->execute();
            $stmt->close();

            $update_trans_sql = "UPDATE transactions SET status = 'succeeded' WHERE id = ?";
            $stmt = $conn->prepare($update_trans_sql);
            $stmt->bind_param("i", $transaction_id);
            $stmt->execute();
            $stmt->close();

            // Redirect to success page
            header("Location: success.php?tid=$transaction_id&amount=$amount&donor_id=$donor_id");
            exit();
        } 
        else if ($paymentIntent->status === 'requires_action' || 
                 $paymentIntent->status === 'requires_source_action') {
            // Card requires authentication
            echo json_encode([
                'requires_action' => true,
                'payment_intent_client_secret' => $paymentIntent->client_secret,
                'return_url' => $success_url
            ]);
            exit();
        }
        else {
            // Payment failed or is pending
            $update_donation_sql = "UPDATE donations SET status = 'pending', stripe_payment_status = 'failed' WHERE id = ?";
            $stmt = $conn->prepare($update_donation_sql);
            $stmt->bind_param("i", $donation_id);
            $stmt->execute();
            $stmt->close();

            $update_trans_sql = "UPDATE transactions SET status = 'pending' WHERE id = ?";
            $stmt = $conn->prepare($update_trans_sql);
            $stmt->bind_param("i", $transaction_id);
            $stmt->execute();
            $stmt->close();

            header("Location: success.php?tid=$transaction_id&amount=$amount&donor_id=$donor_id&status=pending");
            exit();
        }

    } catch (\Stripe\Exception\CardException $e) {
        // Handle card errors
        error_log("Card error: " . $e->getMessage());
        
        // Update status to failed
        $update_donation_sql = "UPDATE donations SET status = 'failed', stripe_payment_status = 'failed' WHERE id = ?";
        $stmt = $conn->prepare($update_donation_sql);
        $stmt->bind_param("i", $donation_id);
        $stmt->execute();
        $stmt->close();

        $update_trans_sql = "UPDATE transactions SET status = 'failed' WHERE id = ?";
        $stmt = $conn->prepare($update_trans_sql);
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $stmt->close();

        header("Location: error.php?error=" . urlencode($e->getMessage()));
        exit();
    } catch (\Exception $e) {
        error_log("Payment error: " . $e->getMessage());
        header("Location: error.php?error=" . urlencode("Payment processing error. Please try again."));
        exit();
    }

} catch (Exception $e) {
    error_log("Error in charge.php: " . $e->getMessage());
    if (isset($conn)) {
        $conn->rollback();
    }
    header("Location: error.php?error=" . urlencode("Your donation was not processed. Please try again later."));
    exit();
}
