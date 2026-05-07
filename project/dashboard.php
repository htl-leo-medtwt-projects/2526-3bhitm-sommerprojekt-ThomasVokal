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
      'SELECT id, license_plate, make, model, year, mileage, engine, color
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
    ];
  } catch (Throwable $exception) {
    return null;
  }
}

$vehicle = loadUserVehicle($customerId);
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
        <div class="logo-icon" aria-hidden="true">🔧</div>
        <span>Car<span class="logo-fast">Fix</span>Fast</span>
      </a>

      <div class="nav-links">
        <a href="index.html" class="nav-link"><span>Startseite</span></a>
        <a href="leistungen.html" class="nav-link"><span>Leistungen</span></a>
        <a href="ersatzteile.html" class="nav-link"><span>Ersatzteile</span></a>
        <a href="dashboard.php" class="nav-link active" aria-current="page"><span>Mein Bereich</span></a>
      </div>

      <div style="display: flex; gap: 10px; align-items: center;">
        <span style="font-size: 14px; color: #666;">👤 <?php echo htmlspecialchars($customerName); ?></span>
        <a href="logout.php" class="btn btn-outline btn-sm" style="margin: 0;">Abmelden</a>
      </div>
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
      <div class="welcome-banner scroll-reveal" id="welcomeBanner" role="banner">
        <div class="welcome-text">
          <h2 id="welcomeTitle">Hallo, Max Mustermann 👋</h2>
          <p id="welcomeSubtitle">Ihr Fahrzeug wird aktuell bei uns umsorgt. Nächster Ölwechsel in ca. 2.580 km.</p>
        </div>
        <div class="dashboard-summary-actions">
          <span class="badge badge-success dashboard-summary-badge">● Fahrzeug aktuell in der Werkstatt</span>
          <span class="dashboard-summary-note">Mitglied seit März 2021</span>
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
              <div class="eyebrow">USP – Ihr persönliches Logbuch</div>
              <h2 class="dashboard-workshop-title">Digitaler Werkstattpass</h2>
            </div>
            <button
              class="btn btn-outline"
              id="editVehicleBtn"
              aria-label="Fahrzeugdaten bearbeiten"
            >
              ✏️ Fahrzeugdaten bearbeiten
            </button>
            <button
              class="btn btn-outline"
              id="exportPassBtn"
              aria-label="Werkstattpass als PDF exportieren"
            >
              📄 Als PDF exportieren
            </button>
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
          <p class="vehicle-profile-copy">
            Bitte geben Sie Ihre Fahrzeugdaten ein.
          </p>
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
        </div>

        <div class="vehicle-profile-actions">
          <button type="button" class="btn btn-outline" id="vehicleProfileCancelBtn">Abbrechen</button>
          <button type="submit" class="btn btn-primary">Daten speichern</button>
        </div>
      </form>
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
    window.CUSTOMER_ID = <?php echo json_encode($customerId); ?>;
  </script>
  <script src="js/data.js"></script>
  <script src="js/app.js"></script>
  <script src="js/dashboard.js"></script>
</body>
</html>
