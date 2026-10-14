CREATE DATABASE IF NOT EXISTS cmb_boutique CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cmb_boutique;

CREATE TABLE products (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(180) NOT NULL,
 category VARCHAR(80) NOT NULL,
 description TEXT NOT NULL,
 price DECIMAL(12,2) NOT NULL,
 image VARCHAR(255) NOT NULL DEFAULT '',
 sizes VARCHAR(160) NOT NULL DEFAULT '',
 stock INT UNSIGNED NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_products_active_category (is_active, category),
 INDEX idx_products_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 order_number BIGINT UNSIGNED NULL UNIQUE,
 reference VARCHAR(80) NOT NULL UNIQUE,
 customer_name VARCHAR(120) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 email VARCHAR(190) NOT NULL,
 address VARCHAR(500) NOT NULL,
 state VARCHAR(40) NOT NULL,
 subtotal DECIMAL(12,2) NOT NULL,
 delivery_fee DECIMAL(12,2) NOT NULL,
 total DECIMAL(12,2) NOT NULL,
 gateway ENUM('paystack','flutterwave') NOT NULL,
 status ENUM('Pending Payment','Paid / Pending Delivery','Shipped') NOT NULL DEFAULT 'Pending Payment',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 paid_at DATETIME NULL,
 INDEX idx_orders_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NULL,
 product_name VARCHAR(180) NOT NULL,
 size VARCHAR(40) NOT NULL DEFAULT '',
 qty SMALLINT UNSIGNED NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
 INDEX idx_items_order (order_id),
 INDEX idx_items_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE delivery_fees (
 state VARCHAR(40) NOT NULL PRIMARY KEY,
 fee DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_login_attempts (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 ip_hash CHAR(64) NOT NULL,
 attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_login_attempt (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products (name,category,description,price,image,sizes,stock,is_active) VALUES
('Linen Sunday Dress','Female Wears','An easy, airy dress in a breathable linen blend. Made for long afternoons and everyday plans.',38500,'','S,M,L,XL',12,1),
('Soft Form Shirt','Male Wears','A relaxed button shirt with a clean drape and room to move.',24500,'','S,M,L,XL',18,1),
('Market Day Set','Female Wears','A two-piece co-ord in a light woven fabric. Wear together or make it your own.',52000,'','S,M,L',8,1),
('Everyday Wrap Skirt','Female Wears','An adjustable wrap skirt with fluid movement and an easy fit.',29500,'','S,M,L,XL',14,1),
('The Quiet Blouse','Female Wears','A softly structured blouse with considered details for day to evening.',32000,'','S,M,L,XL',10,1),
('Weekend Trainers','Male Shoes','A clean everyday trainer selected for comfort and versatility.',41000,'','40,41,42,43',0,1);

INSERT INTO delivery_fees(state,fee) VALUES
('Abia',3500),('Adamawa',4500),('Akwa Ibom',3500),('Anambra',3000),('Bauchi',4500),('Bayelsa',3500),('Benue',3500),('Borno',5000),('Cross River',3500),('Delta',3000),('Ebonyi',3000),('Edo',2800),('Ekiti',3000),('Enugu',3000),('FCT',2500),('Gombe',4500),('Imo',3200),('Jigawa',4500),('Kaduna',3500),('Kano',4000),('Katsina',4500),('Kebbi',5000),('Kogi',3000),('Kwara',3000),('Lagos',1800),('Nasarawa',3000),('Niger',3500),('Ogun',2200),('Ondo',2800),('Osun',2800),('Oyo',2500),('Plateau',4000),('Rivers',3200),('Sokoto',5000),('Taraba',5000),('Yobe',5000),('Zamfara',5000);
