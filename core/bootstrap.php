<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
mb_internal_encoding('UTF-8');
require ROOT . '/core/helpers.php';

if (!is_file(ROOT . '/config.php')) {
    if (!defined('INSTALLING')) redirect_to_install();
    return;
}
$GLOBALS['config'] = require ROOT . '/config.php';
if (!empty($GLOBALS['config']['debug'])) { ini_set('display_errors', '1'); error_reporting(E_ALL); }
else { ini_set('display_errors', '0'); }

function redirect_to_install(): never { header('Location: /install.php'); exit; }

require ROOT . '/core/db.php';
require ROOT . '/core/auth.php';
require ROOT . '/core/security.php';
require ROOT . '/core/images.php';
require ROOT . '/core/blocks.php';
require ROOT . '/core/blocks_render.php';
require ROOT . '/core/blocks_extra.php';
require ROOT . '/core/theme.php';
require ROOT . '/core/blog.php';
require ROOT . '/core/seo.php';

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('pcms');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
session_start();
harden_session();
send_security_headers();

require ROOT . '/core/migrate.php';
migrate();
