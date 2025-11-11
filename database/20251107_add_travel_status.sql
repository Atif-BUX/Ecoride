-- Add status column on travels to support start/stop flow
-- Values: planned (default), in_progress, completed

CREATE DATABASE IF NOT EXISTS ecoride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecoride_db;

SET @has_col := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'travels'
    AND COLUMN_NAME  = 'status'
);

SET @sql := IF(@has_col = 0,
  "ALTER TABLE travels ADD COLUMN status ENUM('planned','in_progress','completed') NOT NULL DEFAULT 'planned' AFTER price_per_seat",
  'SELECT 1'
);

PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

