<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetching doctors
$doctors = $conn->query("SELECT id, username, email FROM users WHERE role='doctor'");

// Fetching admin name
$admin = $conn->query("SELECT username FROM users WHERE role='admin'")->fetch_assoc();
$admin_name = $admin['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors</title>
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
            flex-grow: 1;
            text-align: center;
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
            width: 200px;
            background: #007bff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            margin-right: 20px;
        }

        .sidebar h2 {
            color: #fff;
            margin: 0;
            font-size: 22px;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: #0056b3;
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

        .buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .buttons a {
            text-decoration: none;
            padding: 10px 20px;
            color: white;
            border-radius: 5px;
            background-color: #3498db;
            transition: background-color 0.3s ease, transform 0.2s;
            display: inline-block;
        }

        .buttons a:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .doctor-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .doctor-table th, .doctor-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .doctor-table th {
            background-color: #3498db;
            color: white;
            text-transform: uppercase;
            font-weight: normal;
            letter-spacing: 1px;
        }

        .doctor-table tr:hover {
            background-color: #f1f1f1;
        }

        .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .actions a {
            text-decoration: none;
            padding: 8px 15px;
            color: white;
            border-radius: 5px;
            transition: background-color 0.3s ease, transform 0.2s;
        }

        .actions .edit {
            background-color: #f39c12;
        }

        .actions .edit:hover {
            background-color: #e67e22;
            transform: translateY(-2px);
        }

        .actions .delete {
            background-color: #e74c3c;
        }

        .actions .delete:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }
        .rupee-symbol {
        font-family: 'FontAwesome', 'Arial', sans-serif;
        font-weight: bold;
    }

        @media (max-width: 768px) {
            .buttons {
                flex-direction: column;
                align-items: flex-start;
            }

            .buttons a {
                margin-bottom: 10px;
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
        <span>Welcome, <?php echo htmlspecialchars($admin_name); ?> <i class="fas fa-user-circle"></i></span>
        <div class="logout-dropdown-content">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<div class="d-flex">
    <div class="sidebar">
        <h2>Manage Doctors</h2>
        <div class="nav-links">
            <a href="admin_dashboard.php"><i class="fa fa-home" aria-hidden="true"></i> Home</a>
            <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
            <a href="view_reports.php"><i class="fas fa-file-alt"></i> View Reports</a>
            <a href="manage_schedule.php"><i class="fas fa-calendar-alt"></i> Doctor's Schedules</a>
            <a href="register_patient.php"><i class="fas fa-user-plus"></i> Patient Registration</a>
            <a href="manage_records.php"><i class="fas fa-clipboard"></i> Patient Management</a>
            <a href="manage_invoices.php"><span class="rupee-symbol">₹</span> Manage Invoices</a>
        </div>
    </div>

    <div class="dashboard">
        <h1>Manage Doctors</h1>
        <div class="buttons">
            <a href="admin_dashboard.php" class="back-dashboard">Back to Dashboard</a>
            <a href="add_doctor.php" class="add-doctor">Add New Doctor</a>
        </div>
        <table class="doctor-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($doctor = $doctors->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $doctor['id']; ?></td>
                    <td><?php echo htmlspecialchars($doctor['username']); ?></td>
                    <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                    <td class="actions">
                        <a href="edit_doctor.php?id=<?php echo $doctor['id']; ?>" class="edit">Edit</a>
                        <a href="delete_doctor.php?id=<?php echo $doctor['id']; ?>" class="delete"
                           onclick="return confirm('Are you sure you want to delete this doctor?');">Delete</a>
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
    updateClock();

    function disableCtrlU(event) {
            if (event.ctrlKey && (event.key === 'u' || event.key === 'U')) {
                return false;
            }
        }
</script>
</body>
</html>
