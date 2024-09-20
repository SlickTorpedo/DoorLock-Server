<?php

// Database credentials
$config = require 'config.php';
$servername = $config['servername'];
$username = $config['username'];
$password = $config['password'];
$dbname = $config['dbname'];

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $response = [
        'status' => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error,
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(500); // Internal Server Error
    exit;
}

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = [
        'status' => 'error',
        'message' => 'Method Not Allowed',
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(405); // Method Not Allowed
    exit;
}

// Get POST parameters
$ip = $_POST['ip'] ?? '';
$serial = $_POST['serial'] ?? '';
$secret = $_POST['secret'] ?? '';
$tunnelIp = $_POST['tunnel_ip'] ?? ''; // Optional tunnel_ip parameter

// Validate parameters
if (empty($serial) || empty($secret)) {
    $response = [
        'status' => 'error',
        'message' => 'Missing serial or secret parameters',
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(400); // Bad Request
    exit;
}

// Check if the table exists and create it if it does not
$tableExistsQuery = "SHOW TABLES LIKE 'devices'";
$tableExistsResult = $conn->query($tableExistsQuery);

if ($tableExistsResult->num_rows == 0) {
    $createTableQuery = "CREATE TABLE devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        serial VARCHAR(255) NOT NULL UNIQUE,
        secret VARCHAR(255) NOT NULL,
        ip VARCHAR(255) NOT NULL,
        docker_download_status INT NOT NULL DEFAULT 1,
        tunnel_url VARCHAR(255) DEFAULT ''
    )";
    if ($conn->query($createTableQuery) !== TRUE) {
        $response = [
            'status' => 'error',
            'message' => 'Table creation failed: ' . $conn->error,
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        http_response_code(500); // Internal Server Error
        exit;
    }
}

// Validate secret and retrieve the current device information
$stmt = $conn->prepare("SELECT secret FROM devices WHERE serial = ?");
$stmt->bind_param("s", $serial);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response = [
        'status' => 'error',
        'message' => 'Serial number not found',
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(404); // Not Found
    exit;
}

$row = $result->fetch_assoc();
if ($row['secret'] !== $secret) {
    $response = [
        'status' => 'error',
        'message' => 'Invalid secret',
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(401); // Unauthorized
    exit;
}

// Update IP and optionally tunnel_url if provided
if (!empty($tunnelIp)) {
    $updateStmt = $conn->prepare("UPDATE devices SET ip = ?, tunnel_url = ? WHERE serial = ?");
    $updateStmt->bind_param("sss", $ip, $tunnelIp, $serial);
} else {
    $updateStmt = $conn->prepare("UPDATE devices SET ip = ? WHERE serial = ?");
    $updateStmt->bind_param("ss", $ip, $serial);
}

if ($updateStmt->execute()) {
    $response = [
        'status' => 'success',
        'data' => [
            'ip' => $ip,
            'serial' => $serial,
            'tunnel_url' => $tunnelIp ?: 'unchanged', // Shows 'unchanged' if tunnel_url wasn't modified
        ],
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(200); // OK
} else {
    $response = [
        'status' => 'error',
        'message' => 'Failed to update IP or tunnel URL: ' . $conn->error,
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(500); // Internal Server Error
}

$stmt->close();
$updateStmt->close();
$conn->close();
