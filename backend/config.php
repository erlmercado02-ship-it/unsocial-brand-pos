<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'unsocial_brand_pos');

// Store Information
define('STORE_NAME', 'Unsocial Brand');
define('STORE_LOCATION', 'San Antonio, Cavite City');
define('STORE_PHONE', '+63 XXX XXX XXXX');
define('STORE_EMAIL', 'info@unsocialbrand.com');
define('CURRENCY', '₱');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Set charset to UTF-8
$conn->set_charset('utf8mb4');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Manila');

// API Response Helper
function apiResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Validate POST data
function validateInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Generate unique transaction ID
function generateTransactionID() {
    return 'TRX-' . date('YmdHis') . '-' . rand(1000, 9999);
}

// Generate unique product ID
function generateProductID() {
    return 'PRD-' . date('YmdHis') . '-' . rand(100, 999);
}

// Get current timestamp
function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}
?>