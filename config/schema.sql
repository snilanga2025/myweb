-- SQL schema for the restaurant POS system

-- Table for food/drink categories
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL
);

-- Table for individual menu items
CREATE TABLE menu_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category_id INT,
    image_url VARCHAR(255),
    availability BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Table for customer orders
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    customer_name VARCHAR(255),
    total_amount DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) NOT NULL,
    grand_total DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending' -- e.g., 'pending', 'completed', 'cancelled'
);

-- Table for items within an order (junction table)
CREATE TABLE order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    item_id INT,
    quantity INT NOT NULL,
    item_price DECIMAL(10, 2) NOT NULL, -- Price at the time of order
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (item_id) REFERENCES menu_items(item_id)
);

-- Table for staff members
CREATE TABLE staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50), -- e.g., 'admin', 'waiter', 'kitchen'
    full_name VARCHAR(255)
);

-- Table for sales and system logs
CREATE TABLE sales_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NULL, -- Optional, can be NULL if the log is not order-specific
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT, -- e.g., 'Order #123 completed', 'User X logged in'
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

-- Table for inventory management
CREATE TABLE inventory (
    inventory_item_id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(255) NOT NULL UNIQUE, -- Could be menu item name or ingredient name
    menu_item_id INT NULL, -- Optional, if this inventory item directly maps to a menu item
    stock_quantity INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(item_id)
);

-- Table for application settings
CREATE TABLE settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'tax_rate', 'printer1_ip'
    setting_value VARCHAR(255)
);
