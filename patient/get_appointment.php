<?php
require_once '../config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$appointmentId = intval($_GET['id']);

// Fetch appointment details
$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Appointment not found']);
    exit;
}

$appointment = $result->fetch_assoc();
$stmt->close();

// Output appointment details in JSON format
echo json_encode($appointment);
?>
