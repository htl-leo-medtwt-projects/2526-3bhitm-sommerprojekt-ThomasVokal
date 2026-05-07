-- ============================================================
-- CarFixFast MySQL Schema - Optimiert für Accounts & Fahrzeuge
-- ============================================================

CREATE DATABASE IF NOT EXISTS carfixfast
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE carfixfast;

-- ============================================================
-- 1. ACCOUNTS (Benutzer mit Passwort)
-- ============================================================
CREATE TABLE IF NOT EXISTS accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100),
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_email (email),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- 2. FAHRZEUGE (pro Account eins)
-- ============================================================
CREATE TABLE IF NOT EXISTS vehicles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_id INT UNSIGNED NOT NULL,
  
  license_plate VARCHAR(20) NOT NULL,
  make VARCHAR(100) NOT NULL,
  model VARCHAR(100) NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  mileage INT UNSIGNED NOT NULL DEFAULT 0,
  engine VARCHAR(100),
  color VARCHAR(50),
  vin VARCHAR(50),
  
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_vehicles_account
    FOREIGN KEY (account_id) REFERENCES accounts(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  CONSTRAINT uq_vehicles_license_plate UNIQUE (license_plate),
  CONSTRAINT uq_vehicles_vin UNIQUE (vin),
  
  INDEX idx_account_id (account_id),
  INDEX idx_make_model (make, model)
) ENGINE=InnoDB;

-- ============================================================
-- 3. SERVICE-HISTORIE (Werkstattpass)
-- ============================================================
CREATE TABLE IF NOT EXISTS service_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT UNSIGNED NOT NULL,
  
  service_date DATE NOT NULL,
  service_name VARCHAR(255) NOT NULL,
  mileage INT UNSIGNED NOT NULL,
  cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  mechanic VARCHAR(150),
  notes TEXT,
  
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_service_history_vehicle
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  INDEX idx_vehicle_id (vehicle_id),
  INDEX idx_service_date (service_date)
) ENGINE=InnoDB;

-- ============================================================
-- 4. TERMINE
-- ============================================================
CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT UNSIGNED NOT NULL,
  
  appointment_datetime DATETIME NOT NULL,
  status ENUM('angefragt', 'bestaetigt', 'in_arbeit', 'abgeschlossen', 'storniert') NOT NULL DEFAULT 'angefragt',
  note TEXT,
  
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_appointments_vehicle
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  INDEX idx_vehicle_id (vehicle_id),
  INDEX idx_status_date (status, appointment_datetime)
) ENGINE=InnoDB;

-- ============================================================
-- 5. DIENSTLEISTUNGEN / SERVICES
-- ============================================================
CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  
  name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  duration_minutes INT UNSIGNED DEFAULT 60,
  
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_category (category)
) ENGINE=InnoDB;

-- ============================================================
-- 6. ERSATZTEILE
-- ============================================================
CREATE TABLE IF NOT EXISTS parts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  
  part_number VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  brand VARCHAR(100),
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_quantity INT NOT NULL DEFAULT 0,
  
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_name (name)
) ENGINE=InnoDB;

