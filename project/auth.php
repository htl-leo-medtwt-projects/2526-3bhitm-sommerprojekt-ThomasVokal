<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Registriert einen neuen Benutzer
 */
function registerUser(string $firstName, string $lastName, string $email, string $password): array {
  try {
    $pdo = getDbConnection();
    
    // Prüfe, ob Email bereits existiert
    $check = $pdo->prepare('SELECT id FROM accounts WHERE email = :email LIMIT 1');
    $check->execute(['email' => $email]);
    
    if ($check->fetch()) {
      return ['ok' => false, 'error' => 'Diese Email-Adresse ist bereits registriert.'];
    }
    
    // Hashe Passwort
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    
    // Erstelle neuen Account
    $stmt = $pdo->prepare(
      'INSERT INTO accounts (first_name, last_name, email, password_hash) 
       VALUES (:first_name, :last_name, :email, :password_hash)'
    );
    
    $stmt->execute([
      'first_name' => $firstName,
      'last_name' => $lastName,
      'email' => $email,
      'password_hash' => $hashed
    ]);
    
    $accountId = (int)$pdo->lastInsertId();
    
    $_SESSION['customer_id'] = $accountId;
    $_SESSION['customer_email'] = $email;
    $_SESSION['customer_name'] = $firstName;
    
    return ['ok' => true, 'message' => 'Registrierung erfolgreich! Du bist jetzt angemeldet.'];
  } catch (Throwable $e) {
    return ['ok' => false, 'error' => 'Registrierung fehlgeschlagen: ' . $e->getMessage()];
  }
}

/**
 * Meldet einen Benutzer an
 */
function loginUser(string $email, string $password): array {
  try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare(
      'SELECT id, first_name, email, password_hash FROM accounts WHERE email = :email LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if (!$user) {
      return ['ok' => false, 'error' => 'Email oder Passwort falsch.'];
    }
    
    // Verifiziere Passwort
    if (!password_verify($password, $user['password_hash'])) {
      return ['ok' => false, 'error' => 'Email oder Passwort falsch.'];
    }
    
    $_SESSION['customer_id'] = (int)$user['id'];
    $_SESSION['customer_email'] = $user['email'];
    $_SESSION['customer_name'] = $user['first_name'];
    
    return ['ok' => true, 'message' => 'Anmeldung erfolgreich!'];
  } catch (Throwable $e) {
    return ['ok' => false, 'error' => 'Anmeldung fehlgeschlagen: ' . $e->getMessage()];
  }
}

/**
 * Logout
 */
function logoutUser(): void {
  session_destroy();
  header('Location: login.php');
  exit;
}

/**
 * Prüfe, ob Benutzer angemeldet ist
 */
function isLoggedIn(): bool {
  return isset($_SESSION['customer_id']) && isset($_SESSION['customer_email']);
}

/**
 * Benutzer-ID bekommen
 */
function getCurrentUserId(): ?int {
  return $_SESSION['customer_id'] ?? null;
}

/**
 * Benutzer-Email bekommen
 */
function getCurrentUserEmail(): ?string {
  return $_SESSION['customer_email'] ?? null;
}

/**
 * Benutzer-Name bekommen
 */
function getCurrentUserName(): ?string {
  return $_SESSION['customer_name'] ?? null;
}
