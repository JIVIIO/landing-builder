<?php
require_once __DIR__ . '/helpers.php';
requireLogin();

$user = currentUser();
$pdo = getPDO();

$stmt = $pdo->prepare("SELECT * FROM sites WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$sites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои сайты</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-left">
        <span class="logo">LBuilder</span>
    </div>
    <div class="topbar-right">
        <span><?= htmlspecialchars($user['email']) ?></span>
        <a href="logout.php">Выйти</a>
    </div>
</header>

<main class="container">
    <div class="dashboard-header">
        <h1>Мои сайты</h1>
        <a href="site_edit.php" class="btn-primary">Создать сайт</a>
    </div>

    <?php if (!$sites): ?>
        <p>У вас пока нет сайтов. Нажмите «Создать сайт».</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr>
                <th>Название</th>
                <th>Поддомен</th>
                <th>Статус</th>
                <th>Ссылка</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($sites as $site): ?>
                <tr>
                    <td><?= htmlspecialchars($site['name']) ?></td>
                    <td><?= htmlspecialchars($site['slug']) ?>.<?= MAIN_DOMAIN ?></td>
                    <td><?= $site['is_published'] ? 'Опубликован' : 'Черновик' ?></td>
                    <td>
                        <?php if ($site['is_published']): ?>
                            <a href="https://<?= htmlspecialchars($site['slug']) . '.' . MAIN_DOMAIN ?>" target="_blank">Открыть</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="site_edit.php?id=<?= (int)$site['id'] ?>">Редактировать</a> |
                        <a href="site_delete.php?id=<?= (int)$site['id'] }" onclick="return confirm('Удалить сайт?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</main>
</body>
</html>
