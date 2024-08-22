// get_doctors.php
<?php
require_once '../config.php';

$doctorsQuery = $conn->prepare("SELECT id, username FROM users WHERE role = 'doctor'");
$doctorsQuery->execute();
$result = $doctorsQuery->get_result();

$doctors = [];
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

echo json_encode($doctors);
?>
