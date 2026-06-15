<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): void {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

if (!isLoggedIn()) {
  respond(401, ['ok' => false, 'error' => 'Nicht angemeldet']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
  respond(400, ['ok' => false, 'error' => 'Ungueltige Daten']);
}

$serviceDate = trim((string)($data['serviceDate'] ?? ''));
$serviceName = trim((string)($data['serviceName'] ?? ''));
$mileage = isset($data['mileage']) ? (int)$data['mileage'] : -1;
$costRaw = $data['cost'] ?? null;
$mechanic = trim((string)($data['mechanic'] ?? ''));
$notes = trim((string)($data['notes'] ?? ''));

if ($serviceDate === '' || $serviceName === '') {
  respond(422, ['ok' => false, 'error' => 'Datum und Servicebezeichnung sind erforderlich.']);
}

$dateObject = DateTimeImmutable::createFromFormat('Y-m-d', $serviceDate);
$dateErrors = DateTimeImmutable::getLastErrors();
if (!$dateObject || ($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0) {
  respond(422, ['ok' => false, 'error' => 'Bitte ein gueltiges Datum eingeben.']);
}

if ($mileage < 0) {
  respond(422, ['ok' => false, 'error' => 'Bitte einen gueltigen Kilometerstand eingeben.']);
}

if (!is_numeric($costRaw)) {
  respond(422, ['ok' => false, 'error' => 'Bitte gueltige Kosten eingeben.']);
}

$cost = (float)$costRaw;
if ($cost < 0) {
  respond(422, ['ok' => false, 'error' => 'Kosten duerfen nicht negativ sein.']);
}

$customerId = getCurrentUserId();
if (!$customerId) {
  respond(401, ['ok' => false, 'error' => 'Nicht angemeldet']);
}

try {
  $pdo = getDbConnection();

  $vehicleStatement = $pdo->prepare(
    'SELECT id
     FROM vehicles
     WHERE account_id = :account_id
     ORDER BY updated_at DESC, id DESC
     LIMIT 1'
  );
  $vehicleStatement->execute(['account_id' => $customerId]);
  $vehicle = $vehicleStatement->fetch();

  if (!is_array($vehicle)) {
    respond(422, ['ok' => false, 'error' => 'Bitte zuerst Fahrzeugdaten speichern.']);
  }

  $vehicleId = (int)$vehicle['id'];

  $insertStatement = $pdo->prepare(
    'INSERT INTO service_history (
      vehicle_id,
      service_date,
      service_name,
      mileage,
      cost,
      mechanic,
      notes
    ) VALUES (
      :vehicle_id,
      :service_date,
      :service_name,
      :mileage,
      :cost,
      :mechanic,
      :notes
    )'
  );

  $insertStatement->execute([
    'vehicle_id' => $vehicleId,
    'service_date' => $serviceDate,
    'service_name' => $serviceName,
    'mileage' => $mileage,
    'cost' => $cost,
    'mechanic' => $mechanic !== '' ? $mechanic : null,
    'notes' => $notes !== '' ? $notes : null,
  ]);

  $updateMileageStatement = $pdo->prepare(
    'UPDATE vehicles
     SET mileage = GREATEST(mileage, :mileage),
         updated_at = CURRENT_TIMESTAMP
     WHERE id = :vehicle_id'
  );
  $updateMileageStatement->execute([
    'mileage' => $mileage,
    'vehicle_id' => $vehicleId,
  ]);

  $serviceText = mb_strtolower($serviceName);
  $dotColor = 'primary';
  if ($cost <= 0) {
    $dotColor = 'success';
  } elseif (str_contains($serviceText, 'brems')) {
    $dotColor = 'warning';
  }

  respond(200, [
    'ok' => true,
    'entry' => [
      'date' => $dateObject->format('d.m.Y'),
      'service' => $serviceName,
      'mileage' => $mileage,
      'cost' => $cost,
      'mechanic' => $mechanic !== '' ? $mechanic : 'Unbekannt',
      'notes' => $notes,
      'dotColor' => $dotColor,
    ],
  ]);
} catch (Throwable $exception) {
  error_log('CarFixFast Service History Error: ' . $exception->getMessage());
  respond(500, ['ok' => false, 'error' => 'Datenbank nicht erreichbar']);
}
