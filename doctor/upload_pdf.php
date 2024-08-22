<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['patient_id'])) {
    $patientId = $_POST['patient_id'];

    // Check if file was uploaded without errors
    if ($_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['pdf_file']['name']);
        $fileTmpName = $_FILES['pdf_file']['tmp_name'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validate file extension (example: allow only PDF files)
        if ($fileType !== 'pdf') {
            echo "Only PDF files are allowed.";
            exit;
        }

        // Directory where PDF files will be stored
        $uploadDirectory = '../Pdf/';

        // Move uploaded file to desired directory
        $filePath = $uploadDirectory . $fileName;
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Insert file details into database
            $insertQuery = "INSERT INTO pdf_files (file_name, file_path, doctor_id, patient_id) 
                            VALUES ('$fileName', '$filePath', '$userId', '$patientId')";
            if ($conn->query($insertQuery)) {
                // Success message with CSS styling
                echo '<!DOCTYPE html>
                      <html lang="en">
                      <head>
                          <meta charset="UTF-8">
                          <meta name="viewport" content="width=device-width, initial-scale=1.0">
                          <title>File Upload Success</title>
                          <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                          <style>
                              body {
                                  font-family: Arial, sans-serif;
                                  background-color: #f8f9fa;
                                  display: flex;
                                  justify-content: center;
                                  align-items: center;
                                  height: 100vh;
                              }
                              .message {
                                  padding: 20px;
                                  background-color: #d4edda;
                                  border: 1px solid #c3e6cb;
                                  border-radius: .25rem;
                                  text-align: center;
                                  max-width: 400px;
                                  width: 100%;
                              }
                          </style>
                      </head>
                      <body>
                          <div class="message">
                              <h2>File uploaded successfully!</h2>
                              <p>The file has been uploaded and associated with the selected patient.</p>
                              <p>You will be redirected to the Doctor Dashboard shortly.</p>
                          </div>
                          <script>
                              setTimeout(function() {
                                  window.location.href = "test_lab_results.php";
                              }, 3000); // Redirect after 3 seconds
                          </script>
                      </body>
                      </html>';
                exit;
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            echo "Error uploading file.";
        }
    } else {
        echo "Upload error: " . $_FILES['pdf_file']['error'];
    }
}
?>
