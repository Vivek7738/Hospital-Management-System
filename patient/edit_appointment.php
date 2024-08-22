<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $appointmentId = intval($_POST['id']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $doctorId = $_POST['doctor_id'];
    $note = $_POST['note'];

    // Update appointment
    $stmt = $conn->prepare("UPDATE appointments SET date = ?, time = ?, doctor_id = ?, note = ? WHERE id = ?");
    $stmt->bind_param("ssisi", $date, $time, $doctorId, $note, $appointmentId);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => 'Appointment updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update appointment']);
    }

    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>
