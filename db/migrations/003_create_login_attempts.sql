CREATE TABLE login_attempts (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ip         VARCHAR(45) NOT NULL,
    phone      VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_attempts_phone (phone, created_at),
    KEY idx_attempts_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
