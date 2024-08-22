<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

// Fetch doctor's information
$doctorId = $_SESSION['user_id'];
$doctorQuery = $conn->prepare("SELECT username FROM users WHERE id = ?");
$doctorQuery->bind_param("i", $doctorId);
$doctorQuery->execute();
$doctor = $doctorQuery->get_result()->fetch_assoc();
$doctorQuery->close();

// Handle form submission for new invoice
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $patientId = $_POST['patient_id'];
    $doctorId = $_SESSION['user_id'];
    $date = $_POST['date'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    $insertQuery = $conn->prepare("INSERT INTO invoices (patient_id, doctor_id, amount, date, description, status) VALUES (?, ?, ?, ?, ?, ?)");
    $insertQuery->bind_param("iissss", $patientId, $doctorId, $amount, $date, $description, $status);
    $insertQuery->execute();
    $insertQuery->close();

    header("Location: billing.php");
    exit;
}

// Handle edit and delete requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $invoiceId = $_POST['invoice_id'];
    $patientId = $_POST['patient_id'];
    $date = $_POST['date'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    $updateQuery = $conn->prepare("UPDATE invoices SET patient_id = ?, amount = ?, date = ?, description = ?, status = ? WHERE id = ?");
    $updateQuery->bind_param("iisssi", $patientId, $amount, $date, $description, $status, $invoiceId);
    $updateQuery->execute();
    $updateQuery->close();

    header("Location: billing.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $invoiceId = $_POST['invoice_id'];

    $deleteQuery = $conn->prepare("DELETE FROM invoices WHERE id = ?");
    $deleteQuery->bind_param("i", $invoiceId);
    $deleteQuery->execute();
    $deleteQuery->close();

    header("Location: billing.php");
    exit;
}

// Fetch doctor's invoices
$invoiceQuery = $conn->prepare("SELECT id, patient_id, amount, date, description, status FROM invoices WHERE doctor_id = ?");
$invoiceQuery->bind_param("i", $doctorId);
$invoiceQuery->execute();
$invoices = $invoiceQuery->get_result();
$invoiceQuery->close();

// Fetch list of patients for the form
$patientsQuery = $conn->prepare("SELECT id, username FROM users WHERE role = 'patient'");
$patientsQuery->execute();
$patients = $patientsQuery->get_result();
$patientsQuery->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing</title>
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

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        .form-group {
            margin-bottom: 15px;
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
        #editForm {
    display: none;
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
            <div class="top-bar">
                <h1>Billing</h1>
            </div>

            <?php if ($invoices->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($invoice = $invoices->fetch_assoc()): ?>
                            <tr>
                                <?php
                                    $patientId = $invoice['patient_id'];
                                    $patientQuery = $conn->prepare("SELECT username FROM users WHERE id = ?");
                                    $patientQuery->bind_param("i", $patientId);
                                    $patientQuery->execute();
                                    $patient = $patientQuery->get_result()->fetch_assoc();
                                    $patientName = $patient['username'];
                                    $patientQuery->close();
                                ?>
                                <td><?php echo htmlspecialchars($patientName); ?></td>
                                <td><?php echo htmlspecialchars($invoice['date']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['amount']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['description']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['status']); ?></td>
                                <td>
                                    <!-- Edit Button Trigger -->
                                    <button class="btn btn-secondary btn-sm" onclick="showEditForm(<?php echo htmlspecialchars($invoice['id']); ?>, <?php echo htmlspecialchars($invoice['patient_id']); ?>, '<?php echo htmlspecialchars($invoice['date']); ?>', '<?php echo htmlspecialchars($invoice['amount']); ?>', '<?php echo htmlspecialchars($invoice['description']); ?>', '<?php echo htmlspecialchars($invoice['status']); ?>')">Edit</button>

                                    <!-- Delete Button Trigger -->
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($invoice['id']); ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No invoices found.</p>
            <?php endif; ?>

            <br>
            <h2>Add New Invoice</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="patient_id">Patient:</label>
                    <select id="patient_id" name="patient_id" class="form-control" required>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($patient['id']); ?>"><?php echo htmlspecialchars($patient['username']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="amount">Amount:</label>
                    <input type="number" id="amount" name="amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Add Invoice</button>
            </form>

            <!-- Edit Form -->
            <div id="editForm" style="display:none;">
                <br>
                <h2>Edit Invoice</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" id="edit_invoice_id" name="invoice_id">
                    <div class="form-group">
                        <label for="edit_patient_id">Patient:</label>
                        <select id="edit_patient_id" name="patient_id" class="form-control" required>
                            <?php
                            // Rewind the patients result set
                            $patients->data_seek(0);
                            while ($patient = $patients->fetch_assoc()):
                            ?>
                                <option value="<?php echo htmlspecialchars($patient['id']); ?>"><?php echo htmlspecialchars($patient['username']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_date">Date:</label>
                        <input type="date" id="edit_date" name="date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_amount">Amount:</label>
                        <input type="number" id="edit_amount" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description:</label>
                        <textarea id="edit_description" name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status:</label>
                        <select id="edit_status" name="status" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Invoice</button>
                    <button type="button" class="btn btn-secondary" onclick="hideEditForm()">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    function showEditForm(id, patientId, date, amount, description, status) {
        document.getElementById('edit_invoice_id').value = id;
        document.getElementById('edit_patient_id').value = patientId;
        document.getElementById('edit_date').value = date;
        document.getElementById('edit_amount').value = amount;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_status').value = status;
        document.getElementById('editForm').style.display = 'block';
    }

    function hideEditForm() {
        document.getElementById('editForm').style.display = 'none';
    }
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
