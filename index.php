<?php
declare(strict_types=1);
require __DIR__ . '/core/bootstrap.php';

$path = trim(rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)), '/');
$origin = seo_origin();
seo_early();
$preview = !empty($_GET['preview']) && current_user() !== null;
if ($preview && !empty($_GET['pvd']) && isset($_SESSION['pv_design'])) $GLOBALS['setting_override'] = $_SESSION['pv_design'];   // unsaved design from the admin

function site_context(): array {
    global $preview;
    $home = (int)setting('home_page_id', '0');
    // Hierarchical menu: top-level items + children (menu_parent overrides parent_id for menu tree)
    $allMenu = db_all('SELECT id, title, slug, parent_id, menu_parent FROM pages WHERE is_published = 1 AND show_in_menu = 1 AND id <> ? ORDER BY sort, id', [$home]);
    $byParent = [];
    foreach ($allMenu as $m) {
        $pid = $m['menu_parent'] !== null && $m['menu_parent'] !== '' ? (int)$m['menu_parent'] : 0;
        if ($pid === 0 && $m['parent_id']) $pid = (int)$m['parent_id']; // fallback to page hierarchy
        $byParent[$pid][] = $m;
    }
    $menu = [];
    foreach ($byParent[0] ?? [] as $top) {
        $item = ['title' => $top['title'], 'slug' => $top['slug'], 'children' => []];
        foreach ($byParent[(int)$top['id']] ?? [] as $ch) {
            $item['children'][] = ['title' => $ch['title'], 'slug' => $ch['slug']];
        }
        $menu[] = $item;
    }
    return [
        'title' => setting('site_title', 'Фотограф'), 'description' => setting('site_description'), 'footer' => setting('footer_text'),
        'email' => setting('contact_email'), 'phone' => setting('contact_phone'), 'instagram' => setting('instagram'),
        'fx' => dz('fx'), 'header_layout' => dz('header_layout'), 'header_sticky' => dz('header_sticky'), 'header_over' => dz('header_over'), 'page_fade' => dz('page_fade'),
        'menu' => $menu,
        'has_galleries' => (int)db_value('SELECT COUNT(*) FROM galleries WHERE is_published = 1') > 0,
        'has_blog' => blog_count(0, $preview) > 0,
        'header_html' => setting('header_html', ''),
        'footer_html' => setting('footer_html', ''),
        'footer_layout' => dz('footer_layout') ?: 'center',
    ];
}
function active_theme(): string {
    $t = preg_replace('~[^a-z0-9_-]~i', '', setting('theme', 'air'));
    return is_dir(ROOT . "/themes/$t") ? $t : 'air';
}
function render(string $tpl, array $vars, int $status = 200): never {
    global $origin, $path, $preview;
    $theme = active_theme();
    $vars += ['site' => site_context(), 'theme' => $theme, 'origin' => $origin, 'path' => $path, 'preview' => $preview];
    $vars['meta'] += ['canonical' => $origin . ($path === '' ? '/' : '/' . $path), 'image' => '', 'type' => 'website'];
    $vars['meta']['title'] = seo_title((string)$vars['meta']['title']);
    if ($status === 404) $vars['meta']['robots'] = 'noindex,follow';
    extract($vars);
    http_response_code($status);
    ob_start(); require ROOT . "/themes/$theme/$tpl.php"; $body = ob_get_clean();
    header('Content-Type: text/html; charset=utf-8');
    if ($preview || seo_s('site_noindex') === '1') header('X-Robots-Tag: noindex');
    require ROOT . "/themes/$theme/layout.php";
    exit;
}
function page_meta(array $pg, string $suffix = ''): array {
    $text = '';
    foreach (page_blocks($pg) as $b) { $text = trim(strip_tags((string)($b['data']['body'] ?? $b['data']['subtitle'] ?? $b['data']['text'] ?? ''))); if ($text !== '') break; }
    $img = '';
    if (!empty($pg['cover'])) {
        $img = $GLOBALS['origin'] . clean_image((string)$pg['cover']);
    } elseif (!empty($pg['og_image'])) {
        $img = $GLOBALS['origin'] . clean_image((string)$pg['og_image']);
    } else {
        foreach (page_blocks($pg) as $b) if (!empty($b['data']['image'])) { $img = $GLOBALS['origin'] . clean_image((string)$b['data']['image']); break; }
    }
    return [
        'title' => ($pg['meta_title'] ?: $pg['title']) . $suffix,
        'description' => $pg['meta_description'] ?: mb_substr($text, 0, 160),
        'image' => $img,
        'type' => 'website',
        'crumbs' => (int)setting('home_page_id', '0') === (int)$pg['id'] ? [] : [['Главная', '/'], [$pg['title'], '/' . $pg['slug']]],
    ] + seo_item_meta($pg, '/' . $pg['slug']);
}

