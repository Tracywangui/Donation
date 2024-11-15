<?php
session_start();
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../db.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function logError($message, $data = null) {
    error_log("PAYMENT DEBUG: " . $message);
    if ($data) error_log(print_r($data, true));
}

try {
    // Validate session and data
    if (!isset($_SESSION['donor_id'])) {
        throw new Exception("Please log in to make a donation");
    }

    // Get form data
    $amount = $_POST['amount'] ?? null;
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $campaign_id = $_GET['campaign_id'] ?? $_POST['campaign_id'] ?? null;
    $charity_id = $_GET['charity_id'] ?? $_POST['charity_id'] ?? null;
    $donor_id = $_SESSION['donor_id'];
    $current_time = date('Y-m-d H:i:s');
    $reference = 'DON-' . time();

    // Start database transaction
    $conn->begin_transaction();

    try {
        // Insert into donations table
        $donation_sql = "INSERT INTO donations (
            amount,
            campaign_id,
            donor_id,
            email,
            phone,
            reference,
            status,
            stripe_payment_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($donation_sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        // Set initial values
        $status = 'pending';
        $stripe_payment_status = 'pending';

        // Fix: Exactly 8 parameters to match 8 placeholders
        $stmt->bind_param("diisssss", 
            $amount,              // d - decimal
            $campaign_id,         // i - integer
            $donor_id,           // i - integer
            $email,              // s - string
            $phone,              // s - string
            $reference,          // s - string
            $status,             // s - string
            $stripe_payment_status // s - string
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to save donation: " . $stmt->error);
        }
        $donation_id = $conn->insert_id;
        $stmt->close();

        // Now insert into transactions
        $transaction_sql = "INSERT INTO transactions (
            amount,
            charity_id,
            donor_id,
            payment_method,
            status,
            transaction_date,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NOW())";

        $stmt = $conn->prepare($transaction_sql);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $payment_method = 'stripe';
        $status = 'pending';

        // Fix: Only 5 parameters to match the 5 placeholders
        $stmt->bind_param("diiss", 
            $amount,          // d - decimal
            $charity_id,      // i - integer
            $donor_id,        // i - integer
            $payment_method,  // s - string
            $status          // s - string
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to save transaction: " . $stmt->error);
        }
        $transaction_id = $conn->insert_id;
        $stmt->close();

        // Commit transaction
        $conn->commit();
        
        // After successful insertion, fetch complete information
        try {
            // Fetch complete transaction information
            $query = "SELECT 
                t.*,
                c.name AS charity_name,
                d.first_name AS donor_first_name,
                d.last_name AS donor_last_name,
                d.email AS donor_email
            FROM transactions t
            LEFT JOIN charity_organizations c ON t.charity_id = c.id
            LEFT JOIN donors d ON t.donor_id = d.id
            WHERE t.id = ?";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $transaction_id);
            $stmt->execute();
            $transaction_result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Fetch complete donation information
            $query = "SELECT 
                don.*,
                d.first_name AS donor_first_name,
                d.last_name AS donor_last_name,
                d.email AS donor_email,
                camp.title AS campaign_title,
                c.name AS charity_name
            FROM donations don
            LEFT JOIN donors d ON don.donor_id = d.id
            LEFT JOIN campaigns camp ON don.campaign_id = camp.id
            LEFT JOIN charity_organizations c ON camp.charity_id = c.id
            WHERE don.id = ?";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $donation_id);
            $stmt->execute();
            $donation_result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // You can now use this information in your success page
            $success_url = "success.php?" . http_build_query([
                'tid' => $transaction_id,
                'amount' => $amount,
                'donor_name' => $donation_result['donor_first_name'] . ' ' . $donation_result['donor_last_name'],
                'campaign' => $donation_result['campaign_title'],
                'charity' => $donation_result['charity_name']
            ]);

            echo $success_url;

        } catch (Exception $e) {
            error_log("Error fetching complete information: " . $e->getMessage());
            // Still redirect to success page even if fetching additional info fails
            echo "success.php?tid=" . $transaction_id . "&amount=" . $amount;
        }

    } catch (Exception $e) {
        $conn->rollback();
        logError("Database error", $e->getMessage());
        throw new Exception("Failed to save payment records: " . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Payment Error: " . $e->getMessage());
    echo "error.php?error=" . urlencode($e->getMessage());
}