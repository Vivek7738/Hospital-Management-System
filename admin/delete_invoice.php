<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $invoice_id = $_GET['id'];

    // Delete the invoice
    $stmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->bind_param("i", $invoice_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Invoice deleted successfully!";
    } else {
        $_SESSION['message'] = "Error deleting invoice: " . $stmt->error;
    }

    header("Location: manage_invoices.php");
    exit;
}
?>
