<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$doctorId = $_GET['id'] ?? null;
if ($doctorId) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'doctor'");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $stmt->close();
}

header("Location: manage_doctors.php");
exit;
?>
