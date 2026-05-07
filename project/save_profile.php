<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): void {
  http_response_code($status);
  echo json_encode($payload);
  exit;
}

// Prüfe Auth
if (!isLoggedIn()) {
  respond(401, ['ok' => false, 'error' => 'Nicht angemeldet']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
  respond(400, ['ok' => false, 'error' => 'Invalid JSON']);
}

if (!isset($data['licensePlate']) || $data['licensePlate'] === '') {
  respond(422, ['ok' => false, 'error' => 'Missing required field: licensePlate']);
}

if (!isset($data['make']) || $data['make'] === '') {
  respond(422, ['ok' => false, 'error' => 'Missing required field: make']);
}

if (!isset($data['model']) || $data['model'] === '') {
  respond(422, ['ok' => false, 'error' => 'Missing required field: model']);
}

if (!isset($data['year']) || $data['year'] === '') {
  respond(422, ['ok' => false, 'error' => 'Missing required field: year']);
}

if (!isset($data['mileage']) || $data['mileage'] === '') {
  respond(422, ['ok' => false, 'error' => 'Missing required field: mileage']);
}

$engine = '';
if (isset($data['engine'])) {
  $engine = trim((string)$data['engine']);
}

$color = '';
if (isset($data['color'])) {
  $color = trim((string)$data['color']);
}

$profile = [
  'licensePlate' => strtoupper(trim((string)$data['licensePlate'])),
  'make' => trim((string)$data['make']),
  'model' => trim((string)$data['model']),
  'year' => (int)$data['year'],
  'mileage' => (int)$data['mileage'],
  'engine' => $engine,
  'color' => $color,
];

if ($profile['year'] < 1980) {
  respond(422, ['ok' => false, 'error' => 'Invalid year or mileage']);
}

if ($profile['year'] > 2035) {
  respond(422, ['ok' => false, 'error' => 'Invalid year or mileage']);
}

if ($profile['mileage'] < 0) {
  respond(422, ['ok' => false, 'error' => 'Invalid year or mileage']);
}

$customerId = getCurrentUserId();

try {
  $pdo = getDbConnection();
  
  // Prüfe, ob Auto schon existiert
  $check = $pdo->prepare(
    'SELECT id FROM vehicles WHERE account_id = :account_id AND license_plate = :license_plate LIMIT 1'
  );
  $check->execute([
    'account_id' => $customerId,
    'license_plate' => $profile['licensePlate']
  ]);
  
  $existing = $check->fetch();
  
  if ($existing) {
    // UPDATE
    $statement = $pdo->prepare(
      'UPDATE vehicles SET
        make = :make,
        model = :model,
        year = :year,
        mileage = :mileage,
        engine = :engine,
        color = :color,
        updated_at = CURRENT_TIMESTAMP
       WHERE id = :id'
    );
    
    $statement->execute([
      'id' => $existing['id'],
      'make' => $profile['make'],
      'model' => $profile['model'],
      'year' => $profile['year'],
      'mileage' => $profile['mileage'],
      'engine' => $engine !== '' ? $engine : null,
      'color' => $color !== '' ? $color : null,
    ]);
  } else {
    // INSERT
    $statement = $pdo->prepare(
      'INSERT INTO vehicles (
        account_id,
        license_plate,
        make,
        model,
        year,
        mileage,
        engine,
        color
      ) VALUES (
        :account_id,
        :license_plate,
        :make,
        :model,
        :year,
        :mileage,
        :engine,
        :color
      )'
    );

    $statement->execute([
      'account_id' => $customerId,
      'license_plate' => $profile['licensePlate'],
      'make' => $profile['make'],
      'model' => $profile['model'],
      'year' => $profile['year'],
      'mileage' => $profile['mileage'],
      'engine' => $engine !== '' ? $engine : null,
      'color' => $color !== '' ? $color : null,
    ]);
  }
} catch (Throwable $exception) {
  error_log('CarFixFast DB Error: ' . $exception->getMessage());
  respond(500, ['ok' => false, 'error' => 'Datenbank nicht erreichbar: ' . $exception->getMessage()]);
}

respond(200, ['ok' => true]);

