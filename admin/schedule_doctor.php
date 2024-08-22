<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$doctorId = $_GET['id'] ?? null;
if (!$doctorId) {
    header("Location: manage_doctors.php");
    exit;
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $availability = $_POST['availability'];

    $stmt = $conn->prepare("UPDATE schedules SET availability = ? WHERE doctor_id = ?");
    $stmt->bind_param("si", $availability, $doctorId);

    if ($stmt->execute()) {
        $message = "Schedule updated successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT availability FROM schedules WHERE doctor_id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $stmt->bind_result($availability);
    $stmt->fetch();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Doctor</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #eef2f3; /* Light gray background */
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .dashboard {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
            background: #fff; /* White background */
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        label {
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
            display: block;
            text-align: left;
        }

        textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            height: 150px;
            resize: none;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #3498db;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s;
        }

        button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .back-button-container {
            text-align: center;
            margin-top: 20px;
        }

        .back-button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s;
        }

        .back-button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .back-button a {
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>Schedule Doctor</h1>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="schedule_doctor.php?id=<?php echo $doctorId; ?>">
            <label for="availability">Availability:</label>
            <textarea id="availability" name="availability" required><?php echo htmlspecialchars($availability); ?></textarea>
            <button type="submit">Update Schedule</button>
        </form>
        <div class="back-button-container">
            <button class="back-button"><a href="manage_doctors.php">Back to Manage Doctors</a></button>
        </div>
    </div>
</body>
</html>
