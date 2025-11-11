-- Add composite index on travels(departure_date, departure_time)
-- Safe/idempotent for MariaDB 10.4 and MySQL 5.7/8.0

CREATE DATABASE IF NOT EXISTS ecoride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecoride_db;

SET @has_idx := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'travels'
    AND INDEX_NAME   = 'idx_travels_departure'
);

SET @sql := IF(@has_idx = 0,
  'ALTER TABLE travels ADD INDEX idx_travels_departure (departure_date, departure_time)',
  'SELECT 1'
);

PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

