<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('C:/xampp/htdocs/IS project coding/db.php');

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$action = $data['action'] ?? '';
$campaign_id = $data['campaign_id'] ?? 0;

if (!$campaign_id) {
    echo json_encode(['success' => false, 'message' => 'Campaign ID is required']);
    exit;
}

switch ($action) {
    case 'delete':
        try {
            mysqli_begin_transaction($conn);
            
            // Delete related donations first
            $query1 = "DELETE FROM donations WHERE campaign_id = ?";
            $stmt1 = mysqli_prepare($conn, $query1);
            mysqli_stmt_bind_param($stmt1, 'i', $campaign_id);
            $result1 = mysqli_stmt_execute($stmt1);

            // Then delete the campaign
            $query2 = "DELETE FROM campaigns WHERE id = ?";
            $stmt2 = mysqli_prepare($conn, $query2);
            mysqli_stmt_bind_param($stmt2, 'i', $campaign_id);
            $result2 = mysqli_stmt_execute($stmt2);

            if ($result1 && $result2) {
                mysqli_commit($conn);
                echo json_encode(['success' => true]);
            } else {
                mysqli_rollback($conn);
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle_status':
        // Get current status
        $query = "SELECT status FROM campaigns WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $campaign_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $campaign = mysqli_fetch_assoc($result);
        
        // Toggle status
        $new_status = ($campaign['status'] === 'active') ? 'inactive' : 'active';
        
        $update_query = "UPDATE campaigns SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, 'si', $new_status, $campaign_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

mysqli_close($conn);
?> 