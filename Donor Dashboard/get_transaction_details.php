<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../db.php');

try {
    // Check authorization
    if (!isset($_SESSION['donor_id']) || !isset($_GET['id'])) {
        throw new Exception('Unauthorized access');
    }

    $transaction_id = intval($_GET['id']);
    $donor_id = intval($_SESSION['donor_id']);

    // Updated query to use email instead of username/name
    $query = "SELECT 
                t.id,
                t.created_at,
                t.amount,
                t.payment_method,
                t.status,
                c.organization_name as charity_name,
                d.email as donor_email
              FROM transactions t
              LEFT JOIN charity_organizations c ON t.charity_id = c.id
              LEFT JOIN donors d ON t.donor_id = d.id
              WHERE t.id = ? AND t.donor_id = ?";
              
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    $stmt->bind_param("ii", $transaction_id, $donor_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $response = array(
            'id' => $row['id'],
            'created_at' => date('F j, Y', strtotime($row['created_at'])),
            'amount' => number_format($row['amount'], 2),
            'charity_name' => $row['charity_name'],
            'donor_email' => $row['donor_email'],
            'payment_method' => $row['payment_method'],
            'status' => $row['status']
        );
        
        echo json_encode($response);
    } else {
        throw new Exception('Transaction not found');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

if (isset($stmt)) {
    $stmt->close();
}
if (isset($conn)) {
    $conn->close();
}
?> 