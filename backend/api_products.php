<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once('config.php');

$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_GET['action']) ? $_GET['action'] : '';

switch($method) {
    case 'GET':
        if ($request === 'all') {
            getAllProducts();
        } elseif ($request === 'search') {
            searchProducts();
        } elseif ($request === 'stock') {
            getStockStatus();
        } else {
            getProductByID();
        }
        break;
    
    case 'POST':
        addProduct();
        break;
    
    case 'PUT':
        updateProduct();
        break;
    
    case 'DELETE':
        deleteProduct();
        break;
    
    default:
        apiResponse('error', 'Method not allowed');
}

function getAllProducts() {
    global $conn;
    
    $sql = "SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if (!$result) {
        apiResponse('error', 'Query failed: ' . $conn->error);
    }
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    apiResponse('success', 'Products retrieved successfully', $products);
}

function getProductByID() {
    global $conn;
    
    $product_id = validateInput($_GET['id'] ?? '');
    
    if (empty($product_id)) {
        apiResponse('error', 'Product ID is required');
    }
    
    $sql = "SELECT * FROM products WHERE product_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        apiResponse('error', 'Product not found');
    }
    
    $product = $result->fetch_assoc();
    apiResponse('success', 'Product retrieved successfully', $product);
}

function searchProducts() {
    global $conn;
    
    $search = '%' . validateInput($_GET['q'] ?? '') . '%';
    
    $sql = "SELECT * FROM products WHERE (name LIKE ? OR description LIKE ? OR sku LIKE ?) AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $search, $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    apiResponse('success', 'Search completed', $products);
}

function getStockStatus() {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN stock_quantity > min_stock_level THEN 1 ELSE 0 END) as in_stock,
                SUM(CASE WHEN stock_quantity <= min_stock_level AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
            FROM products WHERE status = 'active'";
    
    $result = $conn->query($sql);
    $status = $result->fetch_assoc();
    
    apiResponse('success', 'Stock status retrieved', $status);
}

function addProduct() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $required_fields = ['name', 'sku', 'price', 'stock_quantity', 'category'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            apiResponse('error', ucfirst($field) . ' is required');
        }
    }
    
    $product_id = generateProductID();
    $name = validateInput($data['name']);
    $sku = validateInput($data['sku']);
    $price = floatval($data['price']);
    $stock_quantity = intval($data['stock_quantity']);
    $category = validateInput($data['category']);
    $description = validateInput($data['description'] ?? '');
    $min_stock = intval($data['min_stock_level'] ?? 10);
    $created_at = getCurrentTimestamp();
    
    $sql = "INSERT INTO products (product_id, name, sku, price, stock_quantity, min_stock_level, category, description, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssdiiss', $product_id, $name, $sku, $price, $stock_quantity, $min_stock, $category, $description, $created_at);
    
    if ($stmt->execute()) {
        apiResponse('success', 'Product added successfully', ['product_id' => $product_id]);
    } else {
        apiResponse('error', 'Failed to add product: ' . $stmt->error);
    }
}

function updateProduct() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['product_id'])) {
        apiResponse('error', 'Product ID is required');
    }
    
    $product_id = validateInput($data['product_id']);
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['name'])) {
        $updates[] = 'name = ?';
        $params[] = validateInput($data['name']);
        $types .= 's';
    }
    if (isset($data['price'])) {
        $updates[] = 'price = ?';
        $params[] = floatval($data['price']);
        $types .= 'd';
    }
    if (isset($data['stock_quantity'])) {
        $updates[] = 'stock_quantity = ?';
        $params[] = intval($data['stock_quantity']);
        $types .= 'i';
    }
    if (isset($data['category'])) {
        $updates[] = 'category = ?';
        $params[] = validateInput($data['category']);
        $types .= 's';
    }
    
    $updates[] = 'updated_at = ?';
    $params[] = getCurrentTimestamp();
    $types .= 's';
    
    $params[] = $product_id;
    $types .= 's';
    
    $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        apiResponse('success', 'Product updated successfully');
    } else {
        apiResponse('error', 'Failed to update product: ' . $stmt->error);
    }
}

function deleteProduct() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['product_id'])) {
        apiResponse('error', 'Product ID is required');
    }
    
    $product_id = validateInput($data['product_id']);
    $updated_at = getCurrentTimestamp();
    
    $sql = "UPDATE products SET status = 'deleted', updated_at = ? WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $updated_at, $product_id);
    
    if ($stmt->execute()) {
        apiResponse('success', 'Product deleted successfully');
    } else {
        apiResponse('error', 'Failed to delete product: ' . $stmt->error);
    }
}
?>