<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Prüfe, ob Benutzer angemeldet ist
if (!isLoggedIn()) {
  header('Location: login.php');
  exit;
}

$customerId = getCurrentUserId();
$customerName = getCurrentUserName();

// Lade Fahrzeugdaten des Benutzers
function loadUserVehicle(int $customerId): ?array {
  try {
    $pdo = getDbConnection();
    $statement = $pdo->prepare(
      'SELECT id, license_plate, make, model, year, mileage, engine, color, vin
       FROM vehicles
       WHERE account_id = :account_id
       ORDER BY updated_at DESC
       LIMIT 1'
    );
    $statement->execute(['account_id' => $customerId]);

    $row = $statement->fetch();
    if (!is_array($row)) {
      return null;
    }

    return [
      'id' => (int)$row['id'],
      'licensePlate' => (string)$row['license_plate'],
      'make' => (string)$row['make'],
      'model' => (string)$row['model'],
      'year' => (int)$row['year'],
      'mileage' => (int)$row['mileage'],
      'engine' => (string)($row['engine'] ?? ''),
      'color' => (string)($row['color'] ?? ''),
      'vin' => (string)($row['vin'] ?? ''),
    ];
  } catch (Throwable $exception) {
    return null;
  }
}

function loadUserServiceHistory(int $vehicleId): array {
  try {
    $pdo = getDbConnection();
    $statement = $pdo->prepare(
      'SELECT id, service_date, service_name, mileage, cost, mechanic, notes
       FROM service_history
       WHERE vehicle_id = :vehicle_id
       ORDER BY service_date DESC, id DESC'
    );
    $statement->execute(['vehicle_id' => $vehicleId]);

    $history = [];
    foreach ($statement->fetchAll() as $row) {
      $serviceDate = new DateTimeImmutable((string)$row['service_date']);
      $serviceName = (string)$row['service_name'];
      $cost = (float)$row['cost'];
      $serviceText = mb_strtolower($serviceName);
      $dotColor = 'primary';

      if ($cost <= 0) {
        $dotColor = 'success';
      } elseif (str_contains($serviceText, 'brems')) {
        $dotColor = 'warning';
      }

      $history[] = [
        'id' => (int)$row['id'],
        'date' => $serviceDate->format('d.m.Y'),
        'service' => $serviceName,
        'mileage' => (int)$row['mileage'],
        'cost' => $cost,
        'mechanic' => (string)($row['mechanic'] ?? 'Unbekannt'),
        'notes' => (string)($row['notes'] ?? ''),
        'dotColor' => $dotColor,
      ];
    }

    return $history;
  } catch (Throwable $exception) {
    return [];
  }
}

function loadUserAppointment(int $vehicleId): ?array {
  try {
    $pdo = getDbConnection();
    $statement = $pdo->prepare(
      'SELECT appointment_datetime, status, note
       FROM appointments
       WHERE vehicle_id = :vehicle_id
       ORDER BY appointment_datetime ASC, id ASC
       LIMIT 1'
    );
    $statement->execute(['vehicle_id' => $vehicleId]);

    $row = $statement->fetch();
    if (!is_array($row)) {
      return null;
    }

    $appointmentDate = new DateTimeImmutable((string)$row['appointment_datetime']);
    $status = (string)$row['status'];
    $statusLabels = [
      'angefragt' => 'Angefragt',
      'bestaetigt' => 'Bestätigt',
      'in_arbeit' => 'In Arbeit',
      'abgeschlossen' => 'Abgeschlossen',
      'storniert' => 'Storniert',
    ];

    return [
      'title' => 'Nächster Werkstatttermin',
      'date' => $appointmentDate->format('d.m.Y'),
      'time' => $appointmentDate->format('H:i') . ' Uhr',
      'status' => $statusLabels[$status] ?? ucfirst($status),
      'note' => (string)($row['note'] ?? ''),
    ];
  } catch (Throwable $exception) {
    return null;
  }
}

