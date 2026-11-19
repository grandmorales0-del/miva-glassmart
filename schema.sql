CREATE DATABASE IF NOT EXISTS glassmart_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE glassmart_db;

CREATE TABLE IF NOT EXISTS users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 phone VARCHAR(30) NULL,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','manager','staff','customer') NOT NULL DEFAULT 'customer',
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL UNIQUE,
 selling_price DECIMAL(12,2) NOT NULL,
 quantity INT NOT NULL DEFAULT 0,
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales (
 id INT AUTO_INCREMENT PRIMARY KEY,
 receipt_no VARCHAR(40) NOT NULL UNIQUE,
 product_id INT NOT NULL,
 user_id INT NOT NULL,
 quantity INT NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 total_amount DECIMAL(12,2) NOT NULL,
 payment_method VARCHAR(40) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(product_id) REFERENCES products(id),
 FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchases (
 id INT AUTO_INCREMENT PRIMARY KEY,
 product_id INT NOT NULL,
 quantity INT NOT NULL,
 supplier_name VARCHAR(160) NOT NULL,
 unit_cost DECIMAL(12,2) NOT NULL,
 total_cost DECIMAL(12,2) NOT NULL,
 user_id INT NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(product_id) REFERENCES products(id),
 FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_no VARCHAR(40) NOT NULL UNIQUE,
 customer_name VARCHAR(120) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 product_id INT NOT NULL,
 quantity INT NOT NULL,
 notes TEXT NULL,
 status ENUM('Pending','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
 source ENUM('Online','Staff') NOT NULL DEFAULT 'Online',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sms_notifications (
 id INT AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(160) NOT NULL,
 message TEXT NOT NULL,
 recipient_phone VARCHAR(30) NOT NULL,
 status ENUM('Queued','Sent','Failed') NOT NULL DEFAULT 'Queued',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO products(name,selling_price,quantity) VALUES
('Clear glass 4mm',850,24),('Clear glass 5mm',1100,14),('Tinted glass',1450,4),
('Mirror glass',1800,8),('Glass fittings set',650,3);
