<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit;
}

// Handle form submission for new appointments or edits
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patientId = $_SESSION['user_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $doctorId = $_POST['doctor_id'];
    $note = $_POST['note'];
    $action = $_POST['action'];

    // Fetch doctor name using doctor ID
    $doctorQuery = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $doctorQuery->bind_param("i", $doctorId);
    $doctorQuery->execute();
    $doctorResult = $doctorQuery->get_result();
    $doctor = $doctorResult->fetch_assoc();
    $doctorName = $doctor['username'];
    $doctorQuery->close();

    // Check if the selected date is a weekend (Saturday or Sunday)
    $dayOfWeek = date('N', strtotime($date));
    if ($dayOfWeek == 6 || $dayOfWeek == 7) {
        $_SESSION['error'] = "Appointments cannot be booked on weekends. Please choose a weekday.";
        header("Location: appointments.php");
        exit;
    }

    if ($action === 'add') {
        // Insert appointment
        $insertQuery = $conn->prepare("INSERT INTO appointments (patient_id, date, time, doctor_name, note) VALUES (?, ?, ?, ?, ?)");
        $insertQuery->bind_param("issss", $patientId, $date, $time, $doctorName, $note);
        $insertQuery->execute();
        $insertQuery->close();
    } elseif ($action === 'edit') {
        // Update appointment
        $appointmentId = $_POST['appointment_id'];
        $updateQuery = $conn->prepare("UPDATE appointments SET date = ?, time = ?, doctor_name = ?, note = ? WHERE id = ? AND patient_id = ?");
        $updateQuery->bind_param("ssssii", $date, $time, $doctorName, $note, $appointmentId, $patientId);
        $updateQuery->execute();
        $updateQuery->close();
    }

    header("Location: appointments.php");
    exit;
}

// Fetch list of doctors
// Fetch the list of doctors from the database
$doctorQuery = "SELECT id, username FROM users WHERE role='doctor'";
$doctors = $conn->query($doctorQuery);

if (!$doctors) {
    die("Error fetching doctors: " . $conn->error);
}

$doctorList = $doctors->fetch_all(MYSQLI_ASSOC);



// Fetch appointments for the logged-in patient
$appointmentsQuery = $conn->prepare("SELECT * FROM appointments WHERE patient_id = ?");
$appointmentsQuery->bind_param("i", $_SESSION['user_id']);
$appointmentsQuery->execute();
$appointments = $appointmentsQuery->get_result();
$appointmentsQuery->close();

$patientId = $_SESSION['user_id'];
$patientQuery = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$patientQuery->bind_param("i", $patientId);
$patientQuery->execute();
$patient = $patientQuery->get_result()->fetch_assoc();
$patientQuery->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Book Appointment</title>
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
            width: 260px;
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
        .action-buttons a {
            margin-right: 10px;
        }
    </style>
</head>
<body oncontextmenu="return false;" onkeydown="return disableCtrlU(event);">
    <div class="top-bar">
        <div class="clock-container">
            <div class="clock" id="clock"></div>
        </div>
        <div class="logout-dropdown">
            <span>Welcome, <?php echo htmlspecialchars($patient['username']); ?> <i class="fas fa-user-circle"></i></span>
            <div class="logout-dropdown-content">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="d-flex">
        <div class="sidebar">
            <h2>Appointment</h2>
            <div class="nav-links">
            <a href="patient_dashboard.php"><i class="fa fa-home" aria-hidden="true"></i> Home</a>
                <a href="lab_file.php"><i class="fas fa-file-medical-alt"></i> Lab Reports</a>
                <a href="comm.php"><i class="fas fa-comments"></i> Communication</a>
                <a href="medical.php"><i class="fas fa-prescription-bottle-alt"></i> Prescription</a>
                <a href="billing.php"><i class="fas fa-rupee-sign"></i> Billing</a>
                <a href="feedback.php"><i class="fas fa-comment-alt"></i> Feedback</a>
            </div>
        </div>

        <div class="dashboard">
        <h1>Your Appointments</h1>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Doctor</th>
                        <th>Note</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($appointment = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $appointment['date']; ?></td>
                            <td><?php echo $appointment['time']; ?></td>
                            <td><?php echo $appointment['doctor_name']; ?></td>
                            <td><?php echo $appointment['note']; ?></td>
                            <td class="action-buttons">
                                <a href="#" class="btn btn-secondary edit-btn" data-toggle="modal" data-target="#editModal" data-appointment='<?php echo json_encode($appointment); ?>'><i class="far fa-edit"></i> Edit</a>
                                <a href="delete_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <hr>
            <br>
            <h1>Book a New Appointment</h1>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="time">Time:</label>
                    <input type="time" id="time" name="time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="doctor_id">Doctor:</label>
                    <!-- In the form for booking a new appointment -->
<select id="doctor_id" name="doctor_id" class="form-control" required>
    <?php foreach ($doctorList as $doctor): ?>
        <option value="<?php echo $doctor['id']; ?>"><?php echo $doctor['username']; ?></option>
    <?php endforeach; ?>
</select>

                </div>
                <div class="form-group">
                    <label for="note">Note:</label>
                    <textarea id="note" name="note" class="form-control" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" onclick="start()">Book Appointment</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Appointment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="appointment_id" name="appointment_id">
                    <div class="form-group">
                        <label for="edit_date">Date:</label>
                        <input type="date" id="edit_date" name="date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_time">Time:</label>
                        <input type="time" id="edit_time" name="time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_doctor_id">Doctor:</label>
                        <select id="edit_doctor_id" name="doctor_id" class="form-control" required>
                            <!-- Doctor options will be populated here by JavaScript -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_note">Note:</label>
                        <textarea id="edit_note" name="note" class="form-control" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>



    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var clockElement = document.getElementById('clock');
    function updateClock() {
        var now = new Date();
        clockElement.textContent = now.toLocaleTimeString();
    }
    setInterval(updateClock, 1000);
    // Fetch the list of doctors from PHP and store it in a variable
    var doctors = <?php echo json_encode($doctorList); ?>;

    // Populate the edit modal with the current appointment details
    var editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var appointment = JSON.parse(this.getAttribute('data-appointment'));
            document.getElementById('appointment_id').value = appointment.id;
            document.getElementById('edit_date').value = appointment.date;
            document.getElementById('edit_time').value = appointment.time;
            document.getElementById('edit_note').value = appointment.note;

            var editDoctorSelect = document.getElementById('edit_doctor_id');
            editDoctorSelect.innerHTML = ''; // Clear previous options
            doctors.forEach(function(doctor) {
                var option = document.createElement('option');
                option.value = doctor.id;
                option.textContent = doctor.username;
                if (doctor.id == appointment.doctor_id) {
                    option.selected = true;
                }
                editDoctorSelect.appendChild(option);
            });
        });
    });
});
    // Fetch the list of doctors from PHP and store it in a variable
    var doctors = <?php echo json_encode($doctorList); ?>;
    console.log(doctors); // Debug: check if doctors list is printed correctly

    function disableCtrlU(event) {
            if (event.ctrlKey && (event.key === 'u' || event.key === 'U')) {
                return false;
            }
        }
    function start(){
	    Push.create("Appointment Scheduled!!", {
		    body: "Thankyou for booking an appointment.",
		    icon: '../admin/logo.png',
		    timeout: 4000,
		    onClick: function () {
			    window.focus();
			    this.close();
		}
	});
}
    </script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="../push/push.min.js"></script>
    <script src="../push/serviceWorker.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
