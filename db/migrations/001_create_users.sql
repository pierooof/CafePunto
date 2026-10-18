CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    phone         VARCHAR(20)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('barista', 'customer') NOT NULL DEFAULT 'customer',
    qr_token      CHAR(32)     NOT NULL,
    stamps        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL,
    UNIQUE KEY uq_users_phone (phone),
    UNIQUE KEY uq_users_qr_token (qr_token),
    CONSTRAINT chk_users_stamps CHECK (stamps <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
