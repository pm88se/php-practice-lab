-- Dedicated performance fixture; does not alter SQL-001...SQL-004 tables.
-- No secondary indexes on purpose: choosing them is the learner's task.
CREATE TABLE IF NOT EXISTS perf_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    status VARCHAR(16) NOT NULL,
    created_at DATETIME NOT NULL,
    total_cents INT UNSIGNED NOT NULL
) ENGINE=InnoDB;
