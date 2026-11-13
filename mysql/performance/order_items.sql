-- PERF-002: order lines, intentionally without an order_id secondary index.
-- Separate fixture: no changes to previous SQL lab tables.
CREATE TABLE IF NOT EXISTS perf_order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL,
    unit_price_cents INT UNSIGNED NOT NULL
) ENGINE=InnoDB;
