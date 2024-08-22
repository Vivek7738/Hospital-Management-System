<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch patients
$patients = $conn->query("SELECT id, username FROM users WHERE role='patient'");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $recipientId = $_POST['recipient_id'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $userId, $recipientId, $message);
    $stmt->execute();
    $stmt->close();
}

// Get selected patient messages
$patientId = $_GET['patient_id'] ?? null;
$selectedPatient = null;

if ($patientId) {
    $patientResult = $conn->query("SELECT username FROM users WHERE id = '$patientId'");
    $selectedPatient = $patientResult->fetch_assoc();
}

$messagesQuery = "SELECT messages.*, users.username AS sender_name 
                  FROM messages 
                  JOIN users ON messages.sender_id = users.id 
                  WHERE (recipient_id = '$userId' AND sender_id = '$patientId') OR (sender_id = '$userId' AND recipient_id = '$patientId')
                  ORDER BY created_at DESC";
$messages = $conn->query($messagesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body {
            margin: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .navbar {
            background-color: #007bff;
            border-bottom: 1px solid #ddd;
        }
        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
        }
        .navbar-nav .nav-link {
            color: #fff;
        }
        .navbar-nav .nav-link:hover {
            color: #e0e0e0;
        }
        .sidebar {
            width: 250px;
            background-color: #fff;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .sidebar .search-bar {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .sidebar .search-bar input {
            width: 100%;
            padding: 8px;
            border-radius: 20px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }
        .sidebar .patient-list {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0;
        }
        .sidebar .patient-list-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
            display: flex;
            align-items: center;
            border-radius: 50px;
            transition: background-color 0.2s;
        }
        .sidebar .patient-list-item:hover {
            background-color: #f0f0f0;
        }
        .sidebar .patient-list-item img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .chat {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #fff;
        }
        .chat-header {
            background-color: #fff;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            padding: 10px;
        }
        .chat-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .chat-header h2 {
            margin: 0;
            font-size: 16px;
        }
        .chat-messages {
            flex: 1;
            padding: 10px;
            overflow-y: auto;
            background-color: #e5e5ea;
            display: flex;
            flex-direction: column-reverse;
        }
        .message {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-end;
        }
        .message.sent {
            justify-content: flex-end;
        }
        .message-content {
            max-width: 60%;
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 14px;
            line-height: 1.5;
            position: relative;
        }
        .message.sent .message-content {
            background-color: #007bff;
            color: #fff;
            text-align: right;
        }
        .message.received .message-content {
            background-color: #e5e5ea;
            color: #000;
            text-align: left;
        }
        .message-time {
            font-size: 12px;
            color: #666;
            display: block;
            margin-top: 5px;
        }
        .message.sent .message-time {
            color: #cce5ff;
        }
        .chat-input {
            padding: 10px;
            border-top: 1px solid #ddd;
            background-color: #fff;
            display: flex;
            align-items: center;
        }
        .chat-input textarea {
            flex: 1;
            border: none;
            padding: 10px;
            border-radius: 20px;
            resize: none;
            font-size: 14px;
            background-color: #fafafa;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        .chat-input button {
            background-color: #007bff;
            border: none;
            padding: 10px 20px;
            margin-left: 10px;
            color: #fff;
            border-radius: 20px;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
            }
            .chat {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand" href="#">ChatApp</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="doctor_dashboard.php">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="doctor_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <div class="d-flex">
        <div class="sidebar">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search..." onkeyup="searchPatient()">
            </div>
            <div class="patient-list" id="patientList">
                <?php while ($patient = $patients->fetch_assoc()) { ?>
                    <div class="patient-list-item" onclick="window.location.href='?patient_id=<?php echo $patient['id']; ?>'">
                        <img src="../a.jpg" alt="Profile">
                        <?php echo htmlspecialchars($patient['username']); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="chat">
            <div class="chat-header">
                <a href="doctor_dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <?php if ($selectedPatient): ?>
                    <img src="../a.jpg" alt="Profile">
                    <h2><?php echo htmlspecialchars($selectedPatient['username']); ?></h2>
                <?php else: ?>
                    <h2>Select a patient</h2>
                <?php endif; ?>
            </div>
            <div class="chat-messages">
                <?php while ($message = $messages->fetch_assoc()) { ?>
                    <div class="message <?php echo $message['sender_id'] == $userId ? 'sent' : 'received'; ?>">
                        <div class="message-content">
                            <strong><?php echo htmlspecialchars($message['sender_name']); ?>:</strong>
                            <?php echo htmlspecialchars($message['message']); ?>
                            <div class="message-time"><?php echo htmlspecialchars(date('d M Y H:i', strtotime($message['created_at']))); ?></div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <?php if ($patientId): ?>
            <form method="post" class="chat-input" id="chatForm">
                <textarea name="message" id="message" rows="1" required></textarea>
                <input type="hidden" name="recipient_id" value="<?php echo htmlspecialchars($patientId); ?>">
                <button type="submit">Send</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function searchPatient() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const patientList = document.getElementById('patientList');
            const patients = patientList.getElementsByClassName('patient-list-item');

            for (let i = 0; i < patients.length; i++) {
                const txtValue = patients[i].textContent || patients[i].innerText;
                patients[i].style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
            }
        }

        document.getElementById('message').addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                document.getElementById('chatForm').submit();
            }
        });
    </script>
</body>
</html>
