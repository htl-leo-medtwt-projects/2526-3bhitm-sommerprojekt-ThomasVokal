-- CarFixFast MySQL Setup

CREATE DATABASE IF NOT EXISTS carfixfast
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'carfix'@'localhost' IDENTIFIED BY 'carfix';
CREATE USER IF NOT EXISTS 'carfix'@'%' IDENTIFIED BY 'carfix';
GRANT ALL PRIVILEGES ON carfixfast.* TO 'carfix'@'localhost';
GRANT ALL PRIVILEGES ON carfixfast.* TO 'carfix'@'%';
FLUSH PRIVILEGES;

USE carfixfast;

-- Kunden
CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NULL,
  email VARCHAR(255) NULL,
  phone VARCHAR(50) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Fahrzeuge
CREATE TABLE IF NOT EXISTS vehicles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NULL,
  license_plate VARCHAR(20) NOT NULL,
  make VARCHAR(100) NOT NULL,
  model VARCHAR(100) NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  mileage INT UNSIGNED NOT NULL DEFAULT 0,
  engine VARCHAR(100) NULL,
  color VARCHAR(50) NULL,
  vin VARCHAR(50) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_vehicles_license_plate UNIQUE (license_plate),
  CONSTRAINT uq_vehicles_vin UNIQUE (vin),
  CONSTRAINT fk_vehicles_customer
    FOREIGN KEY (customer_id) REFERENCES customers(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_vehicles_customer_id ON vehicles(customer_id);
CREATE INDEX idx_vehicles_make_model ON vehicles(make, model);

-- Termine
CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT UNSIGNED NOT NULL,
  appointment_datetime DATETIME NOT NULL,
  status ENUM('angefragt', 'bestaetigt', 'in_arbeit', 'abgeschlossen', 'storniert') NOT NULL DEFAULT 'angefragt',
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_appointments_vehicle
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_appointments_vehicle_id ON appointments(vehicle_id);
CREATE INDEX idx_appointments_status_date ON appointments(status, appointment_datetime);

-- Digitaler Werkstattpass / Historie
CREATE TABLE IF NOT EXISTS service_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT UNSIGNED NOT NULL,
  service_date DATE NOT NULL,
  service_name VARCHAR(255) NOT NULL,
  mileage INT UNSIGNED NOT NULL,
  cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  mechanic VARCHAR(150) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_service_history_vehicle
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_service_history_vehicle_id ON service_history(vehicle_id);
CREATE INDEX idx_service_history_service_date ON service_history(service_date);

-- Ersatzteile
CREATE TABLE IF NOT EXISTS parts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  part_number VARCHAR(100) NOT NULL,
  name VARCHAR(255) NOT NULL,
  brand VARCHAR(100) NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_quantity INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_parts_part_number UNIQUE (part_number)
) ENGINE=InnoDB;

CREATE INDEX idx_parts_name ON parts(name);

-- Ersatzteil-Bestellungen
CREATE TABLE IF NOT EXISTS part_orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NULL,
  status ENUM('neu', 'in_bearbeitung', 'versendet', 'abgeschlossen', 'storniert') NOT NULL DEFAULT 'neu',
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_part_orders_customer
    FOREIGN KEY (customer_id) REFERENCES customers(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_part_orders_customer_id ON part_orders(customer_id);
CREATE INDEX idx_part_orders_status ON part_orders(status);

CREATE TABLE IF NOT EXISTS part_order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  part_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_part_order_items_order
    FOREIGN KEY (order_id) REFERENCES part_orders(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_part_order_items_part
    FOREIGN KEY (part_id) REFERENCES parts(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_part_order_items_order_id ON part_order_items(order_id);
CREATE INDEX idx_part_order_items_part_id ON part_order_items(part_id);

-- Optional: Session-Profil fuer serverseitige Speicherung (falls nicht nur PHP-Session verwendet wird)
CREATE TABLE IF NOT EXISTS session_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(128) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  license_plate VARCHAR(20) NOT NULL,
  make VARCHAR(100) NOT NULL,
  model VARCHAR(100) NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  mileage INT UNSIGNED NOT NULL,
  engine VARCHAR(100) NULL,
  color VARCHAR(50) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_session_profiles_session_id UNIQUE (session_id)
) ENGINE=InnoDB;

-- Beispiel-Daten fuer Historie aus dem Dashboard
INSERT INTO customers (first_name, last_name, email, phone)
VALUES ('Max', 'Mustermann', 'max@example.com', '+43 660 000000')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

INSERT INTO vehicles (customer_id, license_plate, make, model, year, mileage, engine, color, vin)
SELECT c.id, 'L-123AB', 'Volkswagen', 'Golf', 2019, 87420, '1.6 TDI', 'Silber', 'WVWZZZ1KZ9W000001'
FROM customers c
WHERE c.email = 'max@example.com'
ON DUPLICATE KEY UPDATE mileage = VALUES(mileage), updated_at = CURRENT_TIMESTAMP;

INSERT INTO service_history (vehicle_id, service_date, service_name, mileage, cost, mechanic, notes)
SELECT v.id, '2026-03-12', 'Oelwechsel inkl. Filterpaket', 87420, 89.00, 'Karl Hofbauer', 'Oelfilter erneuert und Service zurueckgesetzt.'
FROM vehicles v
WHERE v.license_plate = 'L-123AB';
