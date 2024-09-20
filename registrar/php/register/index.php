<?php

// Database credentials
$config = require '../config.php';
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
$serial = $_POST['serial'] ?? '';
$secret = $_POST['secret'] ?? '';
$newSecret = $_POST['new_secret'] ?? '';
$adminSecret = $_POST['admin_secret'] ?? '';
$tunnelUrl = $_POST['tunnel_url'] ?? ''; // Adding tunnel URL parameter

// Validate parameters
if (empty($serial) || empty($secret) || empty($newSecret) || empty($adminSecret)) {
    $response = [
        'status' => 'error',
        'message' => 'Missing parameters',
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(400); // Bad Request
    exit;
}

// Verify admin secret
$adminSecretHash = $config['admin_secret_hash'];
if ($adminSecret !== $adminSecretHash) {
    $response = [
        'status' => 'error',
        'message' => 'Invalid admin secret',
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(403); // Forbidden
    exit;
}

// Check if device already exists
$stmt = $conn->prepare("SELECT secret FROM devices WHERE serial = ?");
$stmt->bind_param("s", $serial);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $response = [
        'status' => 'error',
        'message' => 'Device already exists',
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(409); // Conflict
    exit;
}

// Insert new device
$ip = ''; // IP is set to empty by default
$tunnelUrl = ''; // Tunnel URL is set to empty by default
$dockerDownloadStatus = 1; // Set docker_download_status to 1 by default

$insertStmt = $conn->prepare("INSERT INTO devices (serial, secret, ip, docker_download_status, tunnel_url) VALUES (?, ?, ?, ?, ?)");
$insertStmt->bind_param("sssds", $serial, $newSecret, $ip, $dockerDownloadStatus, $tunnelUrl);
if ($insertStmt->execute()) {
    $response = [
        'status' => 'success',
        'message' => 'Device registered successfully',
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(201); // Created
} else {
    $response = [
        'status' => 'error',
        'message' => 'Failed to register device: ' . $conn->error,
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    http_response_code(500); // Internal Server Error
}

$stmt->close();
$insertStmt->close();
$conn->close();
