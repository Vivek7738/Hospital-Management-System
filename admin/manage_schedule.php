<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has the appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'doctor'])) {
    header("Location: ../login.php");
    exit;
}

// Initialize variables
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$dashboard_link = ($user_role === 'admin') ? 'admin_dashboard.php' : '../doctor/doctor_dashboard.php';
$message = "";

// Fetch doctors for admin
$doctors = [];
if ($user_role === 'admin') {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'doctor'");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    $stmt->close();
} else {
    // For doctors, add only their own information
    $doctors[] = ['id' => $user_id, 'username' => $username];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add Schedule
    if (isset($_POST['add_schedule'])) {
        $doctor_id = ($user_role === 'admin') ? $_POST['doctor_id'] : $user_id;
        $availability = trim($_POST['availability']);

        if (!empty($availability)) {
            $stmt = $conn->prepare("INSERT INTO schedules (doctor_id, availability) VALUES (?, ?)");
            $stmt->bind_param("is", $doctor_id, $availability);
            if ($stmt->execute()) {
                $message = "Schedule added successfully!";
            } else {
                $message = "Error adding schedule: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Availability cannot be empty.";
        }
    }

    // Update Schedule
    if (isset($_POST['update_schedule'])) {
        $schedule_id = $_POST['schedule_id'];
        $availability = trim($_POST['availability']);

        if (!empty($availability)) {
            // Check ownership or admin privilege
            $stmt = $conn->prepare("SELECT doctor_id FROM schedules WHERE id = ?");
            $stmt->bind_param("i", $schedule_id);
            $stmt->execute();
            $stmt->bind_result($schedule_doctor_id);
            $stmt->fetch();
            $stmt->close();

            if ($schedule_doctor_id == $user_id || $user_role === 'admin') {
                $stmt = $conn->prepare("UPDATE schedules SET availability = ? WHERE id = ?");
                $stmt->bind_param("si", $availability, $schedule_id);
                if ($stmt->execute()) {
                    $message = "Schedule updated successfully!";
                } else {
                    $message = "Error updating schedule: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "You are not authorized to update this schedule.";
            }
        } else {
            $message = "Availability cannot be empty.";
        }
    }

    // Delete Schedule
    if (isset($_POST['delete_schedule'])) {
        $schedule_id = $_POST['schedule_id'];

        // Check ownership or admin privilege
        $stmt = $conn->prepare("SELECT doctor_id FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $stmt->bind_result($schedule_doctor_id);
        $stmt->fetch();
        $stmt->close();

        if ($schedule_doctor_id == $user_id || $user_role === 'admin') {
            $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->bind_param("i", $schedule_id);
            if ($stmt->execute()) {
                $message = "Schedule deleted successfully!";
            } else {
                $message = "Error deleting schedule: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Only Doctor and Admins can delete schedules.";
        }
    }
}

// Fetch schedules to display
$schedules = [];
if ($user_role === 'admin') {
    $stmt = $conn->prepare("SELECT s.id, u.username, s.availability FROM schedules s JOIN users u ON s.doctor_id = u.id ORDER BY s.id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT s.id, u.username, s.availability FROM schedules s JOIN users u ON s.doctor_id = u.id WHERE s.doctor_id = ? ORDER BY s.id DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    $stmt->close();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Add your styles here */
        /* General Body Styles */
body {
    font-family: 'Open Sans', sans-serif;
    background-color: #f4f7f8;
    margin: 0;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Top Bar Styles */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 15px 30px;
    background-color: #007bff;
    color: #fff;
}

.top-bar h4 {
    margin: 0;
    font-size: 24px;
}

.clock-container {
    flex-grow: 1;
    text-align: center;
}

.clock {
    font-size: 22px;
}

.logout-dropdown {
    position: relative;
    display: inline-block;
}

.logout-dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #0056b3;
    min-width: 160px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    z-index: 1;
}

.logout-dropdown-content a {
    color: #fff;
    padding: 10px 15px;
    text-decoration: none;
    display: block;
    border-radius: 8px;
    transition: background 0.3s;
}

.logout-dropdown-content a:hover {
    background-color: #004494;
}

.logout-dropdown:hover .logout-dropdown-content {
    display: block;
}

/* Sidebar Styles */
.sidebar {
    background: #007bff;
    padding: 20px;
    width: 250px;
    border-radius: 8px;
    margin-right: 20px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
}

.sidebar h2 {
    color: #fff;
    font-size: 24px;
    margin-bottom: 20px;
}

.nav-links {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.nav-links a {
    color: #fff;
    text-decoration: none;
    padding: 12px 15px;
    border-radius: 8px;
    transition: background 0.3s, transform 0.2s;
}

.nav-links a:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

/* Dashboard Styles */
.dashboard {
    flex: 1;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    margin: 20px;
}

/* Heading Styles */
h1 {
    color: #333;
    margin-bottom: 20px;
    font-size: 32px;
    text-align: center;
}

h4 {
    margin-bottom: 20px;
    font-size: 20px;
}

/* Message Styles */
.message {
    background: #2ecc71;
    color: #fff;
    padding: 12px;
    border-radius: 8px;
    margin-top: 15px;
    text-align: center;
}

/* Form Container Styles */
.form-container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

/* Form Styles */
.form-container form {
    flex: 1;
    min-width: 300px;
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

/* Button Styles */
.btn-primary, .btn-info, .btn-danger {
    padding: 12px 25px;
    color: #fff;
    border: none;
    border-radius: 30px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-primary {
    background: #1abc9c;
}

.btn-primary:hover {
    background: #16a085;
}

.btn-info {
    background: #3498db;
}

.btn-info:hover {
    background: #2980b9;
}

.btn-danger {
    background: #e74c3c;
}

.btn-danger:hover {
    background: #c0392b;
}
h4::first-letter{
            font-family: Lucida Calligraphy;
            font-size:1cm;
            color:red;
        }

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

table th, table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

table th {
    background-color: #f2f2f2;
    font-weight: bold;
}

table tr:hover {
    background-color: #f9f9f9;
}

/* Modal Styles */
.modal-content {
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.modal-header {
    border-bottom: 1px solid #ddd;
}

.modal-title {
    font-size: 18px;
}

.modal-footer {
    border-top: 1px solid #ddd;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        margin-right: 0;
    }
    .dashboard {
        padding: 20px;
        margin-left: 0;
        max-width: 100%;
    }
}

    </style>
</head>
<body oncontextmenu="return false;" onkeydown="return disableCtrlU(event);">

<div class="top-bar">
<h4><b>Swasthya</b></h4>
        <div class="clock-container">
            <div class="clock" id="clock"></div>
        </div>
    <div class="logout-dropdown">
    <span>Welcome, <?php echo ucfirst($_SESSION['role']); ?> <i class="fas fa-user-circle"></i></span>
        <div class="logout-dropdown-content">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>


<div class="d-flex">
    <div class="sidebar">
    <h2>Manage Schedules</h2>
        <div class="nav-links">
            <a href="<?php echo $dashboard_link; ?>"><i class="fa fa-home" aria-hidden="true"></i> Home</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="view_reports.php"><i class="fas fa-file-alt"></i> View Reports</a>
                <a href="manage_doctors.php"><i class="fas fa-user-md"></i> Manage Doctors</a>
                <a href="register_patient.php"><i class="fas fa-user-plus"></i> Patient Registration</a>
                <a href="manage_records.php"><i class="fas fa-clipboard"></i> Patient Management</a>
                <a href="manage_invoices.php"><span class="rupee-symbol">₹</span> Manage Invoices</a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'doctor'): ?>
                <a href="../admin/manage_records.php"><i class="fas fa-users"></i> Manage Patients</a>
                <a href="../admin/view_reports.php"><i class="fas fa-file-alt"></i> View Reports</a>
                <a href="clinical_documentation.php"><i class="fas fa-notes-medical"></i> Clinical Documentation</a>
                <a href="test_lab_results.php"><i class="fas fa-vials"></i> Test and Lab Results</a>
                <a href="communication.php"><i class="fas fa-comments"></i> Communication</a>
                <a href="medical.php"><i class="fas fa-prescription-bottle-alt"></i> Prescription</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard">
            <h1>Manage Schedules</h1>
            <?php if (!empty($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <div class="form-container">
    <!-- Add New Schedule Form -->
    <div class="form-container">
        <form method="POST" action="">
            <h3>Add New Schedule</h3>
            <div class="form-group">
                <?php if ($user_role === 'admin'): ?>
                    <label for="doctor_id">Select Doctor</label>
                    <select class="form-control" id="doctor_id" name="doctor_id" required>
                        <option value="">Select Doctor</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="doctor_id" value="<?php echo $user_id; ?>">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="availability">Availability</label>
                <input type="text" class="form-control" id="availability" name="availability" required>
            </div>
            <button type="submit" name="add_schedule" class="btn btn-primary">Add Schedule</button>
        </form>
    </div>
</div>

                <!-- Update Schedule Modal -->
<div class="modal fade" id="updateModal<?php echo $schedule['id']; ?>" tabindex="-1" aria-labelledby="updateModalLabel<?php echo $schedule['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel<?php echo $schedule['id']; ?>">Update Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if ($user_role === 'admin'): ?>
                        <div class="form-group">
                            <label for="doctor_name">Doctor Name</label>
                            <input type="text" class="form-control" id="doctor_name" value="<?php echo htmlspecialchars($schedule['username']); ?>" readonly>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="availability<?php echo $schedule['id']; ?>">Availability</label>
                        <input type="text" class="form-control" id="availability<?php echo $schedule['id']; ?>" name="availability" value="<?php echo htmlspecialchars($schedule['availability']); ?>" required>
                    </div>
                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="update_schedule" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End of Update Schedule Modal -->

            </div>
            <hr>
            <!-- Schedules Table -->
            <table>
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(strtoupper('DR.' .$schedule['username'])); ?></td>
                            <td><?php echo htmlspecialchars($schedule['availability']); ?></td>
                            <td>
                                <form action="" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                    <input type="text" name="availability" value="<?php echo htmlspecialchars($schedule['availability']); ?>" required>
                                    <button type="submit" name="update_schedule" class="btn-info">Update</button>
                                </form>
                                <form action="" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                    <button type="submit" name="delete_schedule" class="btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function updateClock() {
            var now = new Date();
            var hours = now.getHours();
            var minutes = now.getMinutes();
            var seconds = now.getSeconds();
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            var time = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
            document.getElementById('clock').textContent = time;
        }
        setInterval(updateClock, 1000);
    </script>
</body>
</html>