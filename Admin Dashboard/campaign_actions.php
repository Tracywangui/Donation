<?php
session_start();
require_once('../config/db.php');

// Receive JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action']) || !isset($data['campaign_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$campaign_id = intval($data['campaign_id']);

switch ($data['action']) {
    case 'toggle_status':
        // Get current status
        $query = "SELECT status FROM campaigns WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $campaign_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $campaign = $result->fetch_assoc();

        // Toggle between active and inactive
        $new_status = $campaign['status'] === 'active' ? 'inactive' : 'active';
        
        $update_query = "UPDATE campaigns SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('si', $new_status, $campaign_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    case 'delete':
        $query = "DELETE FROM campaigns WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $campaign_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?> 