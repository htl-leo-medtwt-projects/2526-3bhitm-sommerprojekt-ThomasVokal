<?php
require_once __DIR__ . '/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  
  if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($email && $password) {
      $result = loginUser($email, $password);
      if ($result['ok']) {
        header('Location: dashboard.php');
        exit;
      } else {
        $error = $result['error'];
      }
    } else {
      $error = 'Bitte Email und Passwort eingeben.';
    }
  } 
  elseif ($action === 'register') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $passwordConfirm = trim($_POST['passwordConfirm'] ?? '');
    
    if (!$firstName) {
      $error = 'Vorname erforderlich.';
    } elseif (!$email || strpos($email, '@') === false) {
      $error = 'Gültige Email erforderlich.';
    } elseif ($password !== $passwordConfirm) {
      $error = 'Passwörter stimmen nicht überein.';
    } elseif (strlen($password) < 4) {
      $error = 'Passwort muss mindestens 4 Zeichen lang sein.';
    } else {
      $result = registerUser($firstName, $lastName, $email, $password);
      if ($result['ok']) {
        header('Location: dashboard.php');
        exit;
      } else {
        $error = $result['error'];
      }
    }
  }
}

// Wenn bereits angemeldet, redirect zum Dashboard
if (isset($_SESSION['customer_id'])) {
  header('Location: dashboard.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Anmelden – CarFixFast</title>
  <link rel="stylesheet" href="css/styles.css" />
</head>
<body>
  <!-- MINIMAL NAV -->
  <nav class="nav" role="navigation" aria-label="Hauptnavigation">
    <div class="container nav-inner">
      <a href="index.html" class="nav-logo" aria-label="CarFixFast Startseite">
        <div class="logo-icon" aria-hidden="true">🔧</div>
        <span>Car<span class="logo-fast">Fix</span>Fast</span>
      </a>
      <div></div>
    </div>
  </nav>

  <!-- AUTH PAGE -->
  <section class="section section-lg" style="min-height: calc(100vh - 68px); display: flex; align-items: center; justify-content: center;">
    <div class="container" style="max-width: 500px;">
      <div class="card" style="padding: var(--space-12);">
        
        <!-- TABS -->
        <div style="display: flex; gap: 0; margin-bottom: var(--space-12); border-bottom: 1px solid var(--color-border);">
          <button 
            class="auth-tab active" 
            onclick="switchTab('login')"
            style="flex: 1; padding: var(--space-4); text-align: center; background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: var(--font-size-base); transition: var(--transition-base); border-bottom: 2px solid transparent; margin-bottom: -1px;"
            id="tab-login"
          >
            Anmelden
          </button>
          <button 
            class="auth-tab" 
            onclick="switchTab('register')"
            style="flex: 1; padding: var(--space-4); text-align: center; background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: var(--font-size-base); transition: var(--transition-base); border-bottom: 2px solid transparent; margin-bottom: -1px;"
            id="tab-register"
          >
            Registrieren
          </button>
        </div>

        <!-- ERROR MESSAGE -->
        <?php if ($error): ?>
          <div style="background: var(--color-danger-bg); border: 1px solid var(--color-danger); color: var(--color-danger); padding: var(--space-4) var(--space-6); border-radius: var(--radius-lg); margin-bottom: var(--space-8); font-size: var(--font-size-sm);">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <form class="auth-form active" id="login" method="POST">
          <input type="hidden" name="action" value="login" />

          <div style="margin-bottom: var(--space-6);">
            <label class="form-label" for="loginEmail">Email-Adresse</label>
            <input
              class="form-input"
              id="loginEmail"
              name="email"
              type="email"
              required
              autocomplete="email"
              placeholder="deine@email.at"
            />
          </div>

          <div style="margin-bottom: var(--space-8);">
            <label class="form-label" for="loginPassword">Passwort</label>
            <input
              class="form-input"
              id="loginPassword"
              name="password"
              type="password"
              required
              autocomplete="current-password"
              placeholder="Dein Passwort"
            />
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%;">Anmelden</button>
        </form>

        <!-- REGISTER FORM -->
        <form class="auth-form" id="register" method="POST">
          <input type="hidden" name="action" value="register" />

          <div style="margin-bottom: var(--space-6);">
            <label class="form-label" for="registerFirstName">Vorname *</label>
            <input
              class="form-input"
              id="registerFirstName"
              name="firstName"
              type="text"
              required
              autocomplete="given-name"
              placeholder="Max"
            />
          </div>

          <div style="margin-bottom: var(--space-6);">
            <label class="form-label" for="registerLastName">Nachname</label>
            <input
              class="form-input"
              id="registerLastName"
              name="lastName"
              type="text"
              autocomplete="family-name"
              placeholder="Mustermann"
            />
          </div>

          <div style="margin-bottom: var(--space-6);">
            <label class="form-label" for="registerEmail">Email-Adresse *</label>
            <input
              class="form-input"
              id="registerEmail"
              name="email"
              type="email"
              required
              autocomplete="email"
              placeholder="deine@email.at"
            />
          </div>

          <div style="margin-bottom: var(--space-6);">
            <label class="form-label" for="registerPassword">Passwort *</label>
            <input
              class="form-input"
              id="registerPassword"
              name="password"
              type="password"
              required
              autocomplete="new-password"
              minlength="4"
              placeholder="Mindestens 4 Zeichen"
            />
          </div>

          <div style="margin-bottom: var(--space-8);">
            <label class="form-label" for="registerPasswordConfirm">Passwort wiederholen *</label>
            <input
              class="form-input"
              id="registerPasswordConfirm"
              name="passwordConfirm"
              type="password"
              required
              autocomplete="new-password"
              minlength="4"
              placeholder="Passwort bestätigen"
            />
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%;">Registrieren</button>
        </form>

      </div>

      <!-- BACK LINK -->
      <div style="text-align: center; margin-top: var(--space-8);">
        <a href="index.html" style="color: var(--color-text-muted); text-decoration: none; font-size: var(--font-size-sm); transition: var(--transition-base);" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--color-text-muted)'">
          ← Zurück zur Startseite
        </a>
      </div>

    </div>
  </section>

  <script>
    function switchTab(tab) {
      document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
      document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));

      document.getElementById(tab).classList.add('active');
      document.getElementById('tab-' + tab).classList.add('active');
      
      // Style active tab
      document.getElementById('tab-' + tab).style.color = 'var(--color-primary)';
      document.getElementById('tab-' + tab).style.borderBottomColor = 'var(--color-primary)';
      
      // Style inactive tab
      document.querySelectorAll('.auth-tab').forEach(t => {
        if (!t.classList.contains('active')) {
          t.style.color = 'var(--color-text-muted)';
          t.style.borderBottomColor = 'transparent';
        }
      });
    }
  </script>

  <style>
    .auth-form {
      display: none;
    }

    .auth-form.active {
      display: block;
      animation: fadeIn var(--transition-base);
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
  </style>
</body>
</html>
