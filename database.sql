-- =========================================================
-- CRM Database Schema
-- Project: Customer Relationship Management System
-- =========================================================

CREATE DATABASE IF NOT EXISTS crm_db;
USE crm_db;

-- ---------------------------------------------------------
-- 1. ADMIN / USERS TABLE (for Login & Profile)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,   -- stored as password_hash()
    full_name VARCHAR(100) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin login -> username: admin | password: admin123
INSERT INTO admin_users (username, password, full_name, email)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin@crm.com');
-- NOTE: The hash above is a placeholder. Run reset_password.php (included) once
-- to set a real bcrypt hash for 'admin123', OR use the PHP snippet in README.

-- ---------------------------------------------------------
-- 2. CUSTOMERS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) NOT NULL UNIQUE,   -- e.g. C001
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO customers (customer_code, name, email, phone, address, status) VALUES
('C001', 'John', 'john@email.com', '9876543210', 'Chennai, TN', 'Active'),
('C002', 'Priya Sharma', 'priya@email.com', '9876501234', 'Salem, TN', 'Active'),
('C003', 'Ravi Kumar', 'ravi@email.com', '9998887771', 'Coimbatore, TN', 'Inactive');

-- ---------------------------------------------------------
-- 3. EMPLOYEES TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) NOT NULL UNIQUE,   -- e.g. E001
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    designation VARCHAR(100) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    salary DECIMAL(10,2) DEFAULT 0,
    joining_date DATE DEFAULT NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO employees (employee_code, name, email, phone, designation, department, salary, joining_date, status) VALUES
('E001', 'Arun Raj', 'arun@crm.com', '9000011111', 'Sales Executive', 'Sales', 25000.00, '2023-01-15', 'Active'),
('E002', 'Divya Sri', 'divya@crm.com', '9000022222', 'HR Manager', 'HR', 40000.00, '2022-06-01', 'Active');

-- ---------------------------------------------------------
-- 4. PRODUCTS / SERVICES TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(20) NOT NULL UNIQUE,   -- e.g. P001
    name VARCHAR(100) NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (product_code, name, category, price, stock) VALUES
('P001', 'Laptop', 'Electronics', 55000.00, 25),
('P002', 'Mouse', 'Accessories', 600.00, 120),
('P003', 'Keyboard', 'Accessories', 1200.00, 80);

-- ---------------------------------------------------------
-- 5. ORDERS TABLE (header)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(20) NOT NULL UNIQUE,     -- e.g. ORD001
    customer_id INT NOT NULL,
    order_date DATE NOT NULL,
    status ENUM('Pending','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------
-- 6. ORDER ITEMS TABLE (line items -> for invoice)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Sample order
INSERT INTO orders (order_code, customer_id, order_date, status, total_amount) VALUES
('ORD001', 1, CURDATE(), 'Processing', 56200.00);

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES
(1, 1, 1, 55000.00, 55000.00),
(1, 2, 2, 600.00, 1200.00);

-- ---------------------------------------------------------
-- 7. ACTIVITY LOG TABLE (for Dashboard "Recent Activities")
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_desc VARCHAR(255) NOT NULL,
    activity_type VARCHAR(50) DEFAULT 'General',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO activity_log (activity_desc, activity_type) VALUES
('New customer "Priya Sharma" added', 'Customer'),
('Order ORD001 placed for John', 'Order'),
('Product "Laptop" stock updated', 'Product');
