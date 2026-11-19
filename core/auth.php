<?php
declare(strict_types=1);

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    return db_row('SELECT id, email FROM users WHERE id = ?', [$_SESSION['uid']]);
}
function require_login(): array {
    $u = current_user();
    if (!$u) redirect('/admin/?r=login');
    return $u;
}
function attempt_login(string $email, string $pass): bool {
    $_SESSION['tries'] = ($_SESSION['tries'] ?? 0) + 1;
    $tries = (int)$_SESSION['tries'];
    if ($tries > 5) { sleep(min(8, $tries - 4)); }
    if ($tries > 20) {
        http_response_code(429);
        exit('Слишком много попыток входа. Подождите несколько минут.');
    }
    $u = db_row('SELECT * FROM users WHERE email = ?', [trim($email)]);
    if ($u && password_verify($pass, $u['pass_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        $_SESSION['tries'] = 0;
        return true;
    }
    return false;
}
function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
