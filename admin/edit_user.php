<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header("Location: manage_users.php");
    exit;
}

// Fetch user details
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username, $email, $role);
$stmt->fetch();
$stmt->close();

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // Update user details
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $role, $userId);

    if ($stmt->execute()) {
        $message = "User updated successfully!";
        $messageType = "success";
    } else {
        $message = "Error: " . $stmt->error;
        $messageType = "error";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #eef2f3;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .dashboard {
            width: 100%;
            max-width: 600px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .dashboard:hover {
            transform: scale(1.02);
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

        label {
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
            display: block;
            text-align: left;
        }

        input[type="text"],
        input[type="email"],
        select {
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

        .back-button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s;
        }

        .back-button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .back-button a {
            color: white;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .dashboard {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .dashboard {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>Edit User</h1>
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="edit_user.php?id=<?php echo $userId; ?>">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="doctor" <?php echo ($role == 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                <option value="patient" <?php echo ($role == 'patient') ? 'selected' : ''; ?>>Patient</option>
            </select>
            <button type="submit">Update User</button>
        </form>
        <div class="back-button-container">
            <button class="back-button"><a href="manage_users.php">Back to Manage Users</a></button>
        </div>
    </div>
</body>
</html>
