<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once('config.php');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch($method) {
    case 'GET':
        if ($action === 'summary') {
            getInventorySummary();
        } elseif ($action === 'low-stock') {
            getLowStockItems();
        } elseif ($action === 'history') {
            getInventoryHistory();
        } else {
            getAllInventory();
        }
        break;
    
    case 'POST':
        addStockMovement();
        break;
    
    default:
        apiResponse('error', 'Method not allowed');
}

function getAllInventory() {
    global $conn;
    
    $sql = "SELECT p.product_id, p.name, p.sku, p.category, p.price, p.stock_quantity, p.min_stock_level,
                    (p.stock_quantity * p.price) as stock_value,
                    CASE 
                        WHEN p.stock_quantity > p.min_stock_level THEN 'In Stock'
                        WHEN p.stock_quantity > 0 THEN 'Low Stock'
                        ELSE 'Out of Stock'
                    END as status
            FROM products p
            WHERE p.status = 'active'
            ORDER BY p.category, p.name";
    
    $result = $conn->query($sql);
    
    $inventory = [];
    while ($row = $result->fetch_assoc()) {
        $inventory[] = $row;
    }
    
    apiResponse('success', 'Inventory retrieved successfully', $inventory);
}

function getInventorySummary() {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total_products,
                SUM(stock_quantity) as total_items,
                SUM(stock_quantity * price) as total_value,
                SUM(CASE WHEN stock_quantity > min_stock_level THEN 1 ELSE 0 END) as in_stock_count,
                SUM(CASE WHEN stock_quantity <= min_stock_level AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_count
            FROM products WHERE status = 'active'";
    
    $result = $conn->query($sql);
    $summary = $result->fetch_assoc();
    
    apiResponse('success', 'Inventory summary retrieved', $summary);
}

function getLowStockItems() {
    global $conn;
    
    $sql = "SELECT product_id, name, sku, stock_quantity, min_stock_level, price,
                    (min_stock_level - stock_quantity) as reorder_quantity
            FROM products 
            WHERE stock_quantity <= min_stock_level AND status = 'active'
            ORDER BY stock_quantity ASC";
    
    $result = $conn->query($sql);
    
    $low_stock = [];
    while ($row = $result->fetch_assoc()) {
        $low_stock[] = $row;
    }
    
    apiResponse('success', 'Low stock items retrieved', $low_stock);
}

function getInventoryHistory() {
    global $conn;
    
    $product_id = isset($_GET['product_id']) ? validateInput($_GET['product_id']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $where = '';
    if (!empty($product_id)) {
        $where = " WHERE product_id = '$product_id'";
    }
    
    $sql = "SELECT * FROM inventory_movements $where ORDER BY movement_date DESC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    apiResponse('success', 'Inventory history retrieved', $history);
}

function addStockMovement() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    $required = ['product_id', 'movement_type', 'quantity'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            apiResponse('error', ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }
    
    $product_id = validateInput($data['product_id']);
    $movement_type = validateInput($data['movement_type']);
    $quantity = intval($data['quantity']);
    $reason = validateInput($data['reason'] ?? '');
    $reference = validateInput($data['reference'] ?? '');
    $movement_date = getCurrentTimestamp();
    
    if (!in_array($movement_type, ['in', 'out'])) {
        apiResponse('error', 'Invalid movement type');
    }
    
    $conn->begin_transaction();
    
    try {
        $sql = "SELECT stock_quantity FROM products WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Product not found');
        }
        
        $row = $result->fetch_assoc();
        $current_stock = $row['stock_quantity'];
        
        if ($movement_type === 'out' && $current_stock < $quantity) {
            throw new Exception('Insufficient stock');
        }
        
        $sign = $movement_type === 'in' ? '+' : '-';
        $sql = "UPDATE products SET stock_quantity = stock_quantity $sign ? WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $quantity, $product_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update stock');
        }
        
        $sql = "INSERT INTO inventory_movements (product_id, movement_type, quantity, reason, reference, movement_date) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssisss', $product_id, $movement_type, $quantity, $reason, $reference, $movement_date);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to record movement');
        }
        
        $conn->commit();
        apiResponse('success', 'Stock movement recorded successfully');
        
    } catch (Exception $e) {
        $conn->rollback();
        apiResponse('error', $e->getMessage());
    }
}
?>