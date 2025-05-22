USE ecommerce_db;

-- Add category column if it doesn't exist
ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(50) AFTER image;

-- Sample Categories: Electronics, Clothing, Home, Accessories, Fitness
UPDATE products SET category = CASE
    WHEN name LIKE '%Headphones%' OR name LIKE '%Charger%' OR name LIKE '%Speaker%' THEN 'Electronics'
    WHEN name LIKE '%T-Shirt%' THEN 'Clothing'
    WHEN name LIKE '%Bottle%' THEN 'Home'
    WHEN name LIKE '%Wallet%' OR name LIKE '%Holder%' THEN 'Accessories'
    WHEN name LIKE '%Yoga%' OR name LIKE '%Mat%' THEN 'Fitness'
    ELSE 'Other'
END WHERE category IS NULL;
