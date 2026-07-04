<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once('config.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse('error', 'Method not allowed');
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['username']) || empty($data['password'])) {
    apiResponse('error', 'Username and password are required');
}

$username = validateInput($data['username']);
$password = validateInput($data['password']);

$sql = "SELECT * FROM users WHERE username = ? AND status = 'active'";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    apiResponse('error', 'Database error: ' . $conn->error);
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    apiResponse('error', 'Invalid credentials');
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    apiResponse('error', 'Invalid credentials');
}

// Set session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];

// Generate token
$token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $token);

// Store token in session
$_SESSION['token'] = $token_hash;
$_SESSION['token_expiry'] = time() + (24 * 60 * 60); // 24 hours

apiResponse('success', 'Login successful', [
    'user_id' => $user['user_id'],
    'username' => $user['username'],
    'full_name' => $user['full_name'],
    'role' => $user['role'],
    'token' => $token
]);
?>