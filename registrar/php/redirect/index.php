<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$config = require('../config.php');
$servername = $config['servername'];
$username = $config['username'];
$password = $config['password'];
$dbname = $config['dbname'];

// Function to process the serial number and redirect
function process_serial($serial, $isRequestTime = false) {
    global $servername, $username, $password, $dbname;

    if (empty($serial)) {
        echo "Serial number is required.";
        exit;
    }

    // Create a connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Retrieve tunnel_url based on serial number
    $stmt = $conn->prepare("SELECT tunnel_url FROM devices WHERE serial = ?");
    $stmt->bind_param("s", $serial);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "Serial number not found. Please contact the admin at (408) 842-1777.";
        exit;
    }

    $row = $result->fetch_assoc();
    $tunnelUrl = $row['tunnel_url'];

    // Redirect to the appropriate URL
    if (!empty($tunnelUrl)) {
        if ($isRequestTime) {
            header("Location: $tunnelUrl/request_time");
        } else {
            header("Location: $tunnelUrl");
        }
        exit;
    } else {
        echo "Tunnel URL not found for the given serial.";
        exit;
    }
}

// Check if the requestTime parameter is present
if (isset($_GET['requestTime'])) {
    $serial = $_GET['serial'] ?? '';
    process_serial($serial, true);
}

// Process GET request with serial parameter (without requestTime)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['serial'])) {
    $serial = $_GET['serial'] ?? '';
    process_serial($serial);
}

// Process form submission when request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serial = $_POST['serial'] ?? '';
    process_serial($serial);
}

// If none of the above conditions are met, display the form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirect Page</title>
</head>
<body>
    <h1>Enter Serial Number</h1>
    <form method="post" action="">
        <label for="serial">Serial Number:</label>
        <input type="text" id="serial" name="serial" required>
        <button type="submit">Redirect</button>
    </form>
</body>
</html>
