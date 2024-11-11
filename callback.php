<?php
session_start();
require_once('C:/xampp/htdocs/IS project coding/db.php');

// Get transaction details from PesaPal
$reference = $_GET['pesapal_merchant_reference'];
$transaction_id = $_GET['pesapal_transaction_tracking_id'];

// Update donation status
$sql = "UPDATE donations SET 
        pesapal_transaction_id = ?, 
        status = 'completed' 
        WHERE reference = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $transaction_id, $reference);

if (mysqli_stmt_execute($stmt)) {
    // Update campaign amount
    $sql = "UPDATE campaigns c 
            JOIN donations d ON c.id = d.campaign_id 
            SET c.current_amount = c.current_amount + d.amount 
            WHERE d.reference = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $reference);
    mysqli_stmt_execute($stmt);
    
    header("Location: Donor Dashboard/donation_success.php");
} else {
    header("Location: Donor Dashboard/donation_failed.php");
}
exit();
?> 