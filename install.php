<?php
declare(strict_types=1);
define('INSTALLING', true);
require __DIR__ . '/core/bootstrap.php';
if (is_file(ROOT . '/config.php')) { http_response_code(403); exit('Сайт уже установлен. Удалите install.php с сервера.'); }

$err = ''; $v = ['host'=>'localhost','name'=>'','user'=>'','title'=>'','email'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['host','name','user','title','email'] as $k) $v[$k] = trim((string)($_POST[$k] ?? ''));
    $dbpass = (string)($_POST['dbpass'] ?? ''); $pass = (string)($_POST['pass'] ?? '');
    try {
        if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Укажите корректный email администратора');
        if (mb_strlen($pass) < 10) throw new RuntimeException('Пароль администратора — минимум 10 символов');
        $pdo = new PDO("mysql:host={$v['host']};dbname={$v['name']};charset=utf8mb4", $v['user'], $dbpass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        foreach (array_filter(array_map('trim', explode(';', file_get_contents(ROOT . '/core/schema.sql')))) as $sql) $pdo->exec($sql);
        $pdo->prepare('INSERT INTO users (email, pass_hash, created_at) VALUES (?, ?, NOW())')->execute([$v['email'], password_hash($pass, PASSWORD_DEFAULT)]);
        $ins = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?)');
        foreach (['site_title' => $v['title'] ?: 'Фотограф', 'site_description' => '', 'theme' => 'air', 'footer_text' => '', 'schema_version' => '4', 'palette' => 'air'] as $k => $val) $ins->execute([$k, $val]);
        $cfg = "<?php\nreturn " . var_export(['db' => ['host'=>$v['host'],'name'=>$v['name'],'user'=>$v['user'],'pass'=>$dbpass], 'debug' => false], true) . ";\n";
        if (file_put_contents(ROOT . '/config.php', $cfg) === false) throw new RuntimeException('Не удалось записать config.php — проверьте права на папку');
        @chmod(ROOT . '/config.php', 0640);
        header('Location: /admin/?r=login'); exit;
    } catch (Throwable $ex) { $err = $ex->getMessage(); }
}
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Установка</title><link rel="stylesheet" href="/admin/assets/admin.css"></head>
<body class="auth"><main class="card auth-card">
<h1>Установка сайта</h1>
<p class="muted">Нужна пустая база MySQL. Данные сохранятся в config.php.</p>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="stack">
<label>Название сайта<input name="title" value="<?= e($v['title']) ?>" placeholder="Леонид Древило, фотограф"></label>
<fieldset><legend>База данных</legend>
<label>Сервер<input name="host" value="<?= e($v['host']) ?>" required></label>
<label>Имя базы<input name="name" value="<?= e($v['name']) ?>" required></label>
<label>Пользователь<input name="user" value="<?= e($v['user']) ?>" required></label>
<label>Пароль<input type="password" name="dbpass" autocomplete="off"></label></fieldset>
<fieldset><legend>Администратор</legend>
<label>Email<input type="email" name="email" value="<?= e($v['email']) ?>" required></label>
<label>Пароль (от 10 символов)<input type="password" name="pass" minlength="10" required autocomplete="new-password"></label></fieldset>
<button class="btn primary">Установить</button>
</form></main></body></html>