function show_post(array $po, ?array $pv): never {
    global $origin, $preview;
    $blocks = $pv['blocks'] ?? (json_decode((string)$po['blocks'], true) ?: []);
    if (!empty($pv['title'])) $po['title'] = $pv['title'];
    $po['cat_title'] = $po['category_id'] ? db_value('SELECT title FROM categories WHERE id = ?', [$po['category_id']]) : null;
    $po['cat_slug'] = $po['category_id'] ? db_value('SELECT slug FROM categories WHERE id = ?', [$po['category_id']]) : null;
    $related = []; $prev = $next = null;
    if (dz('blog_related') === '1') {
        $related = array_values(array_filter(blog_fetch(4, (int)$po['category_id'], 0, $preview), fn($x) => (int)$x['id'] !== (int)$po['id']));
        $related = array_slice($related, 0, 3);
    }
    $cond = $preview ? '1=1' : 'is_published = 1 AND published_at <= NOW()';
    $prev = db_row("SELECT title, slug FROM posts WHERE $cond AND (published_at < ? OR (published_at = ? AND id < ?)) ORDER BY published_at DESC, id DESC LIMIT 1", [$po['published_at'], $po['published_at'], $po['id']]);
    $next = db_row("SELECT title, slug FROM posts WHERE $cond AND (published_at > ? OR (published_at = ? AND id > ?)) ORDER BY published_at ASC, id ASC LIMIT 1", [$po['published_at'], $po['published_at'], $po['id']]);
    $cover = clean_image((string)($po['og_image'] ?? '')) ?: clean_image((string)$po['cover']);
    $url = $origin . '/blog/' . $po['slug']; $desc = $po['meta_description'] ?: post_excerpt($po, 160); $biz = ['@id' => $origin . '/#business'];
    $ld = array_filter(['@type' => 'BlogPosting', '@id' => $url . '#post', 'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url], 'headline' => mb_substr((string)$po['title'], 0, 110),
        'description' => $desc, 'image' => $cover ? [$origin . $cover] : null, 'datePublished' => seo_iso((string)$po['published_at']),
        'dateModified' => seo_iso((string)($po['updated_at'] ?? '') ?: (string)$po['published_at']), 'author' => $biz, 'publisher' => $biz, 'isPartOf' => ['@id' => $origin . '/#website'], 'inLanguage' => 'ru']);
    render('post', ['post' => $po, 'blocks_html' => render_blocks($blocks, true), 'related' => $related, 'prev' => $prev, 'next' => $next, 'meta' => [
        'title' => ($po['meta_title'] ?: $po['title']) . ' — ' . setting('site_title'), 'description' => $desc, 'image' => $cover ? $origin . $cover : '', 'type' => 'article',
        'published' => seo_iso((string)$po['published_at']), 'schema' => [$ld], 'crumbs' => [['Главная', '/'], ['Блог', '/blog'], [$po['title'], '/blog/' . $po['slug']]]] + seo_item_meta($po, '/blog/' . $po['slug'])]);
}

