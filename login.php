<?php
// Include database configuration file
require_once 'config.php';

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['role'] = $role;

            switch ($role) {
                case 'admin':
                    header("Location: admin/admin_dashboard.php");
                    break;
                case 'doctor':
                    header("Location: doctor/doctor_dashboard.php");
                    break;
                case 'patient':
                    header("Location: patient/patient_dashboard.php");
                    break;
                default:
                    $error = "Invalid role.";
            }
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No user found with that email.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            margin: 0;
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(120deg, #007bff, #3498db);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: #fff;
            padding: 40px 50px;
            border-radius: 10px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-container h2 {
            margin-bottom: 20px;
            color: #333;
            font-weight: 500;
            font-size: 24px;
            text-align: center;
        }

        .login-container label {
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
        }

        .login-container input[type="email"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .login-container button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .login-container button:hover {
            background: #0056b3;
        }

        .login-container a {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .login-container a:hover {
            color: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 30px;
            text-align: center;
            font-size: 16px;
        }

        .message {
            display: none;
            color: #ff6f61;
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 30px 40px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 20px 30px;
            }
        }
    </style>
    <script>
        function displayRedirectMessage() {
            var isMobile = /Mobi|Android/i.test(navigator.userAgent);
            if (isMobile) {
                document.getElementById('redirect-message').style.display = 'block';
            }
        }

        window.onload = function() {
            displayRedirectMessage();
        }
    </script>
</head>
<body>
<div class="login-container">
    <h2>Login</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" autocomplete="on" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <a href="register.php">Register</a>
    <a href="forgot_password.php">Forgot Password?</a>
    <div id="redirect-message" class="message">
        If you are using a mobile phone, please open this site in desktop mode.
    </div>
</div>
</body>
</html>
