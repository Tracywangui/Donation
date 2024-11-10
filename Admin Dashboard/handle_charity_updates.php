<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Clear any previous output
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

try {
    // Use absolute path to db.php
    require_once('C:/xampp/htdocs/IS project coding/db.php');
    
    // Log received data
    error_log("POST data received: " . print_r($_POST, true));
    
    // Basic validation
    if (empty($_POST['charity_id']) || empty($_POST['organization_name']) || 
        empty($_POST['email']) || empty($_POST['phoneNo'])) {
        throw new Exception('All fields are required');
    }
    
    // Verify database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Sanitize inputs
    $charity_id = (int)$_POST['charity_id'];
    $organization_name = mysqli_real_escape_string($conn, trim($_POST['organization_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phoneNo = mysqli_real_escape_string($conn, trim($_POST['phoneNo']));
    
    // Log the values for debugging
    error_log("Processing update for: Charity ID: $charity_id, Name: $organization_name, Email: $email, Phone: $phoneNo");
    
    mysqli_begin_transaction($conn);
    
    try {
        // First get the user_id from charity_organizations
        $query = "SELECT user_id FROM charity_organizations WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $charity_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $charity = mysqli_fetch_assoc($result);
        
        if (!$charity) {
            throw new Exception('Charity not found');
        }
        
        $user_id = $charity['user_id'];
        error_log("Found user_id: $user_id");
        
        // Update charity_organizations table
        $query = "UPDATE charity_organizations 
                 SET organization_name = ?, 
                     email = ?
                 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $organization_name, $email, $charity_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error updating charity organization: " . mysqli_error($conn));
        }
        error_log("Successfully updated charity_organizations table");
        
        // Update users table
        $query = "UPDATE users 
                 SET email = ?, 
                     phoneNo = ?
                 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $email, $phoneNo, $user_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error updating user: " . mysqli_error($conn));
        }
        error_log("Successfully updated users table");
        
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'Update successful',
            'debug' => [
                'charity_id' => $charity_id,
                'user_id' => $user_id,
                'organization_name' => $organization_name,
                'email' => $email,
                'phoneNo' => $phoneNo
            ]
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in handle_charity_updates.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit();
?> 