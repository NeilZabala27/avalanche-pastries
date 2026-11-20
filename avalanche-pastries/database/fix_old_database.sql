-- Run ONCE in phpMyAdmin (SQL tab, with the avalanche_pastries database selected) ONLY if
-- "Add product" or "Add ingredient" shows a message like "Unknown column ..." because the database
-- was imported from an older avalanche_pastries.sql. It adds the missing columns and changes nothing else.
-- It is safe to run twice.
ALTER TABLE product
    ADD COLUMN IF NOT EXISTS flavor        VARCHAR(50)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS size          VARCHAR(50)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS quality_level VARCHAR(50)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS price         DECIMAL(10,2) DEFAULT NULL;

ALTER TABLE ingredient
    ADD COLUMN IF NOT EXISTS stock_quantity  DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS low_stock_level DECIMAL(10,2) DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS expiration_date DATE          DEFAULT NULL;
