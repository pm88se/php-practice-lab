CREATE TABLE IF NOT EXISTS inventory (
    product_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    sku VARCHAR(80) NOT NULL,
    available INT NOT NULL,
    CONSTRAINT chk_inventory_nonnegative CHECK (available >= 0)
) ENGINE=InnoDB;
