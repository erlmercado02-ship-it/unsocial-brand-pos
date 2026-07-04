<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once('config.php');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($method) {
    case 'GET':
        if ($action === 'all') {
            getAllCustomers();
        } elseif ($action === 'search') {
            searchCustomers();
        } elseif ($action === 'history') {
            getCustomerHistory();
        } else {
            getCustomerByID();
        }
        break;
    
    case 'POST':
        addCustomer();
        break;
    
    case 'PUT':
        updateCustomer();
        break;
    
    default:
        apiResponse('error', 'Method not allowed');
}

function getAllCustomers() {
    global $conn;
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $sql = "SELECT c.*,
                    COUNT(s.sale_id) as total_purchases,
                    SUM(s.total_amount) as total_spent
            FROM customers c
            LEFT JOIN sales s ON c.customer_id = s.customer_id
            WHERE c.status = 'active'
            GROUP BY c.customer_id
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    apiResponse('success', 'Customers retrieved successfully', $customers);
}

function getCustomerByID() {
    global $conn;
    
    $customer_id = validateInput($_GET['id'] ?? '');
    
    if (empty($customer_id)) {
        apiResponse('error', 'Customer ID is required');
    }
    
    $sql = "SELECT * FROM customers WHERE customer_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        apiResponse('error', 'Customer not found');
    }
    
    $customer = $result->fetch_assoc();
    apiResponse('success', 'Customer retrieved successfully', $customer);
}

function searchCustomers() {
    global $conn;
    
    $search = '%' . validateInput($_GET['q'] ?? '') . '%';
    
    $sql = "SELECT * FROM customers WHERE (customer_name LIKE ? OR email LIKE ? OR phone LIKE ?) AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    apiResponse('success', 'Search completed', $customers);
}

function getCustomerHistory() {
    global $conn;
    
    $customer_id = validateInput($_GET['customer_id'] ?? '');
    
    if (empty($customer_id)) {
        apiResponse('error', 'Customer ID is required');
    }
    
    $sql = "SELECT * FROM sales WHERE customer_id = ? ORDER BY sale_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $purchases = [];
    while ($row = $result->fetch_assoc()) {
        $purchases[] = $row;
    }
    
    apiResponse('success', 'Customer purchase history retrieved', $purchases);
}

function addCustomer() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $required = ['customer_name', 'email', 'phone'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            apiResponse('error', ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }
    
    $customer_id = 'CUST-' . date('YmdHis') . '-' . rand(100, 999);
    $customer_name = validateInput($data['customer_name']);
    $email = validateInput($data['email']);
    $phone = validateInput($data['phone']);
    $address = validateInput($data['address'] ?? '');
    $city = validateInput($data['city'] ?? '');
    $loyalty_points = intval($data['loyalty_points'] ?? 0);
    $created_at = getCurrentTimestamp();
    
    $sql = "SELECT customer_id FROM customers WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        apiResponse('error', 'Email already exists');
    }
    
    $sql = "INSERT INTO customers (customer_id, customer_name, email, phone, address, city, loyalty_points, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssis', $customer_id, $customer_name, $email, $phone, $address, $city, $loyalty_points, $created_at);
    
    if ($stmt->execute()) {
        apiResponse('success', 'Customer added successfully', ['customer_id' => $customer_id]);
    } else {
        apiResponse('error', 'Failed to add customer: ' . $stmt->error);
    }
}

function updateCustomer() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['customer_id'])) {
        apiResponse('error', 'Customer ID is required');
    }
    
    $customer_id = validateInput($data['customer_id']);
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['customer_name'])) {
        $updates[] = 'customer_name = ?';
        $params[] = validateInput($data['customer_name']);
        $types .= 's';
    }
    if (isset($data['phone'])) {
        $updates[] = 'phone = ?';
        $params[] = validateInput($data['phone']);
        $types .= 's';
    }
    if (isset($data['email'])) {
        $updates[] = 'email = ?';
        $params[] = validateInput($data['email']);
        $types .= 's';
    }
    
    $updates[] = 'updated_at = ?';
    $params[] = getCurrentTimestamp();
    $types .= 's';
    
    $params[] = $customer_id;
    $types .= 's';
    
    $sql = "UPDATE customers SET " . implode(', ', $updates) . " WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        apiResponse('success', 'Customer updated successfully');
    } else {
        apiResponse('error', 'Failed to update customer: ' . $stmt->error);
    }
}
?>