<?php
session_start();
require_once('C:/xampp/htdocs/IS project coding/db.php');

header('Content-Type: application/json');

try {
    // For delete action
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON data from request body
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        if (isset($data['action']) && $data['action'] === 'delete' && 
            isset($data['donor_id']) && isset($data['user_id'])) {
            
            mysqli_begin_transaction($conn);
            
            try {
                // First delete from donation_requests table
                $stmt = mysqli_prepare($conn, "DELETE FROM donation_requests WHERE donor_id = ?");
                mysqli_stmt_bind_param($stmt, "i", $data['donor_id']);
                mysqli_stmt_execute($stmt);

                // Then delete from donors table
                $stmt = mysqli_prepare($conn, "DELETE FROM donors WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $data['donor_id']);
                mysqli_stmt_execute($stmt);

                // Finally delete from users table
                $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $data['user_id']);
                mysqli_stmt_execute($stmt);

                mysqli_commit($conn);
                echo json_encode([
                    'success' => true,
                    'message' => 'Donor deleted successfully'
                ]);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                throw $e;
            }
        }
        // For update action (using regular POST data)
        else if (isset($_POST['donor_id']) && isset($_POST['user_id'])) {
            $donor_id = (int)$_POST['donor_id'];
            $user_id = (int)$_POST['user_id'];
            $firstname = mysqli_real_escape_string($conn, trim($_POST['firstname']));
            $lastname = mysqli_real_escape_string($conn, trim($_POST['lastname']));
            $email = mysqli_real_escape_string($conn, trim($_POST['email']));
            $phoneNo = mysqli_real_escape_string($conn, trim($_POST['phoneNo']));

            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // Update users table
                $update_query = "UPDATE users SET 
                               firstname = ?, 
                               lastname = ?, 
                               email = ?, 
                               phoneNo = ? 
                               WHERE id = ?";
                
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "ssssi", $firstname, $lastname, $email, $phoneNo, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_commit($conn);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Donor updated successfully'
                    ]);
                } else {
                    throw new Exception("Failed to update donor");
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                throw $e;
            }
        }
        else {
            throw new Exception('Missing required parameters');
        }
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?> 