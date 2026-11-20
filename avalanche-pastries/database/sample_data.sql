-- Test data for Avalanche Pastries. Run ONCE in phpMyAdmin (SQL tab) after importing avalanche_pastries.sql.
USE avalanche_pastries;

-- Products (prices are only examples)
INSERT INTO product (product_name, flavor, size, quality_level, price) VALUES
('Muffin',     'Chocolate', 'Regular', 'Standard',  45.00),
('Cream Puff', 'Vanilla',   'Regular', 'Standard',  35.00),
('Brownie',    'Fudge',     'Regular', 'Premium',   60.00),
('Cake',       'Mocha',     '8 inch',  'Premium',  750.00);

-- A pre-order date for the order form
INSERT INTO preorder (preorder_date) VALUES (DATE_ADD(CURDATE(), INTERVAL 7 DAY));

-- Ingredients (one is low, one is out, one is expiring, one is expired, so every alert can be seen)
INSERT INTO ingredient (ingredient_name, stock_quantity, low_stock_level, expiration_date) VALUES
('All-purpose flour', 25.00,  5.00, DATE_ADD(CURDATE(), INTERVAL 90 DAY)),
('Sugar',              2.00,  5.00, DATE_ADD(CURDATE(), INTERVAL 120 DAY)),
('Butter',             1.50,  2.00, DATE_ADD(CURDATE(), INTERVAL 2 DAY)),
('Eggs',               0.00, 12.00, DATE_ADD(CURDATE(), INTERVAL 10 DAY)),
('Cocoa powder',       4.00,  1.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY));
