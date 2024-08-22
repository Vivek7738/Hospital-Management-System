<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor')) {
    header("Location: ../login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if the username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $message = "Username already exists. Please choose a different username.";
    } else {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'patient')");
        $stmt->bind_param("sss", $username, $email, $password);

        if ($stmt->execute()) {
            $message = "Patient registered successfully!";
        } else {
            $message = "Error: " . $stmt->error;
        }
    }

    $stmt->close();
}

$name = $conn->query("SELECT username FROM users WHERE role='admin'")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient</title>
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
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
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
        .back-button a {
            color: white;
            text-decoration: none;
        }
        .rupee-symbol {
            font-family: 'FontAwesome', 'Arial', sans-serif;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .dashboard {
                padding: 20px;
            }
            .sidebar {
                width: 200px;
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
            <span>Welcome, <?php echo htmlspecialchars($name); ?> <i class="fas fa-user-circle"></i></span>
            <div class="logout-dropdown-content">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="d-flex">
        <div class="sidebar">
            <h2>Patient Registration</h2>
            <div class="nav-links">
                <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="view_reports.php"><i class="fas fa-file-alt"></i> View Reports</a>
                <a href="manage_doctors.php"><i class="fas fa-user-md"></i> Manage Doctors</a>
                <a href="manage_schedule.php"><i class="fas fa-calendar-alt"></i> Doctor's Schedules</a>
                <a href="manage_records.php"><i class="fas fa-clipboard"></i> Patient Management</a>
                <a href="manage_invoices.php"><span class="rupee-symbol">₹</span> Manage Invoices</a>
            </div>
        </div>

        <div class="dashboard">
            <h1>Register New Patient</h1>
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="register_patient.php">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Register</button>
            </form>
            <div class="back-button-container">
                <button class="back-button"><a href="manage_records.php">Back to Manage Records</a></button>
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
