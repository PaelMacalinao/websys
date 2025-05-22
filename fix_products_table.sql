USE ecommerce_db;

-- First check if category column exists
SET @exists = 0;
SELECT COUNT(*) INTO @exists 
FROM information_schema.columns 
WHERE table_schema = 'ecommerce_db' 
AND table_name = 'products' 
AND column_name = 'category';

-- Add category column only if it doesn't exist
SET @sql = IF(@exists = 0,
    'ALTER TABLE products ADD COLUMN category VARCHAR(50) DEFAULT NULL AFTER image',
    'SELECT "Category column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update categories based on product names
UPDATE products SET category = CASE
    WHEN name LIKE '%Headphones%' OR name LIKE '%Charger%' OR name LIKE '%Speaker%' THEN 'Electronics'
    WHEN name LIKE '%T-Shirt%' THEN 'Clothing'
    WHEN name LIKE '%Bottle%' THEN 'Home'
    WHEN name LIKE '%Wallet%' OR name LIKE '%Holder%' THEN 'Accessories'
    WHEN name LIKE '%Yoga%' OR name LIKE '%Mat%' THEN 'Fitness'
    ELSE 'Other'
END WHERE category IS NULL;

-- Insert sample products if the table is empty
INSERT INTO products (name, description, price, image, category, stock_quantity, is_featured)
SELECT * FROM (
    SELECT 'Premium Headphones', 'High-quality wireless headphones with noise cancellation', 2999.99, 'headphones.jpg', 'Electronics', 50, 1
    UNION ALL SELECT 'Organic Cotton T-Shirt', 'Comfortable 100% organic cotton t-shirt', 899.99, 'tshirt.jpg', 'Clothing', 100, 1
    UNION ALL SELECT 'Stainless Steel Water Bottle', 'Eco-friendly reusable water bottle', 499.99, 'bottle.jpg', 'Home', 75, 0
    UNION ALL SELECT 'Smartphone Holder', 'Adjustable stand for smartphones and tablets', 199.99, 'holder.jpg', 'Accessories', 120, 0
    UNION ALL SELECT 'Wireless Charger', 'Fast charging pad for compatible devices', 599.99, 'charger.jpg', 'Electronics', 60, 1
    UNION ALL SELECT 'Yoga Mat', 'Non-slip premium yoga mat', 799.99, 'yogamat.jpg', 'Fitness', 40, 0
    UNION ALL SELECT 'Bluetooth Speaker', 'Portable waterproof speaker', 1299.99, 'speaker.jpg', 'Electronics', 30, 1
    UNION ALL SELECT 'Leather Wallet', 'Genuine leather wallet with multiple compartments', 699.99, 'wallet.jpg', 'Accessories', 80, 0
) AS new_products
WHERE NOT EXISTS (SELECT 1 FROM products LIMIT 1);