-- ============================================================
-- 7. BESTELLUNGEN
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_id INT UNSIGNED NOT NULL,
  
  status ENUM('neu', 'in_bearbeitung', 'versendet', 'abgeschlossen', 'storniert') NOT NULL DEFAULT 'neu',
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_orders_account
    FOREIGN KEY (account_id) REFERENCES accounts(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  INDEX idx_account_id (account_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- 8. BESTELLUNGS-POSITIONEN
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  part_id INT UNSIGNED NOT NULL,
  
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  
  CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  
  CONSTRAINT fk_order_items_part
    FOREIGN KEY (part_id) REFERENCES parts(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  
  INDEX idx_order_id (order_id),
  INDEX idx_part_id (part_id)
) ENGINE=InnoDB;

-- ============================================================
-- DEMO-DATEN
-- ============================================================

-- Demo Account (Email: max@example.at, Passwort: demo1234)
-- Passwort-Hash: $2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DusJOM
INSERT INTO accounts (first_name, last_name, email, password_hash, phone)
VALUES ('Max', 'Mustermann', 'max@example.at', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DusJOM', '+43 660 000 0000')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- Demo Fahrzeug
INSERT INTO vehicles (account_id, license_plate, make, model, year, mileage, engine, color, vin)
SELECT id, 'L-123AB', 'Volkswagen', 'Golf', 2019, 87420, '1.6 TDI', 'Silber', 'WVWZZZ1KZ9W000001'
FROM accounts WHERE email = 'max@example.at'
ON DUPLICATE KEY UPDATE mileage = 87420;

-- Demo Service-Historie
INSERT INTO service_history (vehicle_id, service_date, service_name, mileage, cost, mechanic, notes)
SELECT v.id, '2026-03-12', 'Ölwechsel inkl. Filterpaket', 87420, 89.00, 'Karl Hofbauer', 'Ölfilter erneuert und Service zurückgesetzt.'
FROM vehicles v WHERE v.license_plate = 'L-123AB'
ON DUPLICATE KEY UPDATE cost = 89.00;

INSERT INTO service_history (vehicle_id, service_date, service_name, mileage, cost, mechanic, notes)
SELECT v.id, '2026-01-28', 'Inspektion HU + AU', 85600, 0.00, 'Karl Hofbauer', 'HU und AU ohne Befund bestanden.'
FROM vehicles v WHERE v.license_plate = 'L-123AB'
ON DUPLICATE KEY UPDATE cost = 0.00;

INSERT INTO service_history (vehicle_id, service_date, service_name, mileage, cost, mechanic, notes)
SELECT v.id, '2025-11-15', 'Bremsbeläge vorne gewechselt', 81200, 250.00, 'Thomas Weber', 'Neue TEXTAR Bremsbeläge montiert.'
FROM vehicles v WHERE v.license_plate = 'L-123AB'
ON DUPLICATE KEY UPDATE cost = 250.00;

-- Demo Termin
INSERT INTO appointments (vehicle_id, appointment_datetime, status, note)
SELECT v.id, '2026-05-28 10:30:00', 'bestaetigt', 'Bitte Fahrzeugpapiere mitbringen. Der Termin ist bereits vorgemerkt.'
FROM vehicles v WHERE v.license_plate = 'L-123AB'
ON DUPLICATE KEY UPDATE status = 'bestaetigt';

-- ============================================================
-- DIENSTLEISTUNGEN (für Leistungen-Seite)
-- ============================================================
INSERT INTO services (name, category, description, price, duration_minutes)
VALUES 
  ('Ölwechsel', 'Wartung', 'Kompletter Ölwechsel mit Ölfilter und Ablassschraube', 89.00, 45),
  ('Inspektionen HU/AU', 'Prüfungen', 'Hauptuntersuchung und Abgasuntersuchung', 0.00, 120),
  ('Bremsbeläge vorne', 'Verschleißteile', 'Austausch der Bremsbeläge an der Vorderachse', 250.00, 90),
  ('Bremsbeläge hinten', 'Verschleißteile', 'Austausch der Bremsbeläge an der Hinterachse', 220.00, 80),
  ('Luftfilter wechseln', 'Wartung', 'Austausch des Motor-Luftfilters', 35.00, 20),
  ('Cabrio-Dach waschen', 'Reinigung', 'Spezialreinigung des Cabriolettverdecks', 65.00, 60),
  ('Inspektion (30.000 km)', 'Wartung', 'Regelmäßige Service-Inspektion', 199.00, 120),
  ('Inspektion (60.000 km)', 'Wartung', 'Große Service-Inspektion mit Kühlmittelwechsel', 399.00, 180),
  ('Bremsflüssigkeit wechseln', 'Wartung', 'Austausch der Bremsflüssigkeit', 145.00, 90),
  ('Zahnriemen wechseln', 'Wartung', 'Austausch des Zahnriemens', 850.00, 300),
  ('Reifenwechsel (4er Set)', 'Reifen', 'Montage und Auswuchtung von 4 Reifen', 120.00, 60),
  ('Stoßdämpfer vorne (Paar)', 'Fahrwerk', 'Austausch der vorderen Stoßdämpfer', 680.00, 180),
  ('Stoßdämpfer hinten (Paar)', 'Fahrwerk', 'Austausch der hinteren Stoßdämpfer', 620.00, 160)
ON DUPLICATE KEY UPDATE price = VALUES(price), duration_minutes = VALUES(duration_minutes);

-- ============================================================
-- ERSATZTEILE (für Ersatzteile-Shop)
-- ============================================================
INSERT INTO parts (part_number, name, brand, description, price, stock_quantity)
VALUES 
  ('FILTER-OIL-001', 'Ölfilter', 'MANN-FILTER', 'Hochwertiger Ölfilter für VW Golf 1.6', 12.50, 45),
  ('FILTER-AIR-001', 'Luftfilter', 'BOSCH', 'Luftfilter für VW Golf 1.6 TDI', 15.00, 30),
  ('BRAKE-PAD-001', 'Bremsbeläge vorne', 'TEXTAR', 'Bremsbeläge Premium für VW Golf', 89.99, 12),
  ('BRAKE-PAD-002', 'Bremsbeläge hinten', 'TEXTAR', 'Bremsbeläge hinten für VW Golf', 79.99, 8),
  ('SPARK-PLUG-001', 'Zündkerzen (4er Set)', 'BOSCH', 'Iridium Zündkerzen für VW Golf', 45.00, 22),
  ('BATTERY-001', 'Starterbatterie', 'VARTA', '12V 77Ah Starterbatterie', 199.99, 5),
  ('BULB-H7-001', 'Scheinwerferlampe H7', 'OSRAM', 'H7 Halogenlampe 55W', 8.50, 60),
  ('BULB-H1-001', 'Fernlicht H1', 'PHILIPS', 'H1 Halogenlampe 55W', 7.50, 55),
  ('RADIATOR-001', 'Kühlmittel', 'SHELL', 'Kühlmittel Konzentrat G12+', 19.99, 35),
  ('OIL-5W40-001', 'Motoröl 5W-40', 'CASTROL', 'CASTROL MAGNATEC 5W-40, 5L Kanister', 39.99, 28),
  ('BRAKE-FLUID-001', 'Bremsflüssigkeit DOT4', 'BOSCH', 'Bremsflüssigkeit DOT4, 1L Flasche', 12.50, 20),
  ('WIPER-BLADE-001', 'Scheibenwischer vorne', 'BOSCH', 'Scheibenwischer-Blatt für VW Golf', 22.00, 40),
  ('WIPER-BLADE-002', 'Scheibenwischer hinten', 'BOSCH', 'Scheibenwischer-Blatt hinten für Golf', 18.00, 35),
  ('SHOCK-ABSORBER-001', 'Stoßdämpfer vorne (Paar)', 'BILSTEIN', 'Bilstein B4 Stoßdämpfer vorne', 320.00, 6),
  ('SHOCK-ABSORBER-002', 'Stoßdämpfer hinten (Paar)', 'BILSTEIN', 'Bilstein B4 Stoßdämpfer hinten', 280.00, 8),
  ('TIRE-SUMMER-001', 'Sommerreifen 205/55R16', 'MICHELIN', 'Michelin Pilot Sport 3 205/55R16', 189.99, 10),
  ('TIRE-WINTER-001', 'Winterreifen 205/55R16', 'CONTINENTAL', 'Continental WinterContact TS 860 205/55R16', 159.99, 12),
  ('BELT-TIMING-001', 'Zahnriemen', 'CONTITECH', 'ContiTech Zahnriemen für VW Golf 1.6', 85.00, 4),
  ('BELT-SERPENTINE-001', 'Keilriemen', 'GATES', 'Gates Keilriemen für Nebenaggregate', 32.00, 18)
ON DUPLICATE KEY UPDATE price = VALUES(price), stock_quantity = VALUES(stock_quantity);

-- ============================================================
-- DEMO-BESTELLUNG (mit Items)
-- ============================================================
INSERT INTO orders (account_id, status, total_amount)
SELECT id, 'abgeschlossen', 127.49
FROM accounts WHERE email = 'max@example.at'
ON DUPLICATE KEY UPDATE total_amount = 127.49;

INSERT INTO order_items (order_id, part_id, quantity, unit_price)
SELECT o.id, p.id, 1, p.price
FROM orders o, parts p
WHERE o.account_id = (SELECT id FROM accounts WHERE email = 'max@example.at')
  AND p.part_number = 'FILTER-OIL-001'
  AND NOT EXISTS (
    SELECT 1 FROM order_items oi 
    WHERE oi.order_id = o.id AND oi.part_id = p.id
  )
LIMIT 1;

INSERT INTO order_items (order_id, part_id, quantity, unit_price)
SELECT o.id, p.id, 1, p.price
FROM orders o, parts p
WHERE o.account_id = (SELECT id FROM accounts WHERE email = 'max@example.at')
  AND p.part_number = 'FILTER-AIR-001'
  AND NOT EXISTS (
    SELECT 1 FROM order_items oi 
    WHERE oi.order_id = o.id AND oi.part_id = p.id
  )
LIMIT 1;
