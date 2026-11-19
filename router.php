<?php
// Локальный запуск: php -S localhost:8080 router.php
// Заменяет правила .htaccess (на боевом Apache-хостинге этот файл не нужен).
$p = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$root = __DIR__;

// закрытые места, как в .htaccess
if (preg_match('~^/(core|storage)(/|$)~', $p) || $p === '/config.php' || $p === '/router.php') { http_response_code(403); exit('Forbidden'); }

$file = $root . $p;
if ($p !== '/' && is_file($file) && !str_ends_with($p, '.php')) return false;   // статика: css, js, фото
if ($p === '/install.php') { require $file; return true; }
if ($p === '/admin' || str_starts_with($p, '/admin/')) { chdir($root . '/admin'); require $root . '/admin/index.php'; return true; }
require $root . '/index.php';
