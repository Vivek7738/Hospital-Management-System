<?php
// Include database configuration file
require_once 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $otp = $_POST['otp'];

    // Prepare the first statement to check the OTP
    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($db_otp, $otp_expiry);
    $stmt->fetch();
    $stmt->close(); // Close the first statement

    // Check if the OTP is valid and not expired
    if ($db_otp == $otp && strtotime($otp_expiry) > time()) {
        // Prepare the second statement to update the user record
        $stmt = $conn->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close(); // Close the second statement

        // Set success message
        $message = 'verification_successful';
    } else {
        $message = 'invalid_or_expired_otp';
    }
}

if (isset($_GET['email'])) {
    $email = htmlspecialchars($_GET['email']);
    $message = "An OTP has been sent to $email.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background: linear-gradient(120deg, #3498db, #8e44ad);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .verify-container {
            background: #fff;
            padding: 40px 50px;
            border-radius: 10px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .verify-container h2 {
            margin-bottom: 20px;
            color: #333;
            font-weight: 500;
            font-size: 24px;
            text-align: center;
        }

        .verify-container label {
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
        }

        .verify-container input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .verify-container button {
            width: 100%;
            padding: 10px;
            background: #3498db;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .verify-container button:hover {
            background: #2980b9;
        }
        .message.info {
            color: blue;
        }

        .message.error {
            color: red;
        }

        .message.success {
            color: green;
        }

        @media (max-width: 768px) {
            .verify-container {
                padding: 30px 40px;
            }
        }

        @media (max-width: 480px) {
            .verify-container {
                padding: 20px 30px;
            }
        }
    </style>
    <script>
        function showAlertAndRedirect() {
            alert("Verification successful!");
            window.location.href = 'login.php';
        }
    </script>
</head>
<body>
    <div class="verify-container">
        <h2>Verify OTP</h2>
        <form method="POST" action="verify.php">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
            <label for="otp">OTP:</label>
            <input type="text" id="otp" name="otp" required>
            <button type="submit">Verify</button>
        </form>
        <?php
        if ($message == 'verification_successful') {
            echo "<script>showAlertAndRedirect();</script>";
        } elseif ($message == 'invalid_or_expired_otp') {
            echo "<div class='message error'>Invalid or expired OTP.</div>";
        } 
        ?>
    </div>
</body>
</html>