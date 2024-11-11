<?php
session_start();
require_once('C:/xampp/htdocs/IS project coding/db.php');
require_once('../pesapal/OAuth.php');
require_once('../pesapal/pesapal_config.php');  // Updated path

if (!isset($_SESSION['donorUsername'])) {
    header("Location: ../donor_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $campaign_id = $_POST['campaign_id'];
        $amount = $_POST['amount'];
        $email = $_SESSION['donorUsername'];
        
        // Generate reference
        $reference = 'REF' . time() . rand(1000, 9999);
        
        // Store donation details in session
        $_SESSION['donation_amount'] = $amount;
        $_SESSION['donation_reference'] = $reference;
        $_SESSION['donation_campaign_id'] = $campaign_id;
        
        // Insert initial donation record
        $sql = "INSERT INTO donations (campaign_id, email, amount, reference, status) 
                VALUES (?, ?, ?, ?, 'pending')";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isds", $campaign_id, $email, $amount, $reference);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error recording donation");
        }
        
        // Redirect to payment page
        header("Location: payment.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['donation_error'] = true;
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: donation_failed.php");
        exit();
    }
}
?> 