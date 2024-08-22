<?php
session_start();
require_once 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $security_question = $_POST['security_question'];
    $security_answer = $_POST['security_answer'];

    // Check if the user exists and the security question/answer match
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND security_question = ? AND security_answer = ?");
    $stmt->bind_param("sss", $email, $security_question, $security_answer);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Generate a reset token (in a real app, you would store this in the database)
        $_SESSION['reset_email'] = $email;
        header("Location: reset_password.php");
        exit;
    } else {
        $message = "No user found with that email or security question/answer.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(120deg, #3498db, #8e44ad);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        label {
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
            display: block;
            text-align: left;
        }

        input[type="email"],
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

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
            background: #3498db;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #2980b9;
        }

        .back-link {
            margin-top: 15px;
            display: inline-block;
            color: #3498db;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #2980b9;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
        }

        .error {
            color: #fff;
            background: #e74c3c;
            border: 1px solid #c0392b;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if ($message): ?>
            <div class="message error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" action="forgot_password.php">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
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
            
            <button type="submit">Reset Password</button>
        </form>
        <a href="login.php" class="back-link">Back to Login</a>
    </div>
</body>
</html>