$vehicle = loadUserVehicle($customerId);
$serviceHistory = is_array($vehicle) ? loadUserServiceHistory((int)$vehicle['id']) : [];
$appointment = is_array($vehicle) ? loadUserAppointment((int)$vehicle['id']) : null;
$needsVehicleSetup = !empty($_SESSION['needs_vehicle_setup']) || !is_array($vehicle);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="CarFixFast Kunden-Dashboard in Leonding – Meine Garage, Termin-Status und Werkstattpass." />
  <title>Mein Bereich – CarFixFast</title>
  <link rel="stylesheet" href="css/styles.css" />
</head>
<body>

  <!-- NAV -->
  <nav class="nav" role="navigation" aria-label="Hauptnavigation">
    <div class="container nav-inner">
      <a href="index.html" class="nav-logo" aria-label="CarFixFast Startseite">
        <img src="./assets/img/logo.png" alt="logo">
      </a>

      <!-- Desktop Navigation -->
      <div class="nav-links" id="navLinks">
        <a href="index.html" class="nav-link active" aria-current="page"><span>Startseite</span></a>
        <a href="leistungen.html" class="nav-link"><span>Leistungen</span></a>
        <a href="ersatzteile.html" class="nav-link"><span>Ersatzteile</span></a>
        <a href="dashboard.php" class="nav-link"><span>Dashboard</span></a>
        <a href="logout.php" class="btn btn-outline btn-sm nav-cta" style="border-color: var(--color-danger); color: var(--color-danger);">Abmelden</a>
        <a href="leistungen.html" class="btn btn-primary btn-sm nav-cta">Termin buchen</a>
      </div>

      <!-- Burger Menu Button -->
      <button class="nav-hamburger" id="burgerMenu" aria-label="Menü öffnen" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div class="nav-mobile" id="mobileMenu" role="navigation" aria-label="Mobile Navigation">
      <a href="index.html" class="nav-link active" aria-current="page"><span>Startseite</span></a>
      <a href="leistungen.html" class="nav-link"><span>Leistungen</span></a>
      <a href="ersatzteile.html" class="nav-link"><span>Ersatzteile</span></a>
      <a href="dashboard.php" class="nav-link"><span>Dashboard</span></a>
      <a href="logout.php" class="btn btn-outline btn-block" style="border-color: var(--color-danger); color: var(--color-danger); margin-bottom: var(--space-4);">🚪 Abmelden</a>
      <a href="leistungen.html" class="btn btn-primary btn-block nav-cta">Termin buchen</a>
    </div>
  </nav>

  <!-- PAGE HERO (Welcome Banner im Stil des page-hero) -->
  <section class="page-hero" aria-labelledby="dashboardHeading">
    <div class="container">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.html">Startseite</a>
        <span class="breadcrumb-sep" aria-hidden="true">›</span>
        <span aria-current="page">Mein Bereich</span>
      </nav>
      <h1 id="dashboardHeading">Mein Bereich</h1>
      <p>Ihr persönlicher Überblick – Fahrzeug, aktueller Termin und Werkstattpass.</p>
    </div>
  </section>

  <!-- DASHBOARD BODY -->
  <main class="section section-sm" aria-label="Dashboard">
    <div class="container">

      <!-- Welcome Banner -->
      <div class="welcome-banner" id="welcomeBanner" role="banner">
        <div class="welcome-text">
          <h2 id="welcomeTitle">Hallo, <?php echo htmlspecialchars($customerName); ?> 👋</h2>
          <p id="welcomeSubtitle">Hier finden Sie Termine, Services und den Werkstattpass auf einen Blick.</p>
        </div>
        <div class="dashboard-summary-actions">
          <span class="badge badge-success dashboard-summary-badge" id="welcomeStatusBadge">● Fahrzeugdaten geladen</span>
          <span class="dashboard-summary-note" id="welcomeSummaryNote">Werkstattpass und Servicehistorie sind bereit.</span>
        </div>
      </div>

      <!-- Dashboard Grid -->
      <div class="dashboard-layout">

        <!-- MEINE GARAGE -->
        <div>
          <div class="eyebrow dashboard-section-eyebrow">Meine Garage</div>
          <div class="vehicle-card" id="vehicleCard" role="region" aria-label="Fahrzeug-Übersicht">
            <!-- Via JS -->
          </div>
        </div>

        <!-- AKTUELLER TERMIN -->
        <div>
          <div class="eyebrow dashboard-section-eyebrow">Aktueller Termin</div>
          <div class="appointment-card" id="appointmentCard" role="region" aria-label="Termin-Status">
            <!-- Via JS -->
          </div>
        </div>

        <!-- ÖLWECHSEL ALERT (full width) -->
        <div class="dashboard-full">
          <div class="oil-alert animate-fade-in-up" id="oilAlert" role="alert" aria-label="Ölwechsel-Empfehlung">
            <!-- Via JS -->
          </div>
        </div>

        <!-- DIGITALER WERKSTATTPASS (full width) -->
        <div class="dashboard-full">
          <div class="dashboard-workshop-header">
            <div>
              <div class="eyebrow">Ihr persönliches Logbuch</div>
              <h2 class="dashboard-workshop-title">Digitaler Werkstattpass</h2>
            </div>
            <div class="dashboard-workshop-actions">
              <button
                class="btn btn-primary"
                id="addServiceEntryBtn"
                aria-label="Serviceeintrag hinzufügen"
              >
                ➕ Serviceeintrag hinzufügen
              </button>
              <button
                class="btn btn-outline"
                id="editVehicleBtn"
                aria-label="Fahrzeugdaten bearbeiten"
              >
                ✏️ Fahrzeugdaten bearbeiten
              </button>
            </div>
          </div>

          <!-- Stats Row -->
          <div class="grid-4 dashboard-stats" id="historyStats">
            <!-- Via JS -->
          </div>

          <!-- Timeline -->
          <div class="timeline" id="historyTimeline" role="list" aria-label="Reparatur-Historie">
            <!-- Via JS -->
          </div>

        </div>

      </div>
    </div>
  </main>

  <div class="modal-overlay" id="vehicleProfileModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="vehicleProfileTitle">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="vehicleProfileTitle">Fahrzeugdaten erfassen</h2>
          <p class="vehicle-profile-copy">Bitte geben Sie Ihre Fahrzeugdaten ein.</p>
        </div>
        <button class="modal-close" id="vehicleProfileCloseBtn" aria-label="Dialog schließen">✕</button>
      </div>

      <form class="modal-body" id="vehicleProfileForm">
        <div class="grid-2 vehicle-profile-grid">
          <div class="form-group">
            <label class="form-label" for="profileFirstName">Vorname</label>
            <input class="form-input" id="profileFirstName" name="firstName" required autocomplete="given-name" />
          </div>
          <div class="form-group">
            <label class="form-label" for="profileLicensePlate">Kennzeichen</label>
            <input class="form-input" id="profileLicensePlate" name="licensePlate" required autocomplete="off" />
          </div>
          <div class="form-group">
            <label class="form-label" for="profileMake">Marke</label>
            <input class="form-input" id="profileMake" name="make" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="profileModel">Modell</label>
            <input class="form-input" id="profileModel" name="model" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="profileYear">Baujahr</label>
            <input class="form-input" id="profileYear" name="year" type="number" min="1980" max="2035" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="profileMileage">Kilometerstand</label>
            <input class="form-input" id="profileMileage" name="mileage" type="number" min="0" step="1" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="profileEngine">Motor (optional)</label>
            <input class="form-input" id="profileEngine" name="engine" placeholder="z. B. 1.6 TDI" />
          </div>
          <div class="form-group">
            <label class="form-label" for="profileColor">Farbe (optional)</label>
            <input class="form-input" id="profileColor" name="color" placeholder="z. B. Silber" />
          </div>
          <div class="form-group">
            <label class="form-label" for="profileVin">Fahrgestellnummer (VIN, optional)</label>
            <input class="form-input" id="profileVin" name="vin" placeholder="z. B. WVWZZZ..." autocomplete="off" />
          </div>
        </div>

        <div class="vehicle-profile-actions">
          <button type="button" class="btn btn-outline" id="vehicleProfileCancelBtn">Abbrechen</button>
          <button type="submit" class="btn btn-primary">Daten speichern</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-overlay" id="serviceEntryModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="serviceEntryTitle">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="serviceEntryTitle">Serviceeintrag hinzufügen</h2>
          <p class="vehicle-profile-copy">Neuen Werkstattpass-Eintrag erfassen und direkt speichern.</p>
        </div>
        <button class="modal-close" id="serviceEntryCloseBtn" aria-label="Dialog schließen">✕</button>
      </div>

      <form class="modal-body" id="serviceEntryForm">
        <div class="grid-2 vehicle-profile-grid">
          <div class="form-group">
            <label class="form-label" for="serviceDate">Datum *</label>
            <input class="form-input" id="serviceDate" name="serviceDate" type="date" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="serviceMileage">Kilometerstand *</label>
            <input class="form-input" id="serviceMileage" name="mileage" type="number" min="0" step="1" required />
          </div>
          <div class="form-group">
            <label class="form-label" for="serviceName">Servicebezeichnung *</label>
            <input class="form-input" id="serviceName" name="serviceName" required placeholder="z. B. Ölwechsel inkl. Filter" />
          </div>
          <div class="form-group">
            <label class="form-label" for="serviceCost">Kosten in € *</label>
            <input class="form-input" id="serviceCost" name="cost" type="number" min="0" step="0.01" required placeholder="z. B. 89.00" />
          </div>
          <div class="form-group">
            <label class="form-label" for="serviceMechanic">Mechaniker (optional)</label>
            <input class="form-input" id="serviceMechanic" name="mechanic" placeholder="z. B. Karl Hofbauer" />
          </div>
          <div class="form-group service-entry-notes-group">
            <label class="form-label" for="serviceNotes">Notiz (optional)</label>
            <textarea class="form-input service-entry-notes" id="serviceNotes" name="notes" rows="4" placeholder="Zusätzliche Infos zum Serviceeintrag"></textarea>
          </div>
        </div>

        <div class="vehicle-profile-actions">
          <button type="button" class="btn btn-outline" id="serviceEntryCancelBtn">Abbrechen</button>
          <button type="submit" class="btn btn-primary">Eintrag speichern</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bestätigungsmodal zum Löschen -->
