<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $patientId = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='patient'");
    $stmt->bind_param("i", $patientId);

    if ($stmt->execute()) {
        header("Location: manage_records.php?message=Patient deleted successfully!");
        exit;
    } else {
        header("Location: manage_records.php?message=Error: " . $stmt->error);
        exit;
    }

    $stmt->close();
} else {
    header("Location: manage_records.php");
    exit;
}
