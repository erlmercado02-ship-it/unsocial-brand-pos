-- Create Database
CREATE DATABASE IF NOT EXISTS unsocial_brand_pos;
USE unsocial_brand_pos;

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    product_id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(50) UNIQUE NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    stock_quantity INT NOT NULL DEFAULT 0,
    min_stock_level INT NOT NULL DEFAULT 10,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customers Table
CREATE TABLE IF NOT EXISTS customers (
    customer_id VARCHAR(50) PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    loyalty_points INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sales Table
CREATE TABLE IF NOT EXISTS sales (
    sale_id VARCHAR(50) PRIMARY KEY,
    customer_id VARCHAR(50),
    total_items INT NOT NULL DEFAULT 0,
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
    discount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    tax DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    notes TEXT,
    status VARCHAR(20) DEFAULT 'completed',
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    INDEX idx_sale_date (sale_date),
    INDEX idx_customer_id (customer_id)
);

-- Sale Items Table
CREATE TABLE IF NOT EXISTS sale_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    item_total DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_sale_id (sale_id)
);

-- Inventory Movements Table
CREATE TABLE IF NOT EXISTS inventory_movements (
    movement_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(50) NOT NULL,
    movement_type ENUM('in', 'out') NOT NULL,
    quantity INT NOT NULL,
    reason VARCHAR(100),
    reference VARCHAR(100),
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_product_id (product_id),
    INDEX idx_movement_date (movement_date)
);

-- Users Table (for staff)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'cashier',
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO products (product_id, name, sku, category, price, description, stock_quantity, min_stock_level) VALUES
('PRD-20240001', 'T-Shirt Classic Black', 'TS-001', 'Shirts', 499.00, 'Classic black t-shirt', 45, 20),
('PRD-20240002', 'Hoodie Oversized', 'HD-001', 'Hoodies', 899.00, 'Oversized hoodie', 8, 15),
('PRD-20240003', 'Cap Baseball', 'CP-001', 'Accessories', 299.00, 'Baseball cap', 62, 20);

INSERT INTO customers (customer_id, customer_name, email, phone, loyalty_points) VALUES
('CUST-001', 'John Doe', 'john@email.com', '+63 912 345 6789', 1245),
('CUST-002', 'Jane Smith', 'jane@email.com', '+63 913 456 7890', 890);

INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@unsocialbrand.com', '$2y$10$hashed_password_here', 'Administrator', 'admin'),
('cashier1', 'cashier@unsocialbrand.com', '$2y$10$hashed_password_here', 'John Cashier', 'cashier');