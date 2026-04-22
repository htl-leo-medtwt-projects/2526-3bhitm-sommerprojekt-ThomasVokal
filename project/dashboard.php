<?php
session_start();

$profile = $_SESSION['dashboard_profile'] ?? null;
if (!is_array($profile)) {
  $profile = null;
}
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

      <a href="tel:+43732123456" class="btn btn-primary btn-sm nav-cta">📞 Jetzt anrufen</a>
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
        <div style="display:flex; flex-direction:column; align-items:flex-end; gap: var(--space-3); flex-shrink:0;">
          <span class="badge badge-success" style="font-size: var(--font-size-xs);">● Fahrzeug aktuell in der Werkstatt</span>
          <span style="color: rgba(255,255,255,.6); font-size: var(--font-size-xs);">Mitglied seit März 2021</span>
        </div>
      </div>

      <!-- Dashboard Grid -->
      <div class="dashboard-layout" style="margin-top: var(--space-6);">

        <!-- MEINE GARAGE -->
        <div>
          <div class="eyebrow" style="margin-bottom: var(--space-4);">Meine Garage</div>
          <div class="vehicle-card" id="vehicleCard" role="region" aria-label="Fahrzeug-Übersicht">
            <!-- Via JS -->
          </div>
        </div>

        <!-- AKTUELLER TERMIN -->
        <div>
          <div class="eyebrow" style="margin-bottom: var(--space-4);">Aktueller Termin</div>
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
          <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap: var(--space-4); margin-bottom: var(--space-6);">
            <div>
              <div class="eyebrow">USP – Ihr persönliches Logbuch</div>
              <h2 style="font-size: var(--font-size-2xl);">Digitaler Werkstattpass</h2>
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
          <div class="grid-4" id="historyStats" style="margin-bottom: var(--space-8);">
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
          <p style="color: var(--color-text-muted); margin-top: var(--space-2); font-size: var(--font-size-sm);">
            Bitte geben Sie Ihre Fahrzeugdaten ein. Die Daten werden in der PHP-Session gespeichert.
          </p>
        </div>
        <button class="modal-close" id="vehicleProfileCloseBtn" aria-label="Dialog schließen">✕</button>
      </div>

      <form class="modal-body" id="vehicleProfileForm">
        <div class="grid-2" style="gap: var(--space-4);">
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

        <div style="margin-top: var(--space-6); display:flex; justify-content:flex-end; gap: var(--space-3); flex-wrap:wrap;">
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
    window.PHP_SESSION_PROFILE = <?php echo json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  </script>
  <script src="js/data.js"></script>
  <script src="js/app.js"></script>
  <script src="js/dashboard.js"></script>
</body>
</html>
