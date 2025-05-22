USE ecommerce_db;

-- Drop and recreate products table with correct schema
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NOT NULL,
    category VARCHAR(50),
    stock_quantity INT NOT NULL DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT INTO products (name, description, price, image, category, stock_quantity, is_featured) VALUES
('Premium Headphones', 'High-quality wireless headphones with noise cancellation', 2999.99, 'headphones.jpg', 'Electronics', 50, 1),
('Organic Cotton T-Shirt', 'Comfortable 100% organic cotton t-shirt', 899.99, 'tshirt.jpg', 'Clothing', 100, 1),
('Stainless Steel Water Bottle', 'Eco-friendly reusable water bottle', 499.99, 'bottle.jpg', 'Home', 75, 0),
('Smartphone Holder', 'Adjustable stand for smartphones and tablets', 199.99, 'holder.jpg', 'Accessories', 120, 0),
('Wireless Charger', 'Fast charging pad for compatible devices', 599.99, 'charger.jpg', 'Electronics', 60, 1),
('Yoga Mat', 'Non-slip premium yoga mat', 799.99, 'yogamat.jpg', 'Fitness', 40, 0),
('Bluetooth Speaker', 'Portable waterproof speaker', 1299.99, 'speaker.jpg', 'Electronics', 30, 1),
('Leather Wallet', 'Genuine leather wallet with multiple compartments', 699.99, 'wallet.jpg', 'Accessories', 80, 0);
