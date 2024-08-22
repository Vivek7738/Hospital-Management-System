<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch doctor details
$doctorQuery = $conn->query("SELECT username, email FROM users WHERE id = '$userId'");
$doctor = $doctorQuery->fetch_assoc();

// Fetch list of patients
$patientsQuery = $conn->query("SELECT id, username FROM users WHERE role = 'patient'");

// Directory where PDF files are stored
$pdfDirectory = '../Pdf/';

// Fetch PDF files uploaded by doctors grouped by patients
$pdfFilesQuery = $conn->query("SELECT pdf_files.*, users.username AS patient_name 
                               FROM pdf_files 
                               LEFT JOIN users ON pdf_files.patient_id = users.id 
                               WHERE pdf_files.doctor_id = '$userId'");

$pdfFiles = [];
while ($row = $pdfFilesQuery->fetch_assoc()) {
    $pdfFiles[$row['patient_id']][] = $row; // Group files by patient ID
}

$userRole = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Results - Doctor Dashboard</title>
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
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        .pdf-table {
            margin-top: 20px;
            width: 100%;
        }
        .pdf-table th, .pdf-table td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .upload-form {
            margin-top: 30px;
        }
    </style>
</head>
<body oncontextmenu="return false;" onkeydown="return disableCtrlU(event);">
<div class="top-bar">
        <div class="clock-container">
            <div class="clock" id="clock"></div>
        </div>
        <div class="logout-dropdown">
        <span>Welcome, <?php echo htmlspecialchars($doctor['username']); ?> <i class="fas fa-user-circle"></i></span>
            <div class="logout-dropdown-content">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="d-flex">
        <div class="sidebar">
        <h2>Doctor Dashboard</h2>
            <div class="nav-links">
                <a href="doctor_dashboard.php"><i class="fa fa-home" aria-hidden="true"></i> Home</a>
                <a href="../admin/manage_records.php"><i class="fas fa-users"></i> Manage Patients</a>
                <a href="../admin/view_reports.php"><i class="fas fa-file-alt"></i> View Reports</a>
                <a href="../admin/manage_schedule.php"><i class="fas fa-calendar-check"></i> Appointment Scheduling</a>
                <a href="view_appointments.php"><i class="fas fa-calendar-day"></i> View Appointments</a>
                <a href="clinical_documentation.php"><i class="fas fa-notes-medical"></i> Clinical Documentation</a>
                <a href="test_lab_results.php"><i class="fas fa-vials"></i> Test and Lab Results</a>
                <a href="communication.php"><i class="fas fa-comments"></i> Communication</a>
                <a href="medical.php"><i class="fas fa-prescription-bottle-alt"></i> Medical Orders</a>
                <a href="billing.php"><i class="fas fa-rupee-sign"></i> Billing</a>
            </div>
        </div>

        <div class="dashboard">
            <h1>Lab Results</h1>

            <?php foreach ($pdfFiles as $patientId => $files) { ?>
                <h2><?php echo htmlspecialchars($files[0]['patient_name']); ?></h2>
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Uploaded Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file) { 
                            $uploadedDate = date('F j, Y, g:i a', strtotime($file['uploaded_at']));
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo $pdfDirectory . htmlspecialchars($file['file_name']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($file['file_name']); ?>
                                </a>
                            </td>
                            <td><?php echo $uploadedDate; ?></td>
                            <td>
                                <form action="delete_pdf.php" method="post" style="display: inline-block;">
                                    <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>

            <div class="upload-form">
                <h2>Upload PDF File</h2>
                <form action="upload_pdf.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="patient_id">Select Patient:</label>
                        <select name="patient_id" id="patient_id" class="form-control">
                            <?php while ($patient = $patientsQuery->fetch_assoc()) { ?>
                                <option value="<?php echo $patient['id']; ?>"><?php echo htmlspecialchars($patient['username']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pdf_file">Upload PDF File:</label>
                        <input type="file" name="pdf_file" id="pdf_file" class="form-control-file">
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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
    </script>
</body>
</html>
