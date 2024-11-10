<?php
session_start();
require_once('C:/xampp/htdocs/IS project coding/db.php');
try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action']) || !isset($data['campaign_id'])) {
        throw new Exception('Invalid request parameters');
    }

    $campaign_id = (int)$data['campaign_id'];

    switch ($data['action']) {
        case 'toggle_status':
            $query = "UPDATE campaigns SET status = 
                     CASE WHEN status = 'active' THEN 'inactive' 
                          ELSE 'active' END 
                     WHERE id = ?";
            break;

        case 'delete':
            // Start transaction
            mysqli_begin_transaction($conn);
            
            // Delete related donations first
            $query1 = "DELETE FROM donations WHERE campaign_id = ?";
            $stmt1 = mysqli_prepare($conn, $query1);
            mysqli_stmt_bind_param($stmt1, 'i', $campaign_id);
            mysqli_stmt_execute($stmt1);

            // Then delete the campaign
            $query = "DELETE FROM campaigns WHERE id = ?";
            break;

        default:
            throw new Exception('Invalid action');
    }

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 'i', $campaign_id);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        if (isset($conn->mysqli_begin_transaction)) {
            mysqli_commit($conn);
        }
        echo json_encode(['success' => true]);
    } else {
        throw new Exception(mysqli_error($conn));
    }

} catch (Exception $e) {
    if (isset($conn->mysqli_begin_transaction)) {
        mysqli_rollback($conn);
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?> 