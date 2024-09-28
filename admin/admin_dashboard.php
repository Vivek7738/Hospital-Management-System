<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$counts = $conn->query("SELECT role, COUNT(*) as total FROM users GROUP BY role");
$totalCounts = [];
while ($row = $counts->fetch_assoc()) {
    $totalCounts[$row['role']] = $row['total'];
}

$totalDoctors = $totalCounts['doctor'] ?? 0;
$totalPatients = $totalCounts['patient'] ?? 0;
$totalAdmins = $totalCounts['admin'] ?? 0;

$name = $conn->query("SELECT username FROM users WHERE role='admin'")->fetch_row()[0];

$doctors = $conn->query("SELECT username, email FROM users WHERE role='doctor'");
$patients = $conn->query("SELECT username, email FROM users WHERE role='patient'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientGroup = $_POST['recipient_group'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // Insert notification into the database
    $stmt = $conn->prepare("INSERT INTO notifications (recipient_group, subject, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $recipientGroup, $subject, $message);
    if ($stmt->execute()) {
        echo "Notification sent successfully!";
    } else {
        echo "Error sending notification: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        .rupee-symbol {
            font-family: 'FontAwesome', 'Arial', sans-serif;
            font-weight: bold;
        }
        h4::first-letter{
            font-family: Lucida Calligraphy;
            font-size:1cm;
            color:red;
        }

        @media (max-width: 768px) {
            .dashboard {
                padding: 20px;
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

        .form-container {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .form-group input, .form-group select, .form-group textarea, .btn-primary {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
        }

        .btn-primary {
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
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
            <h2>Admin Dashboard</h2>
            <div class="nav-links">
                <a href="admin_dashboard.php"><i class="fa fa-home" aria-hidden="true"></i> Home</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="view_reports.php"><i class="fas fa-file-alt"></i> View Reports</a>
                <a href="manage_doctors.php"><i class="fas fa-user-md"></i> Manage Doctors</a>
                <a href="manage_schedule.php"><i class="fas fa-calendar-alt"></i> Doctor's Schedules</a>
                <a href="register_patient.php"><i class="fas fa-user-plus"></i> Patient Registration</a>
                <a href="manage_records.php"><i class="fas fa-clipboard"></i> Patient Management</a>
                <a href="manage_invoices.php"><i class="fas fa-rupee-sign"></i> Manage Invoices</a>
                <a href="feed.php"><i class="fas fa-comment-alt"></i> Feedback</a>
            </div>
        </div>

        <div class="dashboard">
            <h1>Welcome, Admin!</h1>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <h2><i class="fas fa-procedures"></i> Total Patients</h2>
                        <p><?php echo $totalPatients; ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <h2><i class="fas fa-user-md"></i> Total Doctors</h2>
                        <p><?php echo $totalDoctors; ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <h2><i class="fas fa-user-shield"></i> Total Admins</h2>
                        <p><?php echo $totalAdmins; ?></p>
                    </div>
                </div>
            </div>

            <h2>List of Doctors</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($doctor = $doctors->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $doctor['username']; ?></td>
                            <td><?php echo $doctor['email']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <br>
            <h2>List of Patients</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($patient = $patients->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $patient['username']; ?></td>
                            <td><?php echo $patient['email']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <hr>
    <h2 id="send_notifications" style="text-align: center;">Send Notifications</h2>
    <div class="form-container">
        <form method="post" action="">
            <div class="form-group">
                <label for="recipient_group">Recipient Group</label>
                <select id="recipient_group" name="recipient_group" class="form-control">
                    <option value="doctor">Doctors</option>
                    <option value="patient">Patients</option>
                    <option value="all">All</option>
                </select>
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Notification</button>
        </form>
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
