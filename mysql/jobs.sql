CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    payload VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL,
    CONSTRAINT chk_jobs_status CHECK (status IN ('pending', 'processing'))
) ENGINE=InnoDB;
