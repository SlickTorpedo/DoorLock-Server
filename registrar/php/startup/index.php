<?php

// Define your webhook URL
$config = require '../config.php';
$webhook_url = $config['webhook'];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST parameters
    $ip = isset($_POST['IP']) ? trim($_POST['IP']) : null;
    $serial = isset($_POST['Serial']) ? trim($_POST['Serial']) : null;
    $secret = isset($_POST['Secret']) ? trim($_POST['Secret']) : null;

    // Check if any of the required parameters are missing
    if ($ip === null || $serial === null || $secret === null) {
        // Return a "Missing information" message
        echo "Missing information";
    } else {
        // Prepare the message content
        $msg = [
            "content" => "IP: $ip\nSerial: $serial\nSecret: $secret"
        ];
        
        // Set up the HTTP headers for the request
        $headers = ['Content-Type: application/json'];

        // Initialize cURL for the first request (sending IP, Serial, Secret)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $webhook_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($msg));

        // Execute the request and get the response
        $response = curl_exec($ch);
        
        // Close the first cURL session
        curl_close($ch);

        // Output the response (useful for debugging)
        echo $response;

        // Prepare the second message with the link
        $redirect_url = "https://registrar.philipehrbright.com/redirect/?serial=$serial";
        $nfc_link = "https://registrar.philipehrbright.com/redirect/?serial=$serial&requestTime=true";
        $msg_redirect = [
            "content" => "Setup Link: $redirect_url\nNFC Link: $nfc_link"
        ];

        // Initialize cURL for the second request (sending the redirect link)
        $ch_redirect = curl_init();
        curl_setopt($ch_redirect, CURLOPT_URL, $webhook_url);
        curl_setopt($ch_redirect, CURLOPT_POST, true);
        curl_setopt($ch_redirect, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch_redirect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_redirect, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch_redirect, CURLOPT_POSTFIELDS, json_encode($msg_redirect));

        // Execute the second request and get the response
        $response_redirect = curl_exec($ch_redirect);
        
        // Close the second cURL session
        curl_close($ch_redirect);

        // Output the second response (useful for debugging)
        echo $response_redirect;
    }
} else {
    // Handle non-POST requests
    echo "This endpoint only accepts POST requests.";
}

?>
