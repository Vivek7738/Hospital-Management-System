<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'doctor'])) {
    header("Location: ../login.php");
    exit;
}

try {
    // Fetch user counts and roles
    $totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
    $userRolesCount = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");

    $roles = [];
    while ($row = $userRolesCount->fetch_assoc()) {
        $roles[$row['role']] = $row['count'];
    }

    // Defaulting values if not set
    $totalAdmins = $roles['admin'] ?? 0;
    $totalDoctors = $roles['doctor'] ?? 0;
    $totalPatients = $roles['patient'] ?? 0;

    $labels = ['Admin', 'Doctor', 'Patient'];
    $data = [$totalAdmins, $totalDoctors, $totalPatients];

    // Fetch recent users
    $recentUsers = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");

    // Fetch doctor schedules
    $doctorSchedules = $conn->query("SELECT doctor_id, COUNT(*) as schedule_count FROM schedules GROUP BY doctor_id");
    $scheduleLabels = [];
    $scheduleData = [];
    while ($row = $doctorSchedules->fetch_assoc()) {
        $scheduleLabels[] = 'Doctor ' . $row['doctor_id'];
        $scheduleData[] = $row['schedule_count'];
    }

    // Fetch patient records
    $patientRecords = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM users WHERE role='patient' GROUP BY DATE(created_at) ORDER BY created_at");
    $recordLabels = [];
    $recordData = [];
    while ($row = $patientRecords->fetch_assoc()) {
        $recordLabels[] = date('Y-m-d', strtotime($row['date']));
        $recordData[] = $row['count'];
    }

    // Fetch invoice data
    $invoiceData = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total FROM invoices GROUP BY DATE(created_at) ORDER BY created_at");
    $invoiceLabels = [];
    $invoiceCounts = [];
    $invoiceTotals = [];

    while ($row = $invoiceData->fetch_assoc()) {
        $invoiceLabels[] = date('Y-m-d', strtotime($row['date']));
        $invoiceCounts[] = $row['count'];
        $invoiceTotals[] = $row['total'];
    }

    // Fetch medical orders
    $medicalOrders = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM medical_orders GROUP BY DATE(created_at) ORDER BY created_at");
    $orderLabels = [];
    $orderData = [];
    while ($row = $medicalOrders->fetch_assoc()) {
        $orderLabels[] = date('Y-m-d', strtotime($row['date']));
        $orderData[] = $row['count'];
    }

    // Fetch PDF files
    $pdfFiles = $conn->query("SELECT DATE(uploaded_at) as date, COUNT(*) as count FROM pdf_files GROUP BY DATE(uploaded_at) ORDER BY uploaded_at");
    $pdfLabels = [];
    $pdfData = [];
    while ($row = $pdfFiles->fetch_assoc()) {
        $pdfLabels[] = date('Y-m-d', strtotime($row['date']));
        $pdfData[] = $row['count'];
    }

    // Generate PDF report
    if (isset($_POST['download_report'])) {
        $chartImage = $_POST['chartImage'];
        $scheduleChartImage = $_POST['scheduleChartImage'];
        $patientChartImage = $_POST['patientChartImage'];
        $invoiceChartImage = $_POST['invoiceChartImage'];
        $orderChartImage = $_POST['orderChartImage'];
        $pdfChartImage = $_POST['pdfChartImage'];

        generatePDF($totalUsers, $roles, $recentUsers, $chartImage, $scheduleLabels, $scheduleData, $scheduleChartImage, $recordLabels, $recordData, $patientChartImage, $invoiceLabels, $invoiceCounts, $invoiceTotals, $invoiceChartImage, $orderLabels, $orderData, $orderChartImage, $pdfLabels, $pdfData, $pdfChartImage);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Function to generate PDF
function generatePDF($totalUsers, $roles, $recentUsers, $chartImage, $scheduleLabels, $scheduleData, $scheduleChartImage, $recordLabels, $recordData, $patientChartImage, $invoiceLabels, $invoiceCounts, $invoiceTotals, $invoiceChartImage, $orderLabels, $orderData, $orderChartImage, $pdfLabels, $pdfData, $pdfChartImage) {
    require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Swasthya');
    $pdf->SetMargins(10, 10, 10);
    
    // CSS styles for better presentation
    $css = '
    <style>
        h2 {
            color: #2A7D8C;
            border-bottom: 2px solid #2A7D8C;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #B7B7B7;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #F2F2F2;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #F9F9F9;
        }
        img {
            display: block;
            margin: 0 auto 20px auto;
        }
    </style>
    ';
    
    // Cover page with logo
    $pdf->AddPage();
    $pdf->Image('logo.png', 10, 10, 40, 0, 'PNG');
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'User and Schedule Report', 0, 1, 'C');
    $pdf->Ln(5);

    // Total Users
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Total Users: ' . $totalUsers, 0, 1);
    
    // User Roles Distribution
    $html = $css . '<h2>User Roles Distribution</h2>';
    $html .= '<table><tr><th>Role</th><th>Count</th></tr>';
    foreach ($roles as $role => $count) {
        $html .= "<tr><td>" . strtoupper($role) . "</td><td>$count</td></tr>";
    }
    $html .= '</table>';
    $html .= '<h2>User Roles Chart</h2>';
    $html .= '<img src="data:image/png;base64,' . $chartImage . '" width="400" height="300" />';
    $pdf->writeHTML($html, true, false, true, false, '');

    // Recent Users
    $html = $css . '<h2>Recent Users</h2>';
    $html .= '<table><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created At</th></tr>';
    while ($row = $recentUsers->fetch_assoc()) {
        $html .= "<tr>
            <td>{$row['id']}</td>
            <td>{$row['username']}</td>
            <td>{$row['email']}</td>
            <td>{$row['role']}</td>
            <td>{$row['created_at']}</td>
        </tr>";
    }
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');

    // Doctor Schedules
    $pdf->AddPage();
    $html = $css . '<h2>Doctor Schedules</h2>';
    $html .= '<table><tr><th>Doctor ID</th><th>Schedule Count</th></tr>';
    for ($i = 0; $i < count($scheduleLabels); $i++) {
        $html .= "<tr>
            <td>{$scheduleLabels[$i]}</td>
            <td>{$scheduleData[$i]}</td>
        </tr>";
    }
    $html .= '</table>';
    $html .= '<h2>Doctor Schedules Chart</h2>';
    $html .= '<img src="data:image/png;base64,' . $scheduleChartImage . '" width="400" height="300" />';
    $pdf->writeHTML($html, true, false, true, false, '');

    // Patient Records
    $pdf->AddPage();
    $html = $css . '<h2>Patient Records</h2>';
    $html .= '<table><tr><th>Date</th><th>Count</th></tr>';
    for ($i = 0; $i < count($recordLabels); $i++) {
        $html .= "<tr>
            <td>{$recordLabels[$i]}</td>
            <td>{$recordData[$i]}</td>
        </tr>";
    }
    $html .= '</table>';
    $html .= '<h2>Patient Records Chart</h2>';
    $html .= '<img src="data:image/png;base64,' . $patientChartImage . '" width="400" height="300" />';
    $pdf->writeHTML($html, true, false, true, false, '');

    // Invoices
    $pdf->AddPage();
    $html = $css . '<h2>Invoices</h2>';
    $html .= '<table><tr><th>Date</th><th>Invoice Count</th><th>Total Amount</th></tr>';
    for ($i = 0; $i < count($invoiceLabels); $i++) {
        $html .= "<tr>
            <td>{$invoiceLabels[$i]}</td>
            <td>{$invoiceCounts[$i]}</td>
            <td>{$invoiceTotals[$i]}</td>
        </tr>";
    }
    $html .= '</table>';
    $html .= '<h2>Invoice Chart</h2>';
    $html .= '<img src="data:image/png;base64,' . $invoiceChartImage . '" width="400" height="300" />';
    $pdf->writeHTML($html, true, false, true, false, '');

    // Medical Orders
    $pdf->AddPage();
    $html = $css . '<h2>Medical Orders</h2>';
    $html .= '<table><tr><th>Date</th><th>Count</th></tr>';
    for ($i = 0; $i < count($orderLabels); $i++) {
        $html .= "<tr>
            <td>{$orderLabels[$i]}</td>
            <td>{$orderData[$i]}</td>
        </tr>";
    }
    $html .= '</table>';
    $html .= '<h2>Medical Orders Chart</h2>';
    $html .= '<img src="data:image/png;base64,' . $orderChartImage . '" width="400" height="300" />';
    $pdf->writeHTML($html, true, false, true, false, '');

    // PDF Files
    $pdf->AddPage();
    $html = $css . '<h2>Lab Reports</h2>';
    $html .= '<table><tr><th>Date</th><th>Count</th></tr>';
    for ($i = 0; $i < count($pdfLabels); $i++) {
        $html .= "<tr>
            <td>{$pdfLabels[$i]}</td>
            <td>{$pdfData[$i]}</td>
        </tr>";
    }
    $html .= '</table>';
    $html .= '<h2>Lab Report Chart</h2>';
    $html .= '<img src="data:image/png;base64,' . $pdfChartImage . '" width="400" height="300" />';
    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->Output('dashboard_report.pdf', 'D');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        
        body {
    font-family: 'Open Sans', sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f0f0f0;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 1500px;
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
    width: 200px; /* Adjusted width */
    border-radius: 8px;
    margin-right: 20px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
    height: 130em; 
    position: absolute; 
    top: 110px;
    left: 0;
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

h4::first-letter{
            font-family: Lucida Calligraphy;
            font-size:1cm;
            color:red;
        }


.content {
            flex: 1;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 20px;
            margin-left: 270px;
        }

h1 {
    text-align: center;
    color: #333;
}

.stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.stat {
    background: #007bff;
    color: #ffffff;
    padding: 20px;
    text-align: center;
    border-radius: 8px;
    flex: 1;
    margin: 0 10px;
}

.stat:first-child {
    margin-left: 0;
}

.stat:last-child {
    margin-right: 0;
}

.stat i {
    font-size: 30px;
    margin-bottom: 10px;
}

.charts {
    display: flex;
    flex-wrap: wrap;
}

.chart-container {
    width: 37%;
    margin: 2.5%;
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.recent-users {
    margin: 20px 0;
}

.table-container {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

th, td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #dddddd;
}

th {
    background-color: #f4f4f4;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

.download-report {
    text-align: center;
    margin-top: 20px;
}

.download-report button {
    background: #007BFF;
    color: #ffffff;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.download-report button:hover {
    background: #0056b3;
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
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php"><i class="fa fa-home" aria-hidden="true"></i> Home</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="view_reports.php"><i class="fas fa-file-alt"></i> View Reports</a>
                <a href="manage_doctors.php"><i class="fas fa-user-md"></i> Manage Doctors</a>
                <a href="register_patient.php"><i class="fas fa-user-plus"></i> Patient Registration</a>
                <a href="manage_records.php"><i class="fas fa-clipboard"></i> Patient Management</a>
                <a href="manage_invoices.php"><span class="rupee-symbol">₹</span> Manage Invoices</a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'doctor'): ?>
                <a href="../doctor/doctor_dashboard.php"><i class="fa fa-home" aria-hidden="true"></i> Home</a>
                <a href="../admin/manage_records.php"><i class="fas fa-users"></i> Manage Patients</a>
                <a href="../admin/view_reports.php"><i class="fas fa-file-alt"></i> View Reports</a>
                <a href="../admin/manage_schedule.php"><i class="fas fa-calendar-check"></i> Appointment Scheduling</a>
                <a href="../doctor/view_appointments.php"><i class="fas fa-calendar-day"></i> View Appointments</a>
                <a href="../doctor/clinical_documentation.php"><i class="fas fa-notes-medical"></i> Clinical Documentation</a>
                <a href="../doctor/test_lab_results.php"><i class="fas fa-vials"></i> Test and Lab Results</a>
                <a href="../doctor/communication.php"><i class="fas fa-comments"></i> Communication</a>
                <a href="../doctor/medical.php"><i class="fas fa-prescription-bottle-alt"></i> Medical Orders</a>
                <a href="../doctor/billing.php"><i class="fas fa-rupee-sign"></i> Billing</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="content">
        <div class="container">
            <h1>Reports</h1>
            <div class="stats">
                <div class="stat">
                    <i class="fas fa-users"></i>
                    <h2>Total Users</h2>
                    <p><?php echo $totalUsers; ?></p>
                </div>
                <div class="stat">
                    <i class="fas fa-user-shield"></i>
                    <h2>Total Admins</h2>
                    <p><?php echo $totalAdmins; ?></p>
                </div>
                <div class="stat">
                    <i class="fas fa-user-md"></i>
                    <h2>Total Doctors</h2>
                    <p><?php echo $totalDoctors; ?></p>
                </div>
                <div class="stat">
                    <i class="fas fa-user-injured"></i>
                    <h2>Total Patients</h2>
                    <p><?php echo $totalPatients; ?></p>
                </div>
            </div>

            <div class="charts">
                <div class="chart-container">
                    <h2 style="text-align: center">User Roles Distribution</h2>
                    <canvas id="userRolesChart"></canvas>
                </div>
                <div class="chart-container">
                    <h2 style="text-align: center">Doctor Schedules</h2>
                    <canvas id="scheduleChart"></canvas>
                </div>
                <div class="chart-container">
                    <h2 style="text-align: center">Patient Records</h2>
                    <canvas id="patientChart"></canvas>
                </div>
                <div class="chart-container">
                    <h2 style="text-align: center">Invoices</h2>
                    <canvas id="invoiceChart"></canvas>
                </div>
                <div class="chart-container">
                    <h2 style="text-align: center">Medical Orders</h2>
                    <canvas id="orderChart"></canvas>
                </div>
                <div class="chart-container">
                    <h2 style="text-align: center">Lab Reports</h2>
                    <canvas id="pdfChart"></canvas>
                </div>
            </div>

            <div class="recent-users">
                <h2 style="text-align: center">Recent Users</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $recentUsers->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['username']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['role']; ?></td>
                                    <td><?php echo $row['created_at']; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="download-report">
                <form method="post">
                    <input type="hidden" name="chartImage" id="chartImage">
                    <input type="hidden" name="scheduleChartImage" id="scheduleChartImage">
                    <input type="hidden" name="patientChartImage" id="patientChartImage">
                    <input type="hidden" name="invoiceChartImage" id="invoiceChartImage">
                    <input type="hidden" name="orderChartImage" id="orderChartImage">
                    <input type="hidden" name="pdfChartImage" id="pdfChartImage">
                    <button type="submit" name="download_report">Download PDF Report</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // User Roles Chart
        var ctx = document.getElementById('userRolesChart').getContext('2d');
        var userRolesChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                }]
            }
        });

        // Doctor Schedules Chart
        var ctx2 = document.getElementById('scheduleChart').getContext('2d');
        var scheduleChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($scheduleLabels); ?>,
                datasets: [{
                    label: 'Schedules',
                    data: <?php echo json_encode($scheduleData); ?>,
                    backgroundColor: '#36A2EB'
                }]
            }
        });

        // Patient Records Chart
        var ctx3 = document.getElementById('patientChart').getContext('2d');
        var patientChart = new Chart(ctx3, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($recordLabels); ?>,
                datasets: [{
                    label: 'Records',
                    data: <?php echo json_encode($recordData); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            }
        });

        // Invoices Chart
        var ctx4 = document.getElementById('invoiceChart').getContext('2d');
        var invoiceChart = new Chart(ctx4, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($invoiceLabels); ?>,
                datasets: [{
                    label: 'Invoice Count',
                    data: <?php echo json_encode($invoiceCounts); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Total Amount',
                    data: <?php echo json_encode($invoiceTotals); ?>,
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            }
        });

        // Medical Orders Chart
        var ctx5 = document.getElementById('orderChart').getContext('2d');
        var orderChart = new Chart(ctx5, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($orderLabels); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode($orderData); ?>,
                    backgroundColor: '#FFCE56'
                }]
            }
        });

        // PDF Files Chart
        var ctx6 = document.getElementById('pdfChart').getContext('2d');
        var pdfChart = new Chart(ctx6, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($pdfLabels); ?>,
                datasets: [{
                    label: 'Lab Results',
                    data: <?php echo json_encode($pdfData); ?>,
                    backgroundColor: '#FF6384'
                }]
            }
        });

        // Generate Base64 for charts and set in hidden fields
        document.querySelector('form').addEventListener('submit', function (e) {
            document.getElementById('chartImage').value = userRolesChart.toBase64Image().replace(/^data:image\/(png|jpg);base64,/, "");
            document.getElementById('scheduleChartImage').value = scheduleChart.toBase64Image().replace(/^data:image\/(png|jpg);base64,/, "");
            document.getElementById('patientChartImage').value = patientChart.toBase64Image().replace(/^data:image\/(png|jpg);base64,/, "");
            document.getElementById('invoiceChartImage').value = invoiceChart.toBase64Image().replace(/^data:image\/(png|jpg);base64,/, "");
            document.getElementById('orderChartImage').value = orderChart.toBase64Image().replace(/^data:image\/(png|jpg);base64,/, "");
            document.getElementById('pdfChartImage').value = pdfChart.toBase64Image().replace(/^data:image\/(png|jpg);base64,/, "");
        });

        function disableCtrlU(event) {
            if (event.ctrlKey && (event.key === 'u' || event.key === 'U')) {
                return false;
            }
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
