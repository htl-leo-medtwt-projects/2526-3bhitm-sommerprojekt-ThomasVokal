<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): void {
  http_response_code($status);
  echo json_encode($payload);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
  respond(400, ['ok' => false, 'error' => 'Invalid JSON']);
}

if (!isset($data['firstName']) || $data['firstName'] === '') {
  respond(422, ['ok' => false, 'error' => 'Missing required field: firstName']);
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
  'firstName' => trim((string)$data['firstName']),
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

$_SESSION['dashboard_profile'] = $profile;

respond(200, ['ok' => true]);
