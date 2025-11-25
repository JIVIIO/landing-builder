<?php
require_once __DIR__ . '/helpers.php';
requireLogin();
$user = currentUser();
$pdo  = getPDO();

$site = null;
$config = [
    'hero_title'       => 'Заголовок вашего сайта',
    'hero_subtitle'    => 'Короткое описание, чем вы полезны',
    'cta_text'         => 'Оставить заявку',
    'cta_link'         => '#contact',
    'products'         => [
        ['name' => 'Товар 1', 'price' => '1000', 'desc' => 'Описание товара 1'],
        ['name' => 'Товар 2', 'price' => '2000', 'desc' => 'Описание товара 2'],
    ],
    'contact_title'    => 'Связаться с нами',
    'contact_phone'    => '',
    'contact_telegram' => '',
];

$is_new = true;

if (isset($_GET['id'])) {
    $is_new = false;
    $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_GET['id'], $user['id']]);
    $site = $stmt->fetch();
    if (!$site) {
        die('Сайт не найден');
    }

    $cfg = getSiteBlockConfig($site['id']);
    if ($cfg) {
        $config = array_merge($config, $cfg);
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $slug_raw     = trim($_POST['slug'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    $hero_title       = trim($_POST['hero_title'] ?? '');
    $hero_subtitle    = trim($_POST['hero_subtitle'] ?? '');
    $cta_text         = trim($_POST['cta_text'] ?? '');
    $cta_link         = trim($_POST['cta_link'] ?? '');
    $contact_title    = trim($_POST['contact_title'] ?? '');
    $contact_phone    = trim($_POST['contact_phone'] ?? '');
    $contact_telegram = trim($_POST['contact_telegram'] ?? '');

    $products = [];
    if (isset($_POST['product_name']) && is_array($_POST['product_name'])) {
        foreach ($_POST['product_name'] as $i => $pname) {
            $pname = trim($pname);
            if ($pname === '') continue;
            $products[] = [
                'name'  => $pname,
                'price' => trim($_POST['product_price'][$i] ?? ''),
                'desc'  => trim($_POST['product_desc'][$i] ?? ''),
            ];
        }
    }

    if ($name === '') {
        $errors[] = 'Укажите название сайта';
    }

    $slug = sanitizeSlug($slug_raw ?: $name);
    if ($slug === '') {
        $errors[] = 'Не удалось сформировать поддомен (slug)';
    }

    if ($slug) {
        if ($is_new) {
            $stmt = $pdo->prepare("SELECT id FROM sites WHERE slug = ?");
            $stmt->execute([$slug]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM sites WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $site['id']]);
        }
        if ($stmt->fetch()) {
            $errors[] = 'Такой поддомен уже используется, попробуйте другой';
        }
    }

    if (!$errors) {
        if ($is_new) {
            $stmt = $pdo->prepare("INSERT INTO sites (user_id, name, slug, template_id, is_published) VALUES (?, ?, ?, 1, ?)");
            $stmt->execute([$user['id'], $name, $slug, $is_published]);
            $site_id = (int)$pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare("UPDATE sites SET name = ?, slug = ?, is_published = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$name, $slug, $is_published, $site['id'], $user['id']]);
            $site_id = $site['id'];
        }

        $config = [
            'hero_title'       => $hero_title,
            'hero_subtitle'    => $hero_subtitle,
            'cta_text'         => $cta_text,
            'cta_link'         => $cta_link,
            'products'         => $products,
            'contact_title'    => $contact_title,
            'contact_phone'    => $contact_phone,
            'contact_telegram' => $contact_telegram,
        ];
        $json = json_encode($config, JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("SELECT id FROM site_blocks WHERE site_id = ? AND block_type = 'page'");
        $stmt->execute([$site_id]);
        $row = $stmt->fetch();
        if ($row) {
            $stmt = $pdo->prepare("UPDATE site_blocks SET config = ? WHERE id = ?");
            $stmt->execute([$json, $row['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO site_blocks (site_id, block_type, config) VALUES (?, 'page', ?)");
            $stmt->execute([$site_id, $json]);
        }

        header('Location: dashboard.php');
        exit;
    } else {
        $config = [
            'hero_title'       => $hero_title,
            'hero_subtitle'    => $hero_subtitle,
            'cta_text'         => $cta_text,
            'cta_link'         => $cta_link,
            'products'         => $products,
            'contact_title'    => $contact_title,
            'contact_phone'    => $contact_phone,
            'contact_telegram' => $contact_telegram,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_new ? 'Создать сайт' : 'Редактировать сайт'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-left">
        <span class="logo">LBuilder</span>
    </div>
    <div class="topbar-right">
        <a href="dashboard.php">Мои сайты</a>
        <a href="logout.php">Выйти</a>
    </div>
</header>

<main class="container">
    <h1><?php echo $is_new ? 'Создать сайт' : 'Редактировать сайт'; ?></h1>

    <?php if ($errors): ?>
        <div class="errors">
            <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="card">
            <h2>Основное</h2>
            <label>Название сайта
                <input type="text" name="name" required value="<?php echo htmlspecialchars($site['name'] ?? ''); ?>">
            </label>

            <label>Поддомен (латиница, без пробелов)
                <input type="text" name="slug" value="<?php echo htmlspecialchars($site['slug'] ?? ''); ?>">
                <small>Пример: myshop → myshop.<?php echo MAIN_DOMAIN; ?></small>
            </label>

            <label class="checkbox">
                <input type="checkbox" name="is_published" <?php echo !empty($site['is_published']) ? 'checked' : ''; ?>>
                Опубликовать сайт
            </label>
        </div>

        <div class="card">
            <h2>Главный блок</h2>
            <label>Заголовок
                <input type="text" name="hero_title" value="<?php echo htmlspecialchars($config['hero_title']); ?>">
            </label>
            <label>Подзаголовок
                <textarea name="hero_subtitle"><?php echo htmlspecialchars($config['hero_subtitle']); ?></textarea>
            </label>
            <label>Текст кнопки
                <input type="text" name="cta_text" value="<?php echo htmlspecialchars($config['cta_text']); ?>">
            </label>
            <label>Ссылка кнопки
                <input type="text" name="cta_link" value="<?php echo htmlspecialchars($config['cta_link']); ?>">
            </label>
        </div>

        <div class="card">
            <h2>Каталог товаров (простая витрина)</h2>
            <?php
            $maxProducts = max(3, count($config['products']));
            for ($i = 0; $i < $maxProducts; $i++):
                $p = $config['products'][$i] ?? ['name' => '', 'price' => '', 'desc' => ''];
            ?>
                <div class="product-row">
                    <label>Название товара
                        <input type="text" name="product_name[]" value="<?php echo htmlspecialchars($p['name']); ?>">
                    </label>
                    <label>Цена
                        <input type="text" name="product_price[]" value="<?php echo htmlspecialchars($p['price']); ?>">
                    </label>
                    <label>Описание
                        <input type="text" name="product_desc[]" value="<?php echo htmlspecialchars($p['desc']); ?>">
                    </label>
                </div>
            <?php endfor; ?>
        </div>

        <div class="card">
            <h2>Контакты</h2>
            <label>Заголовок блока
                <input type="text" name="contact_title" value="<?php echo htmlspecialchars($config['contact_title']); ?>">
            </label>
            <label>Телефон
                <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($config['contact_phone']); ?>">
            </label>
            <label>Telegram
                <input type="text" name="contact_telegram" value="<?php echo htmlspecialchars($config['contact_telegram']); ?>">
            </label>
        </div>

        <button type="submit" class="btn-primary">Сохранить</button>
    </form>
</main>
</body>
</html>
