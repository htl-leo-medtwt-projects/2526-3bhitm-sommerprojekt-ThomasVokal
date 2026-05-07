<?php
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== CarFixFast DB Connection Test ===\n\n";

// 1. Zeige die aktuellen Einstellungen
echo "1. Konfiguration (aus .env oder Defaults):\n";
echo "   DB_HOST: " . (getenv('DB_HOST') ?: 'db_server (Docker-Standard)') . "\n";
echo "   DB_PORT: " . (getenv('DB_PORT') ?: '3306') . "\n";
echo "   DB_NAME: " . (getenv('DB_NAME') ?: 'carfixfast') . "\n";
echo "   DB_USER: " . (getenv('DB_USER') ?: 'root') . "\n";
echo "   (Passwort: aus .env oder Defaults)\n";
echo "\n";

// 2. Versuche Verbindung
echo "2. Verbindung wird versucht...\n";
try {
  $pdo = getDbConnection();
  echo "   ✓ Verbindung erfolgreich!\n\n";
} catch (Throwable $e) {
  echo "   ✗ Verbindung fehlgeschlagen:\n";
  echo "   " . $e->getMessage() . "\n\n";
  exit(1);
}

// 3. Prüfe Tabelle
echo "3. Prüfe vehicles Tabelle...\n";
try {
  $statement = $pdo->prepare('SHOW TABLES LIKE "vehicles"');
  $statement->execute();
  $row = $statement->fetch();
  
  if ($row) {
    echo "   ✓ Tabelle existiert!\n\n";
  } else {
    echo "   ✗ Tabelle NOT gefunden!\n";
    echo "   Bitte importiere database/carfix_schema.sql in phpMyAdmin.\n\n";
    exit(1);
  }
} catch (Throwable $e) {
  echo "   ✗ Fehler beim Prüfen:\n";
  echo "   " . $e->getMessage() . "\n\n";
  exit(1);
}

// 4. Zeige jüngste Fahrzeuge und zugehörige Accounts
echo "4. Jüngste Fahrzeuge (mit Besitzer):\n";
try {
  $sql = 'SELECT v.license_plate, v.make, v.model, v.mileage, a.first_name, a.last_name, v.updated_at
          FROM vehicles v
          LEFT JOIN accounts a ON v.account_id = a.id
          ORDER BY v.updated_at DESC
          LIMIT 5';
  $statement = $pdo->prepare($sql);
  $statement->execute();
  $rows = $statement->fetchAll();
  
  if (empty($rows)) {
    echo "   (Noch keine Fahrzeuge angelegt)\n\n";
  } else {
    foreach ($rows as $row) {
      $owner = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: 'Unbekannt';
      echo "   - {$row['license_plate']} — {$row['make']} {$row['model']} ({$row['mileage']} km) — Besitzer: {$owner}\n";
    }
    echo "\n";
  }
} catch (Throwable $e) {
  echo "   ✗ Fehler beim Laden:\n";
  echo "   " . $e->getMessage() . "\n\n";
  exit(1);
}

echo "✓ Alles OK! Versuche jetzt im Dashboard Fahrzeugdaten zu speichern.\n";
echo "  Danach sollten neue Einträge hier unten auftauchen.\n";
?>
