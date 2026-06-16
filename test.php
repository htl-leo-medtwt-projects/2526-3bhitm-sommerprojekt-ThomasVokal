<?php
// ============================================================
// GANZ EINFACH: Fahrzeug-Daten verwalten
// ============================================================

require_once 'auth.php';

// 1. FAHRZEUG LADEN (für Dashboard)
function loadVehicle($customerId) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare(
        "SELECT make, model, year, mileage, license_plate 
         FROM vehicles 
         WHERE account_id = ? 
         ORDER BY updated_at DESC 
         LIMIT 1"
    );
    $stmt->execute([$customerId]);
    
    return $stmt->fetch();  // Gibt null zurück, wenn kein Fahrzeug
}

// 2. FAHRZEUG SPEICHERN (aus Formular)
function saveVehicle($customerId, $data) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare(
        "INSERT INTO vehicles (account_id, make, model, year, mileage, license_plate)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
         make = VALUES(make), 
         model = VALUES(model),
         year = VALUES(year),
         mileage = VALUES(mileage)"
    );
    
    return $stmt->execute([
        $customerId,
        $data['make'],
        $data['model'],
        $data['year'],
        $data['mileage'],
        $data['licensePlate']
    ]);
}

// ============================================================
// VERWENDUNG IM DASHBOARD
// ============================================================

// Prüfen: Ist User eingeloggt?
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$customerId = getCurrentUserId();
$vehicle = loadVehicle($customerId);  // Fahrzeug laden

// Formular verarbeiten
if ($_POST) {
    saveVehicle($customerId, $_POST);
    $vehicle = loadVehicle($customerId);  // Neu laden
}
?>

<!-- HTML: Fahrzeug anzeigen -->
<div class="vehicle-card">
    <?php if ($vehicle): ?>
        <h3><?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?></h3>
        <p>Baujahr: <?php echo $vehicle['year']; ?></p>
        <p>Kennzeichen: <?php echo $vehicle['license_plate']; ?></p>
        <p>Kilometer: <?php echo number_format($vehicle['mileage'], 0, ',', '.'); ?> km</p>
    <?php else: ?>
        <p>Noch kein Fahrzeug hinterlegt.</p>
        <button>Fahrzeug hinzufügen</button>
    <?php endif; ?>
</div>