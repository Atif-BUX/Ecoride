-- EcoRide schema upgrade script
-- Aligns the relational model with the MCD (Annexe 1)

-- Create helper schema if missing
CREATE DATABASE IF NOT EXISTS ecoride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecoride_db;

-- ---------------------------------------------------------------------------
-- Core reference tables
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(50) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS brands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(80) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Users table enrichments
-- ---------------------------------------------------------------------------

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone_number VARCHAR(50) NULL AFTER email,
    ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL AFTER phone_number,
    ADD COLUMN IF NOT EXISTS birth_date DATE NULL AFTER address,
    ADD COLUMN IF NOT EXISTS pseudo VARCHAR(50) NULL AFTER birth_date,
    ADD COLUMN IF NOT EXISTS photo LONGBLOB NULL AFTER pseudo,
    ADD COLUMN IF NOT EXISTS photo_mime_type VARCHAR(50) NULL AFTER photo,
    ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER photo_mime_type,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD COLUMN IF NOT EXISTS role_primary_id INT UNSIGNED NULL AFTER updated_at;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
    ADD CONSTRAINT fk_users_role_primary
        FOREIGN KEY (role_primary_id) REFERENCES roles(id)
        ON UPDATE CASCADE ON DELETE SET NULL;

-- ---------------------------------------------------------------------------
-- Vehicle and travel domain
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    brand_id INT UNSIGNED NULL,
    model VARCHAR(80) NOT NULL,
    license_plate VARCHAR(20) NOT NULL,
    energy VARCHAR(30) NULL,
    color VARCHAR(40) NULL,
    first_registration_date DATE NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_vehicle_license (license_plate),
    INDEX idx_vehicle_user (user_id),
    INDEX idx_vehicle_brand (brand_id),
    CONSTRAINT fk_vehicle_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_vehicle_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE travels
    ADD COLUMN IF NOT EXISTS vehicle_id INT UNSIGNED NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT 'open' AFTER price_per_seat,
    ADD COLUMN IF NOT EXISTS nb_place INT NULL AFTER available_seats,
    ADD COLUMN IF NOT EXISTS prix_personne DECIMAL(10,2) NULL AFTER price_per_seat,
    ADD INDEX IF NOT EXISTS idx_travels_vehicle (vehicle_id),
    ADD CONSTRAINT fk_travels_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL;

-- Ensure total_seats column exists and is consistent with schema
ALTER TABLE travels
    ADD COLUMN IF NOT EXISTS total_seats INT NOT NULL DEFAULT 1 AFTER available_seats;

-- ---------------------------------------------------------------------------
-- Reservations (participants)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    travel_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    seats_booked INT NOT NULL DEFAULT 1,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    booking_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reservation_unique (travel_id, user_id),
    INDEX idx_reservation_user (user_id),
    CONSTRAINT fk_reservation_travel FOREIGN KEY (travel_id) REFERENCES travels(id) ON DELETE CASCADE,
    CONSTRAINT fk_reservation_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Reviews (avis)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    travel_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    reviewed_user_id INT UNSIGNED NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment VARCHAR(255) NULL,
    status ENUM('pending', 'published', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_unique (travel_id, reviewer_id),
    INDEX idx_reviews_reviewed (reviewed_user_id),
    INDEX idx_reviews_travel (travel_id),
    CONSTRAINT fk_reviews_travel FOREIGN KEY (travel_id) REFERENCES travels(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewed FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Configuration & parameters
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS configurations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parameters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property VARCHAR(100) NOT NULL UNIQUE,
    default_value VARCHAR(255) NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuration_parameters (
    configuration_id INT UNSIGNED NOT NULL,
    parameter_id INT UNSIGNED NOT NULL,
    value VARCHAR(255) NULL,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (configuration_id, parameter_id),
    CONSTRAINT fk_cfg_param_configuration FOREIGN KEY (configuration_id) REFERENCES configurations(id) ON DELETE CASCADE,
    CONSTRAINT fk_cfg_param_parameter FOREIGN KEY (parameter_id) REFERENCES parameters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed base roles & configuration (idempotent)
INSERT IGNORE INTO roles (label) VALUES ('USER'), ('DRIVER'), ('ADMIN');

INSERT IGNORE INTO configurations (label) VALUES ('default');

INSERT IGNORE INTO parameters (property, default_value, description) VALUES
    ('site_name', 'EcoRide', 'Nom affiché dans le header'),
    ('max_seats_per_travel', '6', 'Nombre maximum de places pour un trajet'),
    ('booking_auto_confirm', '1', 'Confirmer automatiquement les réservations');

INSERT IGNORE INTO configuration_parameters (configuration_id, parameter_id, value)
SELECT c.id, p.id, p.default_value
FROM configurations c
JOIN parameters p
WHERE c.label = 'default';

-- ---------------------------------------------------------------------------
-- Views or helper data (optional)
-- ---------------------------------------------------------------------------

-- Example view: upcoming travels with driver & vehicle infos
CREATE OR REPLACE VIEW vw_travel_overview AS
SELECT
    t.id AS travel_id,
    t.departure_city,
    t.arrival_city,
    t.departure_date,
    t.departure_time,
    t.available_seats,
    t.total_seats,
    t.status,
    u.id AS driver_id,
    u.first_name AS driver_first_name,
    u.last_name AS driver_last_name,
    v.id AS vehicle_id,
    v.model AS vehicle_model,
    b.label AS vehicle_brand
FROM travels t
JOIN users u ON u.id = t.user_id
LEFT JOIN vehicles v ON v.id = t.vehicle_id
LEFT JOIN brands b ON b.id = v.brand_id;
