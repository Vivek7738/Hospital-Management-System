<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch doctor details
$doctorQuery = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$doctorQuery->bind_param("i", $userId);
$doctorQuery->execute();
$doctorResult = $doctorQuery->get_result();
$doctor = $doctorResult->fetch_assoc();

// Handle form submission for creating a new clinical documentation record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_documentation'])) {
    $patient_id = $_POST['patient_id'];
    $treatment_details = $_POST['treatment_details'];
    $clinical_notes = $_POST['clinical_notes'];

    $stmt = $conn->prepare("INSERT INTO clinical_documentation (doctor_id, patient_id, treatment_details, clinical_notes, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiss", $userId, $patient_id, $treatment_details, $clinical_notes);

    if ($stmt->execute()) {
        $success_message = "Clinical documentation added successfully.";
    } else {
        $error_message = "Failed to add clinical documentation.";
    }
}

// Delete functionality
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $docId = $_GET['delete'];

    $deleteStmt = $conn->prepare("DELETE FROM clinical_documentation WHERE id = ? AND doctor_id = ?");
    $deleteStmt->bind_param("ii", $docId, $userId);

    if ($deleteStmt->execute()) {
        $delete_success_message = "Clinical documentation deleted successfully.";
    } else {
        $delete_error_message = "Failed to delete clinical documentation.";
    }
}

// Share functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['share_document'])) {
    $docId = $_POST['doc_id'];
    $shareWith = $_POST['share_with'];

    if ($shareWith === 'all') {
        // Share with all doctors
        $shareStmt = $conn->prepare("UPDATE clinical_documentation SET shared_with_doctors = 'all' WHERE id = ?");
        $shareStmt->bind_param("i", $docId);
    } elseif ($shareWith === 'specific' && isset($_POST['share_doctor_id']) && is_numeric($_POST['share_doctor_id'])) {
        // Share with a specific doctor
        $shareDoctorId = $_POST['share_doctor_id'];
        $shareStmt = $conn->prepare("UPDATE clinical_documentation SET shared_with_doctors = CONCAT_WS(',', shared_with_doctors, ?) WHERE id = ?");
        $shareStmt->bind_param("ii", $shareDoctorId, $docId);
    }

    if ($shareStmt->execute()) {
        $share_success_message = "Clinical documentation shared successfully.";
    } else {
        $share_error_message = "Failed to share clinical documentation.";
    }
}

// Fetch patients for the dropdown
$patientsQuery = $conn->query("SELECT id, username FROM users WHERE role='patient'");
$patients = $patientsQuery->fetch_all(MYSQLI_ASSOC);

// Fetch doctors for the dropdown in the modal
$doctorsQuery = $conn->query("SELECT id, username FROM users WHERE role='doctor'");
$doctors = $doctorsQuery->fetch_all(MYSQLI_ASSOC);

// Fetch clinical documentation records
$clinical_docs_query = "SELECT cd.*, u.username AS patient_username FROM clinical_documentation cd JOIN users u ON cd.patient_id = u.id WHERE cd.doctor_id = ? ORDER BY cd.created_at DESC";
$clinical_docs_stmt = $conn->prepare($clinical_docs_query);
$clinical_docs_stmt->bind_param("i", $userId);
$clinical_docs_stmt->execute();
$clinical_docs_result = $clinical_docs_stmt->get_result();
$clinical_docs = $clinical_docs_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Documentation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Add your CSS styling here */
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
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
            <h1>Clinical Documentation</h1>
            
            <?php if (isset($success_message)) { echo "<div class='alert alert-success'>$success_message</div>"; } ?>
            <?php if (isset($error_message)) { echo "<div class='alert alert-danger'>$error_message</div>"; } ?>
            <?php if (isset($delete_success_message)) { echo "<div class='alert alert-success'>$delete_success_message</div>"; } ?>
            <?php if (isset($delete_error_message)) { echo "<div class='alert alert-danger'>$delete_error_message</div>"; } ?>
            <?php if (isset($share_success_message)) { echo "<div class='alert alert-success'>$share_success_message</div>"; } ?>
            <?php if (isset($share_error_message)) { echo "<div class='alert alert-danger'>$share_error_message</div>"; } ?>

            <form action="clinical_documentation.php" method="POST">
                <input type="hidden" name="create_documentation" value="1">
                <div class="form-group">
                    <label for="patient_id">Patient</label>
                    <select class="form-control" id="patient_id" name="patient_id" required>
                        <option value="">Select Patient</option>
                        <?php foreach ($patients as $patient) { ?>
                            <option value="<?php echo $patient['id']; ?>"><?php echo htmlspecialchars($patient['username']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="treatment_details">Treatment Details</label>
                    <textarea class="form-control" id="treatment_details" name="treatment_details" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="clinical_notes">Clinical Notes</label>
                    <textarea class="form-control" id="clinical_notes" name="clinical_notes" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Add Documentation</button>
            </form>

            <h2 class="mt-4">Existing Clinical Documentation</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Treatment Details</th>
                        <th>Clinical Notes</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clinical_docs as $doc) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doc['id']); ?></td>
                            <td><?php echo htmlspecialchars($doc['patient_username']); ?></td>
                            <td><?php echo htmlspecialchars($doc['treatment_details']); ?></td>
                            <td><?php echo htmlspecialchars($doc['clinical_notes']); ?></td>
                            <td><?php echo htmlspecialchars($doc['created_at']); ?></td>
                            <td>
                                <a href="clinical_documentation.php?delete=<?php echo $doc['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this documentation?');">Delete</a>
                                <!-- Button trigger modal -->
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#shareModal<?php echo $doc['id']; ?>">Share</button>
                            </td>
                        </tr>

                        <!-- Modal for sharing with specific doctor -->
                        <div class="modal fade" id="shareModal<?php echo $doc['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="shareModalLabel<?php echo $doc['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="shareModalLabel<?php echo $doc['id']; ?>">Share with Specific Doctor</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form action="clinical_documentation.php" method="POST">
                                            <input type="hidden" name="share_document" value="1">
                                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                            <input type="hidden" name="share_with" value="specific">
                                            <div class="form-group">
                                                <label for="share_doctor_id<?php echo $doc['id']; ?>">Select Doctor</label>
                                                <select class="form-control" id="share_doctor_id<?php echo $doc['id']; ?>" name="share_doctor_id" required>
                                                    <?php foreach ($doctors as $doctor) { ?>
                                                        <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['username']); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Share</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
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