<div class="modal-overlay" id="deleteConfirmModal" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="modal modal-confirm">
    <div class="modal-header">
      <h3 class="modal-title">⚠️ Eintrag löschen</h3>
      <button class="modal-close" id="deleteConfirmCloseBtn" aria-label="Schließen">✕</button>
    </div>
    <div class="modal-body">
      <p id="deleteConfirmMessage">Möchten Sie diesen Eintrag wirklich löschen?</p>
      <p class="text-muted" style="font-size: var(--font-size-sm); margin-top: var(--space-2);">Diese Aktion kann nicht rückgängig gemacht werden.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="deleteConfirmCancelBtn">Abbrechen</button>
      <button class="btn btn-danger" id="deleteConfirmBtn">🗑️ Löschen</button>
    </div>
  </div>
</div>

  <!-- FOOTER -->
  <div id="footerMount"></div>

  <!-- SCRIPTS -->
  <script>
    <?php
      $initialProfile = is_array($vehicle) ? $vehicle : [];
      $initialProfile['firstName'] = $customerName;
    ?>
    window.PHP_SESSION_PROFILE = <?php echo json_encode($initialProfile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.DASHBOARD_HISTORY = <?php echo json_encode($serviceHistory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.DASHBOARD_APPOINTMENT = <?php echo json_encode($appointment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.NEEDS_VEHICLE_SETUP = <?php echo json_encode($needsVehicleSetup); ?>;
    window.CUSTOMER_ID = <?php echo json_encode($customerId); ?>;
  </script>
  <script src="js/data.js"></script>
  <script src="js/app.js"></script>
  <script src="js/dashboard.js"></script>
</body>
</html>