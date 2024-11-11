<?php
session_start();
require_once('C:/xampp/htdocs/IS project coding/db.php');

$transaction_tracking_id = $_GET['pesapal_transaction_tracking_id'];
$reference = $_GET['pesapal_merchant_reference'];
$status = $_GET['pesapal_notification_type'];

if ($status === 'COMPLETED') {
    // Update donation status
    $sql = "UPDATE donations SET 
            status = 'completed',
            pesapal_transaction_id = ?,
            updated_at = NOW()
            WHERE reference = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $transaction_tracking_id, $reference);
    
    if (mysqli_stmt_execute($stmt)) {
        // Update campaign amount
        $sql = "UPDATE campaigns c 
                JOIN donations d ON c.id = d.campaign_id 
                SET c.current_amount = c.current_amount + d.amount 
                WHERE d.reference = ? AND d.status = 'completed'";
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $reference);
        mysqli_stmt_execute($stmt);
        
        header("Location: donation_success.php");
    } else {
        header("Location: donation_error.php");
    }
} else {
    header("Location: donation_error.php");
}
?> 