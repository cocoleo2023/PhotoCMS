<?php
declare(strict_types=1);

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function slugify(string $s): string {
    static $map = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya','і'=>'i','ў'=>'u'];
    $s = strtr(mb_strtolower($s, 'UTF-8'), $map);
    $s = trim((string)preg_replace('~[^a-z0-9]+~', '-', $s), '-');
    return $s !== '' ? $s : 'item';
}

function unique_slug(string $table, string $slug, int $ignoreId = 0): string {
    $base = $slug; $i = 2;
    while (db_value("SELECT COUNT(*) FROM `$table` WHERE slug = ? AND id <> ?", [$slug, $ignoreId]) > 0) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

function redirect(string $url): never { header('Location: ' . $url); exit; }

function setting(string $key, string $default = ''): string {
    static $cache = null;
    if (isset($GLOBALS['setting_override'][$key])) return (string)$GLOBALS['setting_override'][$key];   // live design preview
    if ($cache === null) {
        $cache = [];
        foreach (db_all('SELECT k, v FROM settings') as $r) $cache[$r['k']] = (string)$r['v'];
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void {
    db_exec('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$key, $value]);
}

function flash(?string $msg = null, string $type = 'ok'): ?array {
    if ($msg !== null) { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $t = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) { http_response_code(419); exit('Сессия устарела. Обновите страницу и повторите.'); }
}

function url_for(string $path = ''): string { return '/' . ltrim($path, '/'); }
function upload_url(int $galleryId, string $file): string { return '/uploads/' . ($galleryId > 0 ? 'g' . $galleryId : 'media') . '/' . $file; }