// ---------- live post preview ----------
if ($preview && isset($_GET['pvpost'])) {
    $po = db_row('SELECT * FROM posts WHERE id = ?', [(int)$_GET['pvpost']]);
    if ($po) show_post($po, $_SESSION['pvp'][(int)$po['id']] ?? null);
}

// ---------- contact form ----------
if ($path === 'contact' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $back = (string)($_POST['back'] ?? '/'); if (!preg_match('~^/[A-Za-z0-9/_-]*$~', $back)) $back = '/';
    $fail = function (string $m) use ($back): never { $_SESSION['pflash'] = ['msg' => $m, 'type' => 'err']; redirect($back . '#contact'); };
    if (!empty($_POST['website'])) redirect($back);                                   // honeypot
    if (time() - (int)($_SESSION['form_t'] ?? 0) < 3) $fail('Форма отправлена слишком быстро. Попробуйте ещё раз.');
    $_SESSION['sent'] = array_filter($_SESSION['sent'] ?? [], fn($t) => $t > time() - 3600);
    if (count($_SESSION['sent']) >= 5) $fail('Слишком много сообщений. Попробуйте позже.');
    $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 190); $email = mb_substr(trim((string)($_POST['email'] ?? '')), 0, 190);
    $phone = mb_substr(trim((string)($_POST['phone'] ?? '')), 0, 60); $msg = mb_substr(trim((string)($_POST['message'] ?? '')), 0, 4000);
    if ($name === '' || $msg === '') $fail('Укажите имя и сообщение.');
    if ($email === '' && $phone === '') $fail('Оставьте email или телефон, чтобы я мог вам ответить.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $fail('Проверьте адрес email.');
    db_exec('INSERT INTO messages (name, email, phone, message, created_at) VALUES (?, ?, ?, ?, NOW())', [$name, $email, $phone, $msg]);
    $_SESSION['sent'][] = time();
    $to = setting('contact_email');
    if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $host = preg_replace('~^www\.~', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $headers = "From: noreply@$host\r\nContent-Type: text/plain; charset=utf-8\r\n" . ($email !== '' ? 'Reply-To: ' . str_replace(["\r", "\n"], '', $email) . "\r\n" : '');
        @mail($to, '=?UTF-8?B?' . base64_encode('Новая заявка с сайта') . '?=', "Имя: $name\nEmail: $email\nТелефон: $phone\n\n$msg\n", $headers);
    }
    $_SESSION['pflash'] = ['msg' => 'Спасибо! Сообщение отправлено, я отвечу вам в ближайшее время.', 'type' => 'ok'];
    redirect($back . '?sent=1#contact');
}

// ---------- live preview from the editor (unsaved changes live in the admin's session only) ----------
if ($preview && isset($_GET['pvpage'])) {
    $pg = db_row('SELECT * FROM pages WHERE id = ?', [(int)$_GET['pvpage']]);
    if ($pg) {
        $pv = $_SESSION['pv'][(int)$pg['id']] ?? null;
        $blocks = $pv['blocks'] ?? page_blocks($pg);
        if (!empty($pv['title'])) $pg['title'] = $pv['title'];
        $isHome = (int)setting('home_page_id', '0') === (int)$pg['id'];
        render('page', ['page' => $pg, 'blocks' => $blocks, 'blocks_html' => render_blocks($blocks, true), 'is_home' => $isHome, 'meta' => page_meta($pg, ' — ' . setting('site_title'))]);
    }
}

