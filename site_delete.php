<?php
require_once __DIR__ . '/helpers.php';
requireLogin();
$user = currentUser();
$pdo  = getPDO();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM site_blocks WHERE site_id = ?");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user['id']]);
}

header('Location: dashboard.php');
exit;
