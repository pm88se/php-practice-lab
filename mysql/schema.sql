CREATE TABLE IF NOT EXISTS accounts (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    owner VARCHAR(100) NOT NULL,
    balance_cents BIGINT NOT NULL,
    CONSTRAINT chk_balance_nonnegative CHECK (balance_cents >= 0)
) ENGINE=InnoDB;
