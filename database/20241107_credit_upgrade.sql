-- Upgrade script: add credit system and enhanced reservation tracking
USE ecoride_db;

-- Add missing user columns (skip ones that already exist by checking INFORMATION_SCHEMA)
SET @has_credit := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'credit_balance');
IF @has_credit = 0 THEN
    ALTER TABLE users
        ADD COLUMN credit_balance INT NOT NULL DEFAULT 20 AFTER password,
        ADD COLUMN is_passenger TINYINT(1) NOT NULL DEFAULT 1 AFTER is_driver,
        ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_passenger;
ELSE
    SET @has_passenger := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_passenger');
    IF @has_passenger = 0 THEN
        ALTER TABLE users ADD COLUMN is_passenger TINYINT(1) NOT NULL DEFAULT 1 AFTER is_driver;
    END IF;
    SET @has_active := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_active');
    IF @has_active = 0 THEN
        ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_passenger;
    END IF;
END IF;

-- Reservations table adjustments
SET @has_res_status := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservations' AND COLUMN_NAME = 'status');
IF @has_res_status = 0 THEN
    ALTER TABLE reservations
        ADD COLUMN status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending' AFTER seats_booked,
        ADD COLUMN confirmation_token VARCHAR(64) NULL AFTER status,
        ADD COLUMN confirmed_at DATETIME NULL AFTER confirmation_token,
        ADD COLUMN credit_spent INT NOT NULL DEFAULT 0 AFTER confirmed_at,
        ADD COLUMN driver_credit INT NOT NULL DEFAULT 0 AFTER credit_spent;
ELSE
    SET @has_credit_spent := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservations' AND COLUMN_NAME = 'credit_spent');
    IF @has_credit_spent = 0 THEN
        ALTER TABLE reservations ADD COLUMN credit_spent INT NOT NULL DEFAULT 0 AFTER confirmed_at;
    END IF;
    SET @has_driver_credit := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservations' AND COLUMN_NAME = 'driver_credit');
    IF @has_driver_credit = 0 THEN
        ALTER TABLE reservations ADD COLUMN driver_credit INT NOT NULL DEFAULT 0 AFTER credit_spent;
    END IF;
END IF;

SET @has_res_unique := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservations' AND INDEX_NAME = 'uq_reservation_user_travel');
IF @has_res_unique = 0 THEN
    ALTER TABLE reservations ADD CONSTRAINT uq_reservation_user_travel UNIQUE KEY (travel_id, user_id);
END IF;

-- Travels table earnings column
SET @has_earnings := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'travels' AND COLUMN_NAME = 'earnings');
SET @sql := IF(@has_earnings = 0, 'ALTER TABLE travels ADD COLUMN earnings INT NOT NULL DEFAULT 0 AFTER price_per_seat', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Credit transactions table
CREATE TABLE IF NOT EXISTS credit_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reservation_id INT NULL,
    amount INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_credit_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_credit_tx_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
