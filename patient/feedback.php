<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = $_SESSION['user_id'];
    $feedback = $_POST['feedback'];

    $stmt = $conn->prepare("INSERT INTO feedback (patient_id, feedback) VALUES (?, ?)");
    $stmt->bind_param("is", $patientId, $feedback);

    if ($stmt->execute()) {
        echo "<script>alert('Thank you for your feedback!'); window.location.href = 'patient_dashboard.php';</script>";
        exit;
    } else {
        $feedbackMsg = "Error submitting feedback. Please try again.";
    }

    $stmt->close();
}

$patientId = $_SESSION['user_id'];
$patientQuery = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$patientQuery->bind_param("i", $patientId);
$patientQuery->execute();
$patient = $patientQuery->get_result()->fetch_assoc();
$patientQuery->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Feedback</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #eef2f3;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
        }

        .clock-container {
            flex-grow: 1;
            text-align: center;
        }

        .clock {
            font-size: 24px;
        }

        .logout-dropdown {
            position: relative;
            display: inline-block;
        }

        .logout-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #007bff;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 8px;
        }

        .logout-dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .logout-dropdown-content a:hover {
            background-color: #0056b3;
        }

        .logout-dropdown:hover .logout-dropdown-content {
            display: block;
        }

        .sidebar {
            background: #007bff;
            padding: 15px;
            width: 200px;
            border-radius: 8px;
            margin-right: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }

        .sidebar h2 {
            color: #fff;
            font-size: 26px;
            margin-bottom: 20px;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            padding: 10px;
            border-radius: 30px;
            transition: background 0.3s, transform 0.2s;
        }

        .nav-links a:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .dashboard {
            flex: 1;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        .feedback-container {
            text-align:center;
            background: #fff;
            padding: 50px;
            border-radius: 12px;
            margin-right:400px;
            width: 100%;
            max-width: 700px;
        }

        .feedback-container h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
            align-content:center;
        }

        .btn-submit {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: background 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            background-color: #ffffff;
        }
    </style>
</head>
<body oncontextmenu="return false;" onkeydown="return disableCtrlU(event);">
<div class="top-bar">
        <div class="clock-container">
            <div class="clock" id="clock"></div>
        </div>
        <div class="logout-dropdown">
        <span>Welcome, <?php echo htmlspecialchars($patient['username']); ?> <i class="fas fa-user-circle"></i></span>
            <div class="logout-dropdown-content">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="d-flex">
        <div class="sidebar">
        <h2>Patient Dashboard</h2>
            <div class="nav-links">
                <a href="patient_dashboard.php"><i class="fa fa-home"></i> Home</a>
                <a href="appointments.php"><i class="fas fa-calendar-day"></i> View Appointments</a>
                <a href="lab_file.php"><i class="fas fa-file-medical-alt"></i> My Reports</a>
                <a href="comm.php"><i class="fas fa-comments"></i> Communication</a>
                <a href="medical.php"><i class="fas fa-prescription-bottle-alt"></i> My Prescription</a>
                <a href="billing.php"><i class="fas fa-rupee-sign"></i> My Bills</a>
                <a href="feedback.php"><i class="fas fa-comment-alt"></i> Feedback</a>
            </div>
        </div>
<div class="dashboard">
        <div class="feedback-container">
            <h2><i class="fas fa-comments"></i> Feedback</h2>
            <form method="POST" action="feedback.php">
                <div class="form-group">
                    <label for="feedback">Your Feedback</label>
                    <textarea class="form-control" id="feedback" name="feedback" rows="6" required></textarea>
                </div>
                <button type="submit" class="btn btn-submit">Submit Feedback</button>
                <?php if (isset($feedbackMsg)) : ?>
                    <p class="text-danger mt-3"><?= htmlspecialchars($feedbackMsg) ?></p>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

    <script>
        function updateClock() {
            const clockElement = document.getElementById('clock');
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            clockElement.textContent = `${hours}:${minutes}`;
        }

        setInterval(updateClock, 1000);
        updateClock(); // initial call

        // Disable certain key combinations
        function disableCtrlU(event) {
            if (event.ctrlKey && (event.key === 'u' || event.key === 'U')) {
                return false;
            }
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
