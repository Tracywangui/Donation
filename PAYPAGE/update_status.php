<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once('../db.php'); // Ensure this file connects to your database

// Get the request body
$input = json_decode(file_get_contents('php://input'), true);

// Extract data from the request
$transaction_id = $input['transaction_id'];
$donation_id = $input['donation_id'];
$status = $input['status'];

try {
    // Update the transaction status in the database
    $stmt = $db->prepare("UPDATE transactions SET status = ? WHERE transaction_id = ? AND donation_id = ?");
    $stmt->execute([$status, $transaction_id, $donation_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Handle error
    echo json_encode(['error' => $e->getMessage()]);
}
?>
