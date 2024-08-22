<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $invoice_id = $_GET['id'];

    // Fetch the invoice data
    $stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoice = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $invoice_id = $_POST['id'];
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE invoices SET patient_id = ?, doctor_id = ?, amount = ?, description = ?, status = ? WHERE id = ?");
    $stmt->bind_param("iisssi", $patient_id, $doctor_id, $amount, $description, $status, $invoice_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Invoice updated successfully!";
        header("Location: manage_invoices.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Fetch patients and doctors
$patients = $conn->query("SELECT id, username FROM users WHERE role='patient'");
$doctors = $conn->query("SELECT id, username FROM users WHERE role='doctor'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Invoice</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 24px;
            color: #343a40;
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
            color: #495057;
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
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: #ffffff;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Invoice</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="message"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <form method="post" action="edit_invoice.php">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($invoice['id']); ?>">
        
        <label for="patient_id">Patient:</label>
        <select name="patient_id" id="patient_id" required>
            <?php while ($patient = $patients->fetch_assoc()) { ?>
                <option value="<?php echo htmlspecialchars($patient['id']); ?>" <?php if ($patient['id'] == $invoice['patient_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($patient['username']); ?>
                </option>
            <?php } ?>
        </select>

        <label for="doctor_id">Doctor:</label>
        <select name="doctor_id" id="doctor_id" required>
            <?php while ($doctor = $doctors->fetch_assoc()) { ?>
                <option value="<?php echo htmlspecialchars($doctor['id']); ?>" <?php if ($doctor['id'] == $invoice['doctor_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($doctor['username']); ?>
                </option>
            <?php } ?>
        </select>

        <label for="amount">Amount:</label>
        <input type="text" name="amount" id="amount" value="<?php echo htmlspecialchars($invoice['amount']); ?>" required>

        <label for="description">Description:</label>
        <textarea name="description" id="description"><?php echo htmlspecialchars($invoice['description']); ?></textarea>

        <label for="status">Status:</label>
        <select name="status" id="status">
            <option value="pending" <?php if ($invoice['status'] == 'pending') echo 'selected'; ?>>Pending</option>
            <option value="paid" <?php if ($invoice['status'] == 'paid') echo 'selected'; ?>>Paid</option>
        </select>

        <button type="submit">Update Invoice</button>
    </form>
</div>

</body>
</html>
