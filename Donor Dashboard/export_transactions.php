<?php
session_start();
require_once('C:/xampp/htdocs/IS project coding/db.php');

// Check if user is authorized
if (!isset($_SESSION['donor_id'])) {
    header("Location: login.php");
    exit();
}

$format = $_GET['format'] ?? 'excel'; // Default to Excel
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$sql = "SELECT t.*, 
        d.first_name as donor_name, 
        co.organization_name as charity_name,
        t.amount,
        t.status,
        t.transaction_date,
        t.payment_method
        FROM transactions t
        JOIN donors d ON t.donor_id = d.id
        JOIN charity_organizations co ON t.charity_id = co.id
        WHERE t.transaction_date BETWEEN ? AND ?
        ORDER BY t.transaction_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($format === 'excel') {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="transactions.xls"');
    
    // Print headers
    echo "Transaction Date\tDonor\tCharity\tAmount\tStatus\tPayment Method\n";
    
    // Print data
    while ($row = mysqli_fetch_assoc($result)) {
        echo "{$row['transaction_date']}\t{$row['donor_name']}\t{$row['charity_name']}\t" .
             "KES {$row['amount']}\t{$row['status']}\t{$row['payment_method']}\n";
    }
} else if ($format === 'pdf') {
    require_once('../vendor/tcpdf/tcpdf.php');
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Your System');
    $pdf->SetTitle('Transaction Report');
    $pdf->AddPage();
    
    // Add content to PDF
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Transaction Report', 0, 1, 'C');
    
    // Add table headers
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(40, 7, 'Date', 1);
    $pdf->Cell(30, 7, 'Donor', 1);
    $pdf->Cell(40, 7, 'Charity', 1);
    $pdf->Cell(30, 7, 'Amount', 1);
    $pdf->Cell(25, 7, 'Status', 1);
    $pdf->Cell(25, 7, 'Method', 1);
    $pdf->Ln();
    
    // Add data
    $pdf->SetFont('helvetica', '', 10);
    while ($row = mysqli_fetch_assoc($result)) {
        $pdf->Cell(40, 6, $row['transaction_date'], 1);
        $pdf->Cell(30, 6, $row['donor_name'], 1);
        $pdf->Cell(40, 6, $row['charity_name'], 1);
        $pdf->Cell(30, 6, 'KES ' . $row['amount'], 1);
        $pdf->Cell(25, 6, $row['status'], 1);
        $pdf->Cell(25, 6, $row['payment_method'], 1);
        $pdf->Ln();
    }
    
    $pdf->Output('transactions.pdf', 'D');
}

mysqli_close($conn);
?> 