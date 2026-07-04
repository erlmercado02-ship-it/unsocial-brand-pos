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
        if ($action === 'all') {
            getAllSales();
        } elseif ($action === 'summary') {
            getSalesSummary();
        } else {
            getSaleByID();
        }
        break;
    
    case 'POST':
        createSale();
        break;
    
    default:
        apiResponse('error', 'Method not allowed');
}

function getAllSales() {
    global $conn;
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $sql = "SELECT s.*, c.customer_name FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.customer_id 
            ORDER BY s.sale_date DESC LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sales = [];
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
    
    apiResponse('success', 'Sales retrieved successfully', $sales);
}

function getSalesSummary() {
    global $conn;
    
    $period = validateInput($_GET['period'] ?? 'month');
    $date_filter = "";
    
    if ($period === 'today') {
        $date_filter = "AND DATE(sale_date) = CURDATE()";
    } elseif ($period === 'week') {
        $date_filter = "AND WEEK(sale_date) = WEEK(CURDATE())";
    } elseif ($period === 'month') {
        $date_filter = "AND MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())";
    }
    
    $sql = "SELECT 
                COUNT(*) as total_transactions,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as average_sale,
                MAX(total_amount) as highest_sale,
                COUNT(DISTINCT customer_id) as unique_customers
            FROM sales WHERE status = 'completed' $date_filter";
    
    $result = $conn->query($sql);
    $summary = $result->fetch_assoc();
    
    apiResponse('success', 'Sales summary retrieved', $summary);
}

function getSaleByID() {
    global $conn;
    
    $sale_id = validateInput($_GET['id'] ?? '');
    
    if (empty($sale_id)) {
        apiResponse('error', 'Sale ID is required');
    }
    
    $sql = "SELECT s.*, c.customer_name, c.email FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.customer_id 
            WHERE s.sale_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $sale_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        apiResponse('error', 'Sale not found');
    }
    
    $sale = $result->fetch_assoc();
    
    $sql = "SELECT * FROM sale_items WHERE sale_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $sale_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    $items = [];
    while ($row = $items_result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $sale['items'] = $items;
    apiResponse('success', 'Sale retrieved successfully', $sale);
}

function createSale() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['items']) || !is_array($data['items'])) {
        apiResponse('error', 'Sale items are required');
    }
    
    $sale_id = generateTransactionID();
    $customer_id = validateInput($data['customer_id'] ?? '');
    $total_items = intval($data['total_items'] ?? 0);
    $subtotal = floatval($data['subtotal'] ?? 0);
    $discount = floatval($data['discount'] ?? 0);
    $tax = floatval($data['tax'] ?? 0);
    $total_amount = floatval($data['total_amount'] ?? 0);
    $payment_method = validateInput($data['payment_method'] ?? 'cash');
    $notes = validateInput($data['notes'] ?? '');
    $sale_date = getCurrentTimestamp();
    
    $conn->begin_transaction();
    
    try {
        $sql = "INSERT INTO sales (sale_id, customer_id, total_items, subtotal, discount, tax, total_amount, payment_method, notes, sale_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssiddddsss', $sale_id, $customer_id, $total_items, $subtotal, $discount, $tax, $total_amount, $payment_method, $notes, $sale_date);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create sale');
        }
        
        foreach ($data['items'] as $item) {
            $product_id = validateInput($item['product_id']);
            $quantity = intval($item['quantity']);
            $unit_price = floatval($item['unit_price']);
            $item_total = $quantity * $unit_price;
            
            $sql = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, item_total) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssddd', $sale_id, $product_id, $quantity, $unit_price, $item_total);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to add sale item');
            }
            
            $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('is', $quantity, $product_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update stock');
            }
        }
        
        $conn->commit();
        apiResponse('success', 'Sale created successfully', ['sale_id' => $sale_id]);
        
    } catch (Exception $e) {
        $conn->rollback();
        apiResponse('error', $e->getMessage());
    }
}
?>