<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $appointmentId = $_GET['id'];

    // Delete the appointment
    $deleteQuery = $conn->prepare("DELETE FROM appointments WHERE id = ? AND patient_id = ?");
    $deleteQuery->bind_param("ii", $appointmentId, $_SESSION['user_id']);
    $deleteQuery->execute();
    $deleteQuery->close();

    header("Location: appointments.php");
    exit;
}
?>

