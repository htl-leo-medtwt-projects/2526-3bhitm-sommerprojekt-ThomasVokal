<?php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = getDbConnection();
  try {
    $statement = $pdo->query(
      'SELECT id, part_number, name, brand, description, image_path, price, stock_quantity, created_at, updated_at
       FROM parts
       ORDER BY id ASC'
    );
  } catch (Throwable $queryException) {
    $statement = $pdo->query(
      'SELECT id, part_number, name, brand, description, price, stock_quantity, created_at, updated_at
       FROM parts
       ORDER BY id ASC'
    );
  }

  echo json_encode($statement->fetchAll(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'error' => 'Ersatzteile konnten nicht geladen werden.',
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
