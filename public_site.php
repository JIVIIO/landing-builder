<?php
require_once __DIR__ . '/helpers.php';

$host = $_SERVER['HTTP_HOST'] ?? '';
$slug = '';

// Определяем slug по поддомену
if ($host) {
    $parts = explode('.', $host);
    // Ожидаем не менее 3 частей: podomain.domain.tld
    if (count($parts) >= 3) {
        $slug = $parts[0];
    } elseif (count($parts) == 2 && $host !== MAIN_DOMAIN) {
        // Если домен второго уровня (например, myshop.com), допускаем slug=первой части
        $slug = $parts[0];
    }
}

// Запасной вариант: передача slug через параметр ?s=
if (!$slug && isset($_GET['s'])) {
    $slug = sanitizeSlug($_GET['s']);
}

if (!$slug) {
    http_response_code(404);
    echo "Сайт не найден";
    exit;
}

$site = findSiteBySlug($slug);
if (!$site) {
    http_response_code(404);
    echo "Сайт не найден";
    exit;
}

$config = getSiteBlockConfig($site['id']) ?? [];

// Логируем просмотр страницы
$pdo = getPDO();
$stmt = $pdo->prepare("INSERT INTO analytics_events (site_id, event_type, event_data, ip, user_agent) VALUES (?, 'view', ?, ?, ?)");
$stmt->execute([
    $site['id'],
    json_encode(new stdClass()),
    $_SERVER['REMOTE_ADDR'] ?? '',
    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
]);

$hero_title       = $config['hero_title']       ?? 'Добро пожаловать';
$hero_subtitle    = $config['hero_subtitle']    ?? '';
$cta_text         = $config['cta_text']         ?? 'Оставить заявку';
$cta_link         = $config['cta_link']         ?? '#contact';
$products         = $config['products']         ?? [];
$contact_title    = $config['contact_title']    ?? 'Связаться с нами';
$contact_phone    = $config['contact_phone']    ?? '';
$contact_telegram = $config['contact_telegram'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($site['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="landing">

<section class="hero">
    <div class="hero-inner">
        <h1><?= htmlspecialchars($hero_title) ?></h1>
        <p><?= nl2br(htmlspecialchars($hero_subtitle)) ?></p>
        <a href="<?= htmlspecialchars($cta_link) ?>" class="btn-primary"
           data-track="click" data-site-id="<?= (int)$site['id'] ?>">
            <?= htmlspecialchars($cta_text) ?>
        </a>
    </div>
</section>

<?php if ($products): ?>
<section class="products">
    <div class="container">
        <h2>Наши предложения</h2>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <?php if ($p['price'] !== ''): ?>
                        <p class="product-price"><?= htmlspecialchars($p['price']) ?> ₽</p>
                    <?php endif; ?>
                    <?php if ($p['desc'] !== ''): ?>
                        <p><?= htmlspecialchars($p['desc']) ?></p>
                    <?php endif; ?>
                    <button class="btn-secondary"
                            data-track="lead"
                            data-site-id="<?= (int)$site['id'] ?>"
                            data-product="<?= htmlspecialchars($p['name']) ?>">
                        Оставить заявку
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="contacts" id="contact">
    <div class="container">
        <h2><?= htmlspecialchars($contact_title) ?></h2>
        <?php if ($contact_phone): ?>
            <p>Телефон: <a href="tel:<?= htmlspecialchars($contact_phone) ?>"><?= htmlspecialchars($contact_phone) ?></a></p>
        <?php endif; ?>
        <?php if ($contact_telegram): ?>
            <p>Telegram: <a href="<?= htmlspecialchars($contact_telegram) ?>" target="_blank"><?= htmlspecialchars($contact_telegram) ?></a></p>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('click', function (e) {
    const target = e.target.closest('[data-track]');
    if (!target) return;

    const type = target.getAttribute('data-track');
    const siteId = target.getAttribute('data-site-id');
    const product = target.getAttribute('data-product') || '';

    fetch('/track.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            site_id: siteId,
            event_type: type,
            product: product
        })
    }).catch(() => {});
});
</script>

</body>
</html>