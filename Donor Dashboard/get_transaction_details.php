<?php
session_start();
include '../db.php';

if (!isset($_SESSION['donor_id'])) {
    echo json_encode([]);
    exit;
}

$donor_id = $_SESSION['donor_id'];
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Base SQL query
$sql = "SELECT t.*, c.organization_name AS charity_name 
        FROM transactions t
        LEFT JOIN charity_organizations c ON t.charity_id = c.id
        WHERE t.donor_id = ?";
$params = [$donor_id];

// Assuming you have already fetched the transaction details

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