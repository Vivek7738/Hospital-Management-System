<?php
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');
require_once('../config.php');

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['invoice_id'])) {
    $invoiceId = $_GET['invoice_id'];
    $patientId = $_SESSION['user_id'];

    // Fetch invoice details
    $invoiceQuery = $conn->prepare("SELECT date, amount, description, status FROM invoices WHERE id = ? AND patient_id = ?");
    $invoiceQuery->bind_param("ii", $invoiceId, $patientId);
    $invoiceQuery->execute();
    $invoice = $invoiceQuery->get_result()->fetch_assoc();
    $invoiceQuery->close();

    // Fetch patient details
    $patientQuery = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $patientQuery->bind_param("i", $patientId);
    $patientQuery->execute();
    $patient = $patientQuery->get_result()->fetch_assoc();
    $patientQuery->close();

    if ($invoice && $patient) {
        // Page size A6
        $pdf = new TCPDF('P', 'mm', [105, 148], true, 'UTF-8', false);
        $pdf->SetMargins(10, 10, 10); // Reduced margins
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 8); // Smaller font size

        // Set the logo and header
        $pdf->Image('../admin/logo.png', 10, 10, 20, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'INVOICE', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Swasthya Hospital', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Malad (W), Mumbai, Maharashtra', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Phone: (+91) 1234567890 | Email: info@swasthya.com', 0, 1, 'C');

        // Patient and Invoice details
        $patientName = strtoupper($patient['username']);
        $html = "
        <style>
            .invoice-header {
                border-bottom: 2px solid #007bff;
                margin-bottom: 15px;
                padding-bottom: 5px;
            }
            .invoice-header h2 {
                margin: 0;
                font-size: 14px;
                color: #007bff;
            }
            .invoice-header p {
                margin: 3px 0;
                font-size: 10px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 5px;
                text-align: left;
                font-size: 8px;
            }
            th {
                background-color: #007bff;
                color: white;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .footer {
                text-align: center;
                font-size: 8px;
                margin-top: 10px;
                border-top: 1px solid #ddd;
                padding-top: 5px;
            }
        </style>
        <div class='invoice-header'>
            <h2>Invoice Details</h2>
            <p><strong>Patient:</strong> {$patientName}</p>
            <p><strong>Date:</strong> {$invoice['date']}</p>
            <p><strong>Invoice ID:</strong> #$invoiceId</p>
        </div>
        <table>
            <tr>
                <th>Description</th>
                <td>{$invoice['description']}</td>
            </tr>
            <tr>
                <th>Amount</th>
                <td>RS.{$invoice['amount']}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{$invoice['status']}</td>
            </tr>
        </table>
        <p><strong>Note:</strong> Thank you for choosing our hospital. Please visit us again!</p>
        <div class='footer'>
            <em>For any queries, contact us at (123) 456-7890 or billing@swasthya.com.</em>
        </div>
        ";

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output("invoice_$invoiceId.pdf", 'D');
    } else {
        echo "Invoice not found.";
    }
} else {
    echo "No invoice ID specified.";
}
?>
