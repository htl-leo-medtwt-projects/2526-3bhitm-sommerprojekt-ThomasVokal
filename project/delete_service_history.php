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

$entryId = isset($data['id']) ? (int)$data['id'] : 0;
if ($entryId <= 0) {
  respond(422, ['ok' => false, 'error' => 'Gueltige Eintrags-ID erforderlich']);
}

$customerId = getCurrentUserId();
if (!$customerId) {
  respond(401, ['ok' => false, 'error' => 'Nicht angemeldet']);
}

try {
  $pdo = getDbConnection();

  $entryStatement = $pdo->prepare(
    'SELECT sh.id, sh.vehicle_id, sh.mileage
     FROM service_history sh
     INNER JOIN vehicles v ON v.id = sh.vehicle_id
     WHERE sh.id = :id
       AND v.account_id = :account_id
     LIMIT 1'
  );
  $entryStatement->execute([
    'id' => $entryId,
    'account_id' => $customerId,
  ]);

  $entry = $entryStatement->fetch();
  if (!is_array($entry)) {
    respond(404, ['ok' => false, 'error' => 'Eintrag nicht gefunden']);
  }

  $vehicleId = (int)$entry['vehicle_id'];
  $deletedMileage = (int)$entry['mileage'];

  $deleteStatement = $pdo->prepare(
    'DELETE sh
     FROM service_history sh
     INNER JOIN vehicles v ON v.id = sh.vehicle_id
     WHERE sh.id = :id
       AND v.account_id = :account_id'
  );
  $deleteStatement->execute([
    'id' => $entryId,
    'account_id' => $customerId,
  ]);

  $mileageStatement = $pdo->prepare(
    'SELECT COALESCE(MAX(mileage), 0) AS max_mileage
     FROM service_history
     WHERE vehicle_id = :vehicle_id'
  );
  $mileageStatement->execute(['vehicle_id' => $vehicleId]);
  $newMileage = (int)($mileageStatement->fetchColumn() ?: 0);

  if ($deletedMileage >= $newMileage) {
    $updateMileageStatement = $pdo->prepare(
      'UPDATE vehicles
       SET mileage = :mileage,
           updated_at = CURRENT_TIMESTAMP
       WHERE id = :vehicle_id
         AND account_id = :account_id'
    );
    $updateMileageStatement->execute([
      'mileage' => $newMileage,
      'vehicle_id' => $vehicleId,
      'account_id' => $customerId,
    ]);
  }

  respond(200, [
    'ok' => true,
    'deletedId' => $entryId,
  ]);
} catch (Throwable $exception) {
  error_log('CarFixFast Delete Service History Error: ' . $exception->getMessage());
  respond(500, ['ok' => false, 'error' => 'Datenbank nicht erreichbar']);
}