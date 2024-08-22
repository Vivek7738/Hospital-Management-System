<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit;
}

// Fetch patient's information
$patientId = $_SESSION['user_id'];
$patientQuery = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$patientQuery->bind_param("i", $patientId);
$patientQuery->execute();
$patient = $patientQuery->get_result()->fetch_assoc();
$patientQuery->close();

// Fetch patient's appointments
$appointmentsQuery = $conn->prepare("SELECT date, time, doctor_name, note FROM appointments WHERE patient_id = ?");
$appointmentsQuery->bind_param("i", $patientId);
$appointmentsQuery->execute();
$appointments = $appointmentsQuery->get_result();
$appointmentsQuery->close();

// Fetch notifications if any
$notifications = $conn->query("SELECT * FROM notifications WHERE recipient_group = 'all' OR recipient_group = 'patient'");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
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
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .card {
            margin: 15px;
            padding: 20px;
            background: #007bff;
            color: #fff;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card h2 {
            margin: 0;
            font-size: 24px;
        }

        .card p {
            font-size: 18px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                margin-bottom: 20px;
            }

            .dashboard {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .nav-links a {
                font-size: 14px;
                padding: 8px;
            }

            .card {
                margin: 10px;
                padding: 15px;
            }

            .card h2 {
                font-size: 20px;
            }

            .card p {
                font-size: 16px;
            }
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

        .notification {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
        }

        .notification h3 {
            margin: 0;
            font-size: 18px;
        }

        .notification p {
            margin: 5px 0;
        }
    </style>
</head>
<body oncontextmenu="return false;" onkeydown="return disableCtrlU(event);">
    <div class="top-bar">
        <div class="clock-container">
            <div class="clock" id="clock"></div>
        </div>
        <div class="logout-dropdown">
            <span>Welcome, <?php echo htmlspecialchars(strtoupper($patient['username'])); ?> <i class="fas fa-user-circle"></i></span>
            <div class="logout-dropdown-content">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap">
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
            <h1>Welcome, <?php echo htmlspecialchars(strtoupper($patient['username'])); ?>!</h1>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <h2><i class="fas fa-user"></i> Your Details</h2>
                        <hr>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars(strtoupper($patient['username'])); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <h2><i class="fas fa-calendar-day"></i> My Appointments</h2>
                        <hr>
                        <?php if ($appointments->num_rows > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Doctor</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appointment['date']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['time']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['note']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No appointments scheduled.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <h2>Notifications</h2>
            <?php while ($notification = $notifications->fetch_assoc()) { ?>
                <div class="notification">
                    <h3><?php echo htmlspecialchars($notification['subject']); ?></h3>
                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                </div>
            <?php } ?>
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
