-- EcoRide minimal seed data (idempotent)
-- Use: SOURCE C:/xampp/htdocs/EcoRide/database/sample/seed_minimal.sql

CREATE DATABASE IF NOT EXISTS ecoride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecoride_db;

-- Ensure base configuration exists
INSERT IGNORE INTO configurations (label) VALUES ('default');
INSERT IGNORE INTO parameters (property, default_value) VALUES ('booking_auto_confirm','0');
INSERT INTO configuration_parameters (configuration_id, parameter_id, value)
SELECT c.id, p.id, '0'
FROM configurations c, parameters p
WHERE c.label = 'default' AND p.property = 'booking_auto_confirm'
ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP;

-- Test users (password = 'password')
-- Bcrypt hash for 'password' from PHP manual examples
-- Default hash is for 'password' (press the admin button to set password321)
SET @pwd := '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

INSERT INTO users (email, password, first_name, last_name, credit_balance, is_driver, is_passenger, is_active)
VALUES
  ('jean.dupont@test.fr', @pwd, 'Jean', 'Dupont', 50, 1, 0, 1),
  ('john.wick@gmail.com', @pwd, 'John','Wick',   50, 0, 1, 1)
ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), is_active=1;

-- Simple travel by driver Jean (next week)
SET @driver_id := (SELECT id FROM users WHERE email='jean.dupont@test.fr' LIMIT 1);
INSERT INTO travels (user_id, departure_city, arrival_city, departure_date, departure_time, available_seats, price_per_seat, description, total_seats)
VALUES (@driver_id, 'Chantilly', 'Paris', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '08:30:00', 3, 18.00, 'Trajet test (seed).', 3);

-- Optional: pending reservation for Alice (1 seat)
SET @passenger_id := (SELECT id FROM users WHERE email='alice@example.com' LIMIT 1);
SET @travel_id := (SELECT id FROM travels WHERE user_id=@driver_id ORDER BY id DESC LIMIT 1);
INSERT INTO reservations (travel_id, user_id, seats_booked, status, booking_date, confirmed_at, credit_spent, driver_credit)
VALUES (@travel_id, @passenger_id, 1, 'pending', CURRENT_TIMESTAMP, NULL, 0, 0)
ON DUPLICATE KEY UPDATE seats_booked=VALUES(seats_booked), status='pending';

-- Friendly base roles (if table exists)
INSERT IGNORE INTO roles (label) VALUES ('USER'), ('DRIVER'), ('ADMIN');

-- Done
