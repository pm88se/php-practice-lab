CREATE TABLE IF NOT EXISTS payment_events (
    event_id VARCHAR(100) NOT NULL PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    amount_cents BIGINT NOT NULL,
    CONSTRAINT chk_payment_positive CHECK (amount_cents > 0)
) ENGINE=InnoDB;
