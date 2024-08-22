<?php
session_start(); // Start the session to access session variables
require_once '../config.php'; // Include database configuration

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); // Redirect to login if not an admin
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id']; // Get patient ID from form
    $doctor_id = $_POST['doctor_id']; // Get doctor ID from form
    $amount = $_POST['amount']; // Get amount from form
    $description = $_POST['description']; // Get description from form
    $date = date('Y-m-d'); // Get current date

    // Prepare SQL statement to insert invoice data
    $stmt = $conn->prepare("INSERT INTO invoices (patient_id, doctor_id, amount, date, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $patient_id, $doctor_id, $amount, $date, $description);

    // Execute statement and check for success
    if ($stmt->execute()) {
        $_SESSION['message'] = "Invoice added successfully!"; // Success message
        header("Location: manage_invoices.php"); // Redirect to manage invoices page
        exit;
    } else {
        echo "Error: " . $stmt->error; // Display error message
    }
}

// Fetch patients and doctors for dropdown lists
$patients = $conn->query("SELECT id, username FROM users WHERE role='patient'");
$doctors = $conn->query("SELECT id, username FROM users WHERE role='doctor'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Invoice</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa; /* Light background */
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff; /* White background */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 24px;
            color: #343a40; /* Dark text color */
            margin-bottom: 20px;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 14px;
            color: #495057; /* Medium text color */
        }

        select, input[type="text"], textarea {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 8px;
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        button {
            padding: 10px 20px;
            background-color: #007bff; /* Blue button */
            border: none;
            border-radius: 4px;
            color: #ffffff; /* White text */
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        .message {
            background: #d4edda; /* Light green background for success message */
            color: #155724; /* Dark green text */
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }

        .container a{
            padding: 10px 20px;
            background-color: #007bff; /* Blue button */
            border: none;
            border-radius: 4px;
            color: #ffffff; /* White text */
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            h1 {
                font-size: 20px;
            }

            button {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 10px;
            }

            h1 {
                font-size: 18px;
            }

            button {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Add Invoice</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="message"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <form method="post" action="add_invoice.php">
        <label for="patient_id">Patient:</label>
        <select name="patient_id" id="patient_id" required>
            <option value="">Select Patient</option>
            <?php while ($patient = $patients->fetch_assoc()) { ?>
                <option value="<?php echo $patient['id']; ?>"><?php echo htmlspecialchars($patient['username']); ?></option>
            <?php } ?>
        </select>

        <label for="doctor_id">Doctor:</label>
        <select name="doctor_id" id="doctor_id" required>
            <option value="">Select Doctor</option>
            <?php while ($doctor = $doctors->fetch_assoc()) { ?>
                <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['username']); ?></option>
            <?php } ?>
        </select>

        <label for="amount">Amount:</label>
        <input type="text" name="amount" id="amount" required>

        <label for="description">Description:</label>
        <textarea name="description" id="description"></textarea>

        <button type="submit">Add Invoice</button>
            <a href="admin_dashboard.php" class="btn btn-back">Back to Dashboard</a>
        </form>
</div>

</body>
</html>
