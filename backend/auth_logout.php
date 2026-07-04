<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

require_once('config.php');
session_start();

// Destroy session
session_destroy();

apiResponse('success', 'Logout successful');
?>