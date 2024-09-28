<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor')) {
    header("Location: ../login.php");
    exit;
}

$dashboard_link = ($_SESSION['role'] === 'admin') ? 'admin_dashboard.php' : '../doctor/doctor_dashboard.php';

// Fetch all patients' records
$patients = $conn->query("SELECT id, username, email FROM users WHERE role='patient'");

$name = $conn->query("SELECT username FROM users WHERE role='admin'")->fetch_row()[0];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patient Records</title>
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

        h4::first-letter{
            font-family: Lucida Calligraphy;
            font-size:1cm;
            color:red;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .table {
            margin-top: 20px;
        }
        .back-button {
            margin: 10px 0;
            text-decoration: none;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #fff;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .rupee-symbol {
        font-family: 'FontAwesome', 'Arial', sans-serif;
        font-weight: bold;
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
            <span>Welcome, <?php echo $name; ?> <i class="fas fa-user-circle"></i></span>
            <div class="logout-dropdown-content">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="d-flex">
        <div class="sidebar">
            <h2>Patient Management</h2>
            <div class="nav-links">
                <a href="<?php echo $dashboard_link; ?>"><i class="fa fa-home" aria-hidden="true"></i> Home</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="view_reports.php"><i class="fas fa-file-alt"></i> View Reports</a>
                <a href="manage_doctors.php"><i class="fas fa-user-md"></i> Manage Doctors</a>
                <a href="manage_schedule.php"><i class="fas fa-calendar-alt"></i> Doctor's Schedules</a>
                <a href="register_patient.php"><i class="fas fa-user-plus"></i> Patient Registration</a>
                <a href="manage_invoices.php"><i class="fas fa-rupee-sign"></i> Manage Invoices</a>
            </div>
        </div>

        <div class="dashboard">
            <h1>Manage Patient Records</h1>
            <div class="button-container">
                <a href="register_patient.php" class="back-button">Register New Patient</a>
                <a href="<?php echo $dashboard_link; ?>" class="back-button">Back to Dashboard</a>
            </div>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($patient = $patients->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $patient['id']; ?></td>
                            <td><?php echo $patient['username']; ?></td>
                            <td><?php echo $patient['email']; ?></td>
                            <td>
                                <a href="edit_record.php?id=<?php echo $patient['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_record.php?id=<?php echo $patient['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
