<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$site_id    = (int)($data['site_id'] ?? 0);
$event_type = $data['event_type'] ?? '';
$product    = $data['product'] ?? '';

if (!$site_id || !in_array($event_type, ['click', 'lead'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$pdo = getPDO();
$event_data = ['product' => $product];

$stmt = $pdo->prepare("INSERT INTO analytics_events (site_id, event_type, event_data, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([
    $site_id,
    $event_type,
    json_encode($event_data, JSON_UNESCAPED_UNICODE),
    $_SERVER['REMOTE_ADDR'] ?? '',
    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
]);

echo json_encode(['ok' => true]);