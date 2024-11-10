<?php
session_start();
require_once('C:/xampp/htdocs/IS project coding/db.php');

// Ensure we're sending JSON response
header('Content-Type: application/json');

if (isset($_GET['donor_id']) && isset($_GET['user_id'])) {
    $donor_id = (int)$_GET['donor_id'];
    $user_id = (int)$_GET['user_id'];
    
    $query = "SELECT u.firstname, u.lastname, u.email, u.phoneNo 
              FROM users u 
              JOIN donors d ON u.id = d.user_id 
              WHERE d.id = ? AND u.id = ?";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $donor_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Donor not found']);
    }
} else {
    echo json_encode(['error' => 'Missing parameters']);
}
?> 