<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch orders for the doctor, including patient names
$ordersQuery = "
    SELECT mo.*, u.username AS patient_name
    FROM medical_orders mo
    JOIN users u ON mo.patient_id = u.id
    WHERE mo.doctor_id = '$userId'
    ORDER BY mo.created_at DESC
";
$ordersResult = $conn->query($ordersQuery);

// Handle deletion of an order
if (isset($_POST['delete_order_id'])) {
    $orderId = $_POST['delete_order_id'];
    $stmt = $conn->prepare("DELETE FROM medical_orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $stmt->close();

    header("Location: medical.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_order_id'])) {
    $patientId = $_POST['patient_id'];
    $medicineName = $_POST['medicine_name'];
    $dose = $_POST['dose'];
    $frequency = $_POST['frequency'];

    $stmt = $conn->prepare("
        INSERT INTO medical_orders (doctor_id, patient_id, medicine_name, dose, frequency)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisss", $userId, $patientId, $medicineName, $dose, $frequency);
    $stmt->execute();
    $stmt->close();

    header("Location: medical.php");
    exit;
}

// Fetch doctor details
$doctorQuery = $conn->query("SELECT username FROM users WHERE id = '$userId'");
$doctor = $doctorQuery->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Orders</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .form-group button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .form-group button:hover {
            background-color: #0056b3;
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
            <h1>Prescription</h1>

            <form method="post" class="mb-4">
                <div class="form-group">
                    <label for="patient_id">Select Patient:</label>
                    <select name="patient_id" id="patient_id" class="form-control">
                        <?php
                        $patients = $conn->query("SELECT id, username FROM users WHERE role='patient'");
                        while ($patient = $patients->fetch_assoc()) {
                            echo "<option value='{$patient['id']}'>{$patient['username']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="medicine_name">Medicine Name:</label>
                    <input type="text" name="medicine_name" id="medicine_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="dose">Dose:</label>
                    <input type="text" name="dose" id="dose" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="frequency">Frequency:</label>
                    <input type="text" name="frequency" id="frequency" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Submit Order</button>
            </form>

            <div class="orders">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th>Medicine Name</th>
                            <th>Dose</th>
                            <th>Frequency</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $ordersResult->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['patient_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['medicine_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['dose']); ?></td>
                                <td><?php echo htmlspecialchars($order['frequency']); ?></td>
                                <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="delete_order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
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
