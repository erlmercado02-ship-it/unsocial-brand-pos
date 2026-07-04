# Unsocial Brand - Point of Sales & Inventory System

## 📋 Overview
A comprehensive POS and Inventory Management System for Unsocial Brand retail store in San Antonio, Cavite City, Philippines.

---

## 🛠️ Technology Stack

### Frontend
- HTML5
- CSS3 (Responsive Design)
- JavaScript (Vanilla)

### Backend
- PHP 7.4+
- MySQL 5.7+

### Database
- 6 Tables (Products, Customers, Sales, Sale Items, Inventory Movements, Users)

---

## 📁 Project Structure

```
unsocial-brand-pos/
├── public/                          # Frontend files
│   ├── index.html                   # Landing page
│   ├── dashboard.html               # Admin dashboard
│   ├── css/
│   │   ├── styles.css               # Homepage styles
│   │   └── dashboard-styles.css     # Dashboard styles
│   └── js/
│       ├── script.js                # Homepage JS
│       └── dashboard-script.js      # Dashboard JS
├── backend/                         # Backend PHP files
│   ├── config.php                   # Database configuration
│   ├── database.sql                 # Database schema
│   ├── auth_login.php               # User authentication
│   ├── auth_logout.php              # User logout
│   ├── api_products.php             # Products API
│   ├── api_sales.php                # Sales API
│   ├── api_inventory.php            # Inventory API
│   └── api_customers.php            # Customers API
├── .htaccess                        # URL rewriting
└── README.md                        # Documentation
```

---

## 🚀 Installation

### 1. Create Database
```bash
Import backend/database.sql into MySQL
```

### 2. Configure Database
Edit `backend/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'unsocial_brand_pos');
```

### 3. Start Server
```bash
php -S localhost:8000
```

### 4. Access Application
- Landing Page: `http://localhost:8000/public/index.html`
- Dashboard: `http://localhost:8000/public/dashboard.html`

---

## 📡 API Endpoints

### Authentication
- `POST /backend/auth_login.php` - User login
- `POST /backend/auth_logout.php` - User logout

### Products
- `GET /backend/api_products.php?action=all` - Get all products
- `GET /backend/api_products.php?action=search&q=keyword` - Search
- `POST /backend/api_products.php` - Add product
- `PUT /backend/api_products.php` - Update product
- `DELETE /backend/api_products.php` - Delete product

### Sales
- `GET /backend/api_sales.php?action=all` - Get all sales
- `POST /backend/api_sales.php` - Create sale
- `GET /backend/api_sales.php?action=summary` - Sales summary

### Inventory
- `GET /backend/api_inventory.php` - Get all inventory
- `GET /backend/api_inventory.php?action=low-stock` - Low stock items
- `POST /backend/api_inventory.php` - Add stock movement

### Customers
- `GET /backend/api_customers.php?action=all` - Get all customers
- `POST /backend/api_customers.php` - Add customer
- `PUT /backend/api_customers.php` - Update customer

---

## 💾 Database Tables

### products
- product_id, name, sku, category, price, stock_quantity, min_stock_level

### customers
- customer_id, customer_name, email, phone, loyalty_points

### sales
- sale_id, customer_id, total_amount, payment_method, sale_date

### sale_items
- item_id, sale_id, product_id, quantity, unit_price

### inventory_movements
- movement_id, product_id, movement_type, quantity, movement_date

### users
- user_id, username, email, password_hash, role

---

## ✅ Features

✓ Point of Sales System
✓ Inventory Management
✓ Product Catalog
✓ Sales Tracking
✓ Customer Management
✓ Revenue Analytics
✓ Stock Alerts
✓ User Authentication
✓ Responsive Dashboard
✓ Transaction History

---

## 👨‍💼 Support

**Store Location:** San Antonio, Cavite City, Philippines
**Contact:** info@unsocialbrand.com

---

## 📄 License

Property of Unsocial Brand © 2024