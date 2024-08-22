<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch patient details
$patientQuery = $conn->query("SELECT username, email FROM users WHERE id = '$userId'");
$patient = $patientQuery->fetch_assoc();

// Fetch PDF files uploaded by doctors for the logged-in patient with doctor details and upload date
$pdfFilesQuery = $conn->query("SELECT pdf_files.*, users.username AS doctor_name 
                               FROM pdf_files 
                               LEFT JOIN users ON pdf_files.doctor_id = users.id 
                               WHERE patient_id = '$userId'");

// Directory where PDF files are stored
$pdfDirectory = '../Pdf/';

$pdfFiles = [];
while ($row = $pdfFilesQuery->fetch_assoc()) {
    $pdfFiles[] = $row;
}

$userRole = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Results - Patient Dashboard</title>
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
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
        }

        .clock-container {
            flex-grow:1;
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
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            height: 100vh;
            width: 280px;
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
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #007bff;
        }
        .pdf-list a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        .pdf-list a:hover {
            text-decoration: underline;
        }
        .pdf-list hr {
            border-top: 1px solid #ccc;
            margin: 10px 0;
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
            <h2>Lab Reports</h2>
            <div class="nav-links">
            <a href="patient_dashboard.php"><i class="fa fa-home" aria-hidden="true"></i> Home</a>
                <a href="lab_file.php"><i class="fas fa-file-medical-alt"></i> Lab Reports</a>
                <a href="comm.php"><i class="fas fa-comments"></i> Communication</a>
                <a href="medical.php"><i class="fas fa-prescription-bottle-alt"></i> Prescription</a>
                <a href="billing.php"><i class="fas fa-rupee-sign"></i> Billing</a>
                <a href="feedback.php"><i class="fas fa-comment-alt"></i> Feedback</a>
            </div>
        </div>

        <div class="dashboard">
            <h1>Lab Results</h1>

            <div class="pdf-list">
                <?php if (empty($pdfFiles)) { ?>
                    <p>No PDF files uploaded for you yet.</p>
                <?php } else { ?>
                    <?php foreach ($pdfFiles as $file) { ?>
                        <a href="<?php echo htmlspecialchars($pdfDirectory . $file['file_name']); ?>" target="_blank">
                            <?php echo htmlspecialchars(strtoupper($file['file_name'])); ?>
                        </a>
                        <br>
                        Uploaded by: Dr. <?php echo htmlspecialchars(strtoupper($file['doctor_name'])); ?>
                        <br>
                        Uploaded on: <?php echo htmlspecialchars(!empty($file['uploaded_at']) ? date('F j, Y', strtotime($file['uploaded_at'])) : 'Date not available'); ?>
                        <hr>
                    <?php } ?>
                <?php } ?>
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