if ($path === 'feed.xml') {
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>' . e(setting('site_title')) . '</title><link>' . e($origin . '/blog') . '</link><description>' . e(setting('site_description') ?: setting('site_title')) . '</description><language>ru</language>';
    foreach (blog_fetch(20) as $po) echo '<item><title>' . e($po['title']) . '</title><link>' . e($origin . post_url($po)) . '</link><guid>' . e($origin . post_url($po)) . '</guid><pubDate>' . date(DATE_RSS, strtotime((string)$po['published_at'])) . '</pubDate><description>' . e(post_excerpt($po)) . '</description></item>';
    echo '</channel></rss>'; exit;
}
if ($path === 'robots.txt') seo_robots_txt($origin);
if ($path === 'sitemap.xml') seo_sitemap($origin);
if ($path === 'llms.txt') seo_llms_txt($origin);
if (preg_match('~^[a-f0-9]{32}\.txt$~', $path) && $path === seo_s('indexnow_key') . '.txt') { header('Content-Type: text/plain; charset=utf-8'); echo substr($path, 0, 32); exit; }

// ---------- home ----------
if ($path === '') {
    $homeId = (int)setting('home_page_id', '0');
    $pg = $homeId ? db_row('SELECT * FROM pages WHERE id = ?' . ($preview ? '' : ' AND is_published = 1'), [$homeId]) : null;
    if ($pg) {
        $m = page_meta($pg); $m['title'] = $pg['meta_title'] ?: (seo_s('home_title') ?: setting('site_title', 'Фотограф'));
        $m['description'] = $m['description'] ?: setting('site_description');
        render('page', ['page' => $pg, 'blocks' => page_blocks($pg), 'blocks_html' => render_blocks(page_blocks($pg), $preview), 'is_home' => true, 'meta' => $m]);
    }
    $galleries = db_all('SELECT g.*, (SELECT thumb FROM photos p WHERE p.id = g.cover_id) AS cover FROM galleries g WHERE g.is_published = 1 ORDER BY g.sort, g.id');
    $first = $galleries[0] ?? null;
    render('home', ['galleries' => $galleries, 'meta' => [
        'title' => seo_s('home_title') ?: setting('site_title', 'Фотограф'), 'description' => setting('site_description'),
        'image' => ($first && $first['cover']) ? $origin . upload_url((int)$first['id'], $first['cover']) : '',
    ]]);
}

// ---------- blog ----------
if ($path === 'blog' || preg_match('~^blog/category/([a-z0-9-]+)$~', $path, $mc)) {
    $cat = isset($mc[1]) ? db_row('SELECT * FROM categories WHERE slug = ?', [$mc[1]]) : null;
    if (!isset($mc[1]) || $cat) {
        $bo = blog_opts(); $per = max(4, min(24, (int)$bo['per'])); $cid = (int)($cat['id'] ?? 0);
        $total = blog_count($cid, $preview); $pages = max(1, (int)ceil($total / $per)); $pn = max(1, min($pages, (int)($_GET['p'] ?? 1)));
        $cats = db_all('SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category_id = c.id AND ' . ($preview ? '1=1' : 'p.is_published = 1 AND p.published_at <= NOW()') . ') AS n FROM categories c ORDER BY c.sort, c.id');
        render('blog', ['cat' => $cat, 'cats' => array_values(array_filter($cats, fn($c) => $c['n'] > 0)), 'posts' => blog_fetch($per, $cid, ($pn - 1) * $per, $preview), 'pn' => $pn, 'pages' => $pages, 'meta' => [
            'title' => ($cat ? $cat['title'] . ' — ' : '') . ($bo['title'] ?: 'Блог') . ($pn > 1 ? " — страница $pn" : '') . ' — ' . setting('site_title'), 'description' => $bo['intro'] ?: setting('site_description'),
            'canonical' => $origin . '/' . $path . ($pn > 1 ? '?p=' . $pn : ''),
            'prev' => $pn > 1 ? $origin . '/' . $path . ($pn > 2 ? '?p=' . ($pn - 1) : '') : '', 'next' => $pn < $pages ? $origin . '/' . $path . '?p=' . ($pn + 1) : '',
            'crumbs' => $cat ? [['Главная', '/'], ['Блог', '/blog'], [$cat['title'], '/' . $path]] : [['Главная', '/'], ['Блог', '/blog']]]]);
    }
} elseif (preg_match('~^blog/([a-z0-9-]+)$~', $path, $mp)) {
    $po = db_row('SELECT * FROM posts WHERE slug = ?' . ($preview ? '' : ' AND is_published = 1 AND published_at <= NOW()'), [$mp[1]]);
    if ($po) show_post($po, null);
}

