<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch patient details using prepared statements
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch doctors
$doctors = $conn->query("SELECT id, username FROM users WHERE role='doctor'");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $recipientId = $_POST['recipient_id'];
    $message = $_POST['message'];

    // Insert message using prepared statement
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $userId, $recipientId, $message);
    $stmt->execute();
    $stmt->close();

    // Redirect after message sent (prevent form resubmission)
    header("Location: comm.php?doctor_id=$recipientId");
    exit;
}

// Get selected doctor messages
$doctorId = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
$selectedDoctor = null;

if ($doctorId) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $doctorResult = $stmt->get_result();
    $selectedDoctor = $doctorResult->fetch_assoc();
    $stmt->close();
}

$messagesQuery = "SELECT messages.*, users.username AS sender_name 
                  FROM messages 
                  JOIN users ON messages.sender_id = users.id 
                  WHERE (recipient_id = ? AND sender_id = ?) OR (sender_id = ? AND recipient_id = ?)
                  ORDER BY created_at DESC";
$stmt = $conn->prepare($messagesQuery);
$stmt->bind_param("iiii", $userId, $doctorId, $userId, $doctorId);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Communication</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
        .sidebar .doctor-list {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0;
        }
        .sidebar .doctor-list-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
            display: flex;
            align-items: center;
            border-radius: 50px;
            transition: background-color 0.2s;
        }
        .sidebar .doctor-list-item:hover {
            background-color: #f0f0f0;
        }
        .sidebar .doctor-list-item img {
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
        }
        .message {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-end;
            flex-direction: column; /* Ensure messages stack vertically */
        }
        .message-content {
            max-width: 60%;
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .message.sent .message-content {
            background-color: #007bff;
            color: #fff;
            align-self: flex-end; /* Align sent messages to the right */
        }
        .message.received .message-content {
            background-color: #e5e5ea;
            color: #000;
            align-self: flex-start; /* Align received messages to the left */
        }
        .message-time {
            font-size: 12px;
            color: #666;
            text-align: right;
        }
        .message.sent .message-time {
            color: #cce5ff; /* Light blue color for better visibility on blue background */
        }
        /* Ensure newest message is at the bottom */
        #messageContainer {
            display: flex;
            flex-direction: column-reverse; /* Reverse order of messages */
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
                    <a class="nav-link" href="patient_dashboard.php">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-home"></i> Home
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="patient_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <div class="d-flex">
        <div class="sidebar">
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search..." onkeyup="searchDoctor()">
            </div>
            <div class="doctor-list" id="doctorList">
                <?php while ($doctor = $doctors->fetch_assoc()) { ?>
                    <div class="doctor-list-item" onclick="window.location.href='comm.php?doctor_id=<?php echo $doctor['id']; ?>'">
                        <?php echo htmlspecialchars($doctor['username']); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="chat">
            <div class="chat-header">
                <img src="<?php echo isset($patient['profile_picture']) ? htmlspecialchars($patient['profile_picture']) : '../a1.jpg'; ?>" alt="Profile Picture" class="profile-picture">
                <h2><?php echo $selectedDoctor ? htmlspecialchars($selectedDoctor['username']) : 'Select a doctor to start chatting'; ?></h2>
            </div>
            <div class="chat-messages">
                <div id="messageContainer">
                    <?php while ($message = $messages->fetch_assoc()) { ?>
                        <div class="message <?php echo $message['sender_id'] == $userId ? 'sent' : 'received'; ?>">
                            <div class="message-content">
                                <?php echo htmlspecialchars($message['message']); ?>
                                <div class="message-sender">
                                    <small><?php echo $message['sender_id'] == $userId ? 'You' : htmlspecialchars($message['sender_name']); ?></small>
                                </div>
                                <div class="message-time">
                                    <small><?php echo htmlspecialchars(date('d M Y H:i', strtotime($message['created_at']))); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php if ($selectedDoctor) { ?>
                <form method="post" action="" id="chatForm">
                    <input type="hidden" name="recipient_id" value="<?php echo htmlspecialchars($doctorId); ?>">
                    <div class="chat-input">
                        <textarea name="message" id="message" placeholder="Type your message..." required></textarea>
                        <button type="submit">Send</button>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function searchDoctor() {
            // Declare variables
            var input, filter, ul, li, a, i, txtValue;
            input = document.getElementById('searchInput');
            filter = input.value.toUpperCase();
            ul = document.getElementById("doctorList");
            li = ul.getElementsByTagName('div');

            // Loop through all list items, and hide those who don't match the search query
            for (i = 0; i < li.length; i++) {
                a = li[i];
                txtValue = a.textContent || a.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    li[i].style.display = "";
                } else {
                    li[i].style.display = "none";
                }
            }
        }

        // Handle Enter key for sending messages
        document.getElementById('message').addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                document.getElementById('chatForm').submit();
            }
        });
    </script>
</body>
</html>