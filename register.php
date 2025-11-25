<?php
require_once __DIR__ . '/helpers.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Неверный email';
    }
    if (strlen($pass) < 6) {
        $errors[] = 'Пароль минимум 6 символов';
    }
    if ($pass !== $pass2) {
        $errors[] = 'Пароли не совпадают';
    }

    if (!$errors) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Пользователь с таким email уже существует';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)");
            $stmt->execute([$email, $hash, $name]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth">
<div class="auth-card">
    <h1>Регистрация</h1>
    <?php if ($errors): ?>
        <div class="errors">
            <?php foreach ($errors as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Имя
            <input type="text" name="name">
        </label>
        <label>Пароль
            <input type="password" name="password" required>
        </label>
        <label>Повтор пароля
            <input type="password" name="password2" required>
        </label>
        <button type="submit">Создать аккаунт</button>
    </form>
    <p><a href="login.php">У меня уже есть аккаунт</a></p>
</div>
</body>
</html>