// ---------- portfolio index ----------
if ($path === 'portfolio') {
    $galleries = db_all('SELECT g.*, (SELECT thumb FROM photos p WHERE p.id = g.cover_id) AS cover FROM galleries g WHERE g.is_published = 1 ORDER BY g.sort, g.id');
    render('home', ['galleries' => $galleries, 'meta' => ['title' => 'Портфолио — ' . setting('site_title'), 'description' => setting('site_description'), 'crumbs' => [['Главная', '/'], ['Портфолио', '/portfolio']]]]);
}

// ---------- gallery page ----------
if (preg_match('~^portfolio/([a-z0-9-]+)$~', $path, $m)) {
    $g = db_row('SELECT * FROM galleries WHERE slug = ?' . ($preview ? '' : ' AND is_published = 1'), [$m[1]]);
    if ($g) {
        $photos = db_all('SELECT * FROM photos WHERE gallery_id = ? ORDER BY sort, id', [$g['id']]);
        $cover = db_row('SELECT file FROM photos WHERE id = ?', [$g['cover_id'] ?: 0]);
        $gUrl = $origin . '/portfolio/' . $g['slug'];
        $gDesc = $g['meta_description'] ?: (mb_substr(trim(strip_tags((string)$g['description'])), 0, 160) ?: $g['title'] . ' — фотографии из портфолио. ' . setting('site_title'));
        $gImg = clean_image((string)$g['og_image']) ?: ($cover ? upload_url((int)$g['id'], $cover['file']) : '');
        $ld = ['@type' => 'ImageGallery', '@id' => $gUrl . '#gallery', 'url' => $gUrl, 'name' => $g['title'], 'description' => $gDesc, 'inLanguage' => 'ru',
            'isPartOf' => ['@id' => $origin . '/#website'], 'publisher' => ['@id' => $origin . '/#business'],
            'image' => array_map(fn($p) => array_filter(['@type' => 'ImageObject', 'contentUrl' => $origin . upload_url((int)$g['id'], $p['file']), 'caption' => $p['alt'] ?: null, 'width' => $p['width'] ?: null, 'height' => $p['height'] ?: null]), array_slice($photos, 0, 20))];
        render('gallery', ['gallery' => $g, 'photos' => $photos, 'meta' => [
            'title' => ($g['meta_title'] ?: $g['title']) . ' — ' . setting('site_title'), 'description' => $gDesc,
            'image' => $gImg ? $origin . $gImg : '', 'schema' => [$ld],
            'crumbs' => [['Главная', '/'], ['Портфолио', '/portfolio'], [$g['title'], '/portfolio/' . $g['slug']]],
        ] + seo_item_meta($g, '/portfolio/' . $g['slug'])]);
    }
// ---------- ordinary page ----------
} elseif (preg_match('~^[a-z0-9-]+$~', $path)) {
    $pg = db_row('SELECT * FROM pages WHERE slug = ?' . ($preview ? '' : ' AND is_published = 1'), [$path]);
    if ($pg) {
        if ((int)setting('home_page_id', '0') === (int)$pg['id'] && !$preview) redirect('/');
        render('page', ['page' => $pg, 'blocks' => page_blocks($pg), 'blocks_html' => render_blocks(page_blocks($pg), $preview), 'is_home' => false, 'meta' => page_meta($pg, ' — ' . setting('site_title'))]);
    }
}
if (!$preview) seo_log404($path);
render('404', ['meta' => ['title' => 'Страница не найдена — ' . setting('site_title'), 'description' => '']], 404);
