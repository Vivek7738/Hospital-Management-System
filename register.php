<?php
// Include database configuration file
require_once 'config.php';

// Load Composer's autoloader
require 'vendor/autoload.php';

// PHPMailer namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOTP($email, $username, $otp, $otp_expiry) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                          
        $mail->Host       = 'smtp.gmail.com';  // For Gmail SMTP server             
        $mail->SMTPAuth   = true;                                  
        $mail->Username   = 'vivekdubey5960@gmail.com';              
        $mail->Password   = 'xaos vazc tmrm xyns';                 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;        
        $mail->Port       = 587;                                   

        // Recipients
        $mail->setFrom('no-reply@example.com', 'YourAppName');
        $mail->addAddress($email);                                 

        // Content
        $mail->isHTML(true);                                  
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "
            <html>
            <head>
                <title>Your OTP for Verification</title>
            </head>
            <body>
                <p>Dear $username,</p>
                <p>Your One-Time Password (OTP) for verification is:</p>
                <h2 style='color: #ff6600;'>$otp</h2>
                <p>This OTP is valid until $otp_expiry. Please enter this code in the verification form to complete your authentication process.</p>
                <p>If you did not request this OTP, please ignore this email.</p>
                <br>
                <p>Best regards,</p>
                <p>Your App Team</p>
            </body>
            </html>
        ";

        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $security_question = $_POST['security_question'];
    $security_answer = $_POST['security_answer'];
    $otp = rand(1000, 9999);
    $otp_expiry = date("Y-m-d H:i:s", strtotime("+150 minutes"));

    // Check if the username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $message = "Username or email already exists.";
        $message_type = 'error';
    } else {
        $stmt->close();

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, otp, otp_expiry, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $username, $email, $password, $role, $otp, $otp_expiry, $security_question, $security_answer);

        if ($stmt->execute()) {
            sendOTP($email, $username, $otp, $otp_expiry);
            $message = "Registration successful! Please check your email for the OTP.";
            $message_type = 'success';
            header("Location: verify.php?email=$email");
            exit;
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = 'error';
        }
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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

        .register-container {
            background: #fff;
            padding: 40px 50px;
            border-radius: 10px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .register-container h2 {
            margin-bottom: 20px;
            color: #333;
            font-weight: 500;
            font-size: 24px;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .register-container label {
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
        }

        .register-container input[type="text"],
        .register-container input[type="email"],
        .register-container input[type="password"],
        .register-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .register-container button {
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

        .register-container button:hover {
            background: #0056b3;
        }

        .register-container a {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .register-container a:hover {
            color: #0056b3;
        }

        .message {
            margin-bottom: 30px;
            text-align: center;
            font-size: 16px;
            padding: 10px;
            border: 1px solid transparent;
            border-radius: 5px;
        }

        .error {
            color: #ff0000;
            background: #ffcccc;
            border-color: #ff0000;
        }

        .success {
            color: #007bff;
            background: #cce5ff;
            border-color: #007bff;
        }

        @media (max-width: 768px) {
            .register-container {
                padding: 30px 40px;
            }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 20px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register</h2>
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="patient">Patient</option>
            </select>
            
            <label for="security_question">Security Question:</label>
            <select id="security_question" name="security_question" required>
                <option value="nickname">What was your childhood nickname?</option>
                <option value="pet">What is the name of your first pet?</option>
                <option value="maiden_name">What is your mother's maiden name?</option>
                <option value="school">What was the name of your elementary school?</option>
                <option value="book">What is your favorite book?</option>
                <option value="movie">What is your favorite movie?</option>
                <option value="birth_city">What city were you born in?</option>
                <option value="car">What was the make of your first car?</option>
                <option value="dream_job">What was your dream job as a child?</option>
                <option value="food">What is your favorite food?</option>
            </select>

            <label for="security_answer">Security Answer:</label>
            <input type="text" id="security_answer" name="security_answer" required>
            
            <button type="submit">Register</button>
        </form>
        <a href="login.php">Login</a>
    </div>
</body>
</html>
