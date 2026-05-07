<?php
declare(strict_types=1);

// Lade .env Datei wenn sie existiert
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
  $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
      list($key, $value) = explode('=', $line, 2);
      $key = trim($key);
      $value = trim($value);
      if (!getenv($key)) {
        putenv("$key=$value");
      }
    }
  }
}

function getDbConnection(): PDO {
  static $pdo = null;

  if ($pdo instanceof PDO) {
    return $pdo;
  }

  $host = getenv('DB_HOST') ?: 'db_server';
  $port = (int)(getenv('DB_PORT') ?: 3306);
  $name = getenv('DB_NAME') ?: 'carfixfast';
  $user = getenv('DB_USER') ?: 'root';
  $pass = getenv('DB_PASS') ?: 'rootpassword';

  $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  return $pdo;
}