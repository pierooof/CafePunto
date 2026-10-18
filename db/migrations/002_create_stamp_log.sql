CREATE TABLE stamp_log (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    staff_id    INT UNSIGNED NULL,
    type        ENUM('stamp', 'redeem') NOT NULL,
    amount      TINYINT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL,
    KEY idx_log_customer (customer_id, created_at),
    KEY idx_log_type_date (type, created_at),
    CONSTRAINT fk_log_customer FOREIGN KEY (customer_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_log_staff FOREIGN KEY (staff_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
