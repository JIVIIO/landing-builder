<?php
// helpers.php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function requireLogin(): void {
    if (!currentUser()) {
        header('Location: login.php');
        exit;
    }
}

function sanitizeSlug(string $slug): string {
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function findSiteBySlug(string $slug): ?array {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM sites WHERE slug = ? AND is_published = 1");
    $stmt->execute([$slug]);
    $site = $stmt->fetch();
    return $site ?: null;
}

function getSiteBlockConfig(int $site_id): ?array {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM site_blocks WHERE site_id = ? AND block_type = 'page' LIMIT 1");
    $stmt->execute([$site_id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return json_decode($row['config'], true);
}
