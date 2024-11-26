<?php
session_start();
include '../db.php';

// Check if the charity organization is logged in (check for charity_id)
if (!isset($_SESSION['charity_id'])) {
    echo json_encode([]);
    exit;
}

$charity_id = $_SESSION['charity_id'];
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Base SQL query
$sql = "SELECT t.*, c.organization_name AS charity_name 
        FROM transactions t
        LEFT JOIN charity_organizations c ON t.charity_id = c.id
        WHERE t.charity_id = ?";
$params = [$charity_id];

// Add filter conditions
if ($filter === 'pending') {
    $sql .= " AND status = 'pending'";
} elseif ($filter === 'completed') {
    $sql .= " AND status = 'succeeded'";
}

// Add search condition
if (!empty($search)) {
    $sql .= " AND c.organization_name LIKE ?";
    $params[] = "%$search%";
}

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

echo json_encode($transactions);
$conn->close();
?>
