<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';

require_login();

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$ids  = $body['ids'] ?? [];

if (!is_array($ids) || empty($ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$db   = get_db();
$stmt = $db->prepare("UPDATE projects SET sort_order = ? WHERE id = ?");

$db->beginTransaction();
$total = count($ids);
foreach ($ids as $i => $id) {
    // First item in array = highest sort_order = shown first (DESC on frontend)
    $stmt->execute([$total - $i, (int)$id]);
}
$db->commit();

echo json_encode(['ok' => true]);
