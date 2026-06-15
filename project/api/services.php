<?php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = getDbConnection();
  $statement = $pdo->query(
    'SELECT id, name, category, description, price, duration_minutes, created_at, updated_at
     FROM services
     ORDER BY id ASC'
  );

  echo json_encode($statement->fetchAll(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Leistungen konnten nicht geladen werden.',
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
