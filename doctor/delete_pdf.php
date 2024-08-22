<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['file_id'])) {
    $fileId = $_POST['file_id'];

    // Fetch file information to get file path
    $fileQuery = $conn->query("SELECT * FROM pdf_files WHERE id = '$fileId'");
    $fileData = $fileQuery->fetch_assoc();

    if ($fileData) {
        // Delete file from directory
        $filePath = '../Pdf/' . $fileData['file_name'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete file record from database
        $deleteQuery = $conn->query("DELETE FROM pdf_files WHERE id = '$fileId'");
        if ($deleteQuery) {
            // Redirect back to Doctor Dashboard after deletion
            header("Location: test_lab_results.php");
            exit;
        } else {
            echo "Error deleting file from database.";
        }
    } else {
        echo "File not found.";
    }
} else {
    echo "Invalid request.";
}
?>
<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['file_id'])) {
    $fileId = $_POST['file_id'];

    // Fetch file information to get file path
    $fileQuery = $conn->query("SELECT * FROM pdf_files WHERE id = '$fileId'");
    $fileData = $fileQuery->fetch_assoc();

    if ($fileData) {
        // Delete file from directory
        $filePath = '../Pdf/' . $fileData['file_name'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete file record from database
        $deleteQuery = $conn->query("DELETE FROM pdf_files WHERE id = '$fileId'");
        if ($deleteQuery) {
            // Redirect back to Doctor Dashboard after deletion
            header("Location: test_lab_results.php");
            exit;
        } else {
            echo "Error deleting file from database.";
        }
    } else {
        echo "File not found.";
    }
} else {
    echo "Invalid request.";
}
?>
<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['file_id'])) {
    $fileId = $_POST['file_id'];

    // Fetch file information to get file path
    $fileQuery = $conn->query("SELECT * FROM pdf_files WHERE id = '$fileId'");
    $fileData = $fileQuery->fetch_assoc();

    if ($fileData) {
        // Delete file from directory
        $filePath = '../Pdf/' . $fileData['file_name'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete file record from database
        $deleteQuery = $conn->query("DELETE FROM pdf_files WHERE id = '$fileId'");
        if ($deleteQuery) {
            // Redirect back to Doctor Dashboard after deletion
            header("Location: test_lab_results.php");
            exit;
        } else {
            echo "Error deleting file from database.";
        }
    } else {
        echo "File not found.";
    }
} else {
    echo "Invalid request.";
}
?>
<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['file_id'])) {
    $fileId = $_POST['file_id'];

    // Fetch file information to get file path
    $fileQuery = $conn->query("SELECT * FROM pdf_files WHERE id = '$fileId'");
    $fileData = $fileQuery->fetch_assoc();

    if ($fileData) {
        // Delete file from directory
        $filePath = '../Pdf/' . $fileData['file_name'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete file record from database
        $deleteQuery = $conn->query("DELETE FROM pdf_files WHERE id = '$fileId'");
        if ($deleteQuery) {
            // Redirect back to Doctor Dashboard after deletion
            header("Location: test_lab_results.php");
            exit;
        } else {
            echo "Error deleting file from database.";
        }
    } else {
        echo "File not found.";
    }
} else {
    echo "Invalid request.";
}
?>
