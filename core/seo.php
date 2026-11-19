<?php
declare(strict_types=1);
/**
 * SEO-ядро: редиректы, canonical, title-шаблон, JSON-LD, sitemap/robots/llms.txt, IndexNow,
 * аналитика (GA4 / GTM / Яндекс Метрика), аудит. Все настройки — в таблице settings (seo_*, an_*).
 */

const SEO_JSON = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP;

function seo_s(string $k, string $d = ''): string { return setting('seo_' . $k, $d); }
function seo_is_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}
/** Главный адрес сайта: явно заданный в SEO или определённый по запросу. */
function seo_origin(): string {
    $b = rtrim(seo_s('base_url'), '/');
    if (preg_match('~^https?://[^/\s]+$~i', $b)) return $b;
    return (seo_is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
function seo_abs(string $u, string $origin): string { return preg_match('~^https?://~i', $u) ? $u : $origin . '/' . ltrim($u, '/'); }
function seo_go(string $url, int $code = 301): never { header('Location: ' . $url, true, $code); exit; }
function seo_iso(?string $d): string { $t = $d ? strtotime($d) : false; return $t ? date('c', $t) : ''; }

// ---------------------------------------------------------------- редиректы

/** Вызывается в начале index.php: https/www, слэши, index.php, таблица редиректов. */
function seo_early(): void {
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) return;
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $host = strtolower(explode(':', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'))[0]);
    $local = $host === 'localhost' || !str_contains($host, '.') || filter_var($host, FILTER_VALIDATE_IP);
    if (!$local) {
        $newHost = $host; $www = seo_s('www');
        if ($www === 'www' && !str_starts_with($host, 'www.')) $newHost = 'www.' . $host;
        elseif ($www === 'bare' && str_starts_with($host, 'www.')) $newHost = substr($host, 4);
        $needHttps = seo_s('force_https') === '1' && !seo_is_https();
        if ($needHttps || $newHost !== $host) seo_go(($needHttps || seo_is_https() ? 'https' : 'http') . '://' . $newHost . $uri);
    }
    $raw = (string)parse_url($uri, PHP_URL_PATH); $qs = (string)parse_url($uri, PHP_URL_QUERY);
    $norm = (string)preg_replace('~/{2,}~', '/', $raw);
    if (preg_match('~^/index\.(php|html?)$~i', $norm)) $norm = '/';
    if ($norm !== '/') $norm = rtrim($norm, '/');
    if ($norm === '') $norm = '/';
    if ($norm !== $raw) seo_go($norm . ($qs !== '' ? '?' . $qs : ''));
    if (isset($_GET['preview'])) return;

    $dec = rawurldecode($norm);
    $row = null;
    foreach ($qs !== '' ? [$dec . '?' . rawurldecode($qs), $dec] : [$dec] as $c) {
        $row = db_row('SELECT * FROM redirects WHERE from_path = ?', [$c]);
        if ($row) break;
    }
    if (!$row) {
        foreach (db_all("SELECT * FROM redirects WHERE from_path LIKE '%*'") as $w) {
            $pre = rtrim($w['from_path'], '*');
            if ($pre !== '' && str_starts_with($dec, $pre)) { $row = $w; $row['to_url'] = str_replace('*', substr($dec, strlen($pre)), $w['to_url']); break; }
        }
    }
    if ($row && rtrim((string)parse_url($row['to_url'], PHP_URL_PATH), '/') !== rtrim($dec, '/')) {
        db_exec('UPDATE redirects SET hits = hits + 1 WHERE id = ?', [$row['id']]);
        seo_go((string)$row['to_url'], (int)$row['code'] === 302 ? 302 : 301);
    }
}
function seo_redirect_add(string $from, string $to, int $code = 301): bool {
    $from = trim($from); $to = trim($to);
    if (preg_match('~^https?://[^/]+(/.*)?$~i', $from, $m)) $from = $m[1] ?? '/';          // из полного URL берём путь
    $from = '/' . ltrim($from, '/'); if ($from !== '/') $from = rtrim($from, '/');
    if (!preg_match('~^(/|https?://)~', $to)) $to = '/' . ltrim($to, '/');
    if ($from === '/' || $from === $to || strlen($from) > 190 || strlen($to) > 500 || preg_match('~[\s<>"]~', $to)) return false;
    db_exec('INSERT INTO redirects (from_path, to_url, code, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE to_url = VALUES(to_url), code = VALUES(code)', [$from, $to, $code === 302 ? 302 : 301]);
    return true;
}
/** Адрес страницы изменился: ставим 301 со старого и исправляем цепочки. */
function seo_url_changed(string $old, string $new): void {
    if ($old === $new) return;
    db_exec('DELETE FROM redirects WHERE from_path = ?', [$new]);
    db_exec('UPDATE redirects SET to_url = ? WHERE to_url = ?', [$new, $old]);
    seo_redirect_add($old, $new);
}
function seo_log404(string $path): void {
    $p = '/' . $path;
    if (strlen($p) > 190 || preg_match('~\.(php|env|git|sql|bak|asp|aspx)\b|wp-|xmlrpc|\.well-known~i', $p)) return;
    db_exec("INSERT INTO log404 (path, hits, referrer, last_at) VALUES (?, 1, ?, NOW()) ON DUPLICATE KEY UPDATE hits = hits + 1, last_at = NOW(), referrer = IF(VALUES(referrer) <> '', VALUES(referrer), referrer)",
        [$p, mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 190)]);
    if (random_int(1, 50) === 1 && (int)db_value('SELECT COUNT(*) FROM log404') > 500) db_exec('DELETE FROM log404 ORDER BY last_at ASC LIMIT 100');
}

// ---------------------------------------------------------------- title / мета

/** Заменяет « — Название сайта» в конце title на выбранный разделитель и бренд. */
function seo_title(string $t): string {
    $site = setting('site_title', 'Фотограф'); $old = ' — ' . $site;
    if ($site === '' || !str_ends_with($t, $old)) return $t;
    return substr($t, 0, -strlen($old)) . ' ' . seo_s('sep', '—') . ' ' . (trim(seo_s('brand')) ?: $site);
}
/** Доп. мета-поля сущности: noindex и свой canonical. */
function seo_item_meta(array $row, string $selfPath): array {
    $o = [];
    if (!empty($row['noindex'])) $o['robots'] = 'noindex,follow';
    $c = trim((string)($row['canonical'] ?? ''));
    if ($c !== '') $o['canonical'] = seo_abs($c, $GLOBALS['origin']);
    return $o;
}

// ---------------------------------------------------------------- <head>: мета, OG, JSON-LD

function seo_business(string $origin, string $id): array {
    $types = ['Photographer', 'ProfessionalService', 'LocalBusiness', 'Organization'];
    $t = seo_s('biz_type', 'Photographer');
    $n = ['@type' => in_array($t, $types, true) ? $t : 'Photographer', '@id' => $id, 'name' => seo_s('biz_name') ?: setting('site_title'), 'url' => $origin . '/'];
    $img = clean_image(seo_s('biz_logo')) ?: clean_image(seo_s('og_default'));
    if ($img) { $n['logo'] = $origin . $img; $n['image'] = $origin . $img; }
    foreach (['telephone' => 'contact_phone', 'email' => 'contact_email', 'description' => 'site_description'] as $k => $s) if (setting($s) !== '') $n[$k] = setting($s);
    $a = array_filter(['streetAddress' => seo_s('biz_street'), 'addressLocality' => seo_s('biz_city'), 'addressRegion' => seo_s('biz_region'), 'postalCode' => seo_s('biz_zip'), 'addressCountry' => seo_s('biz_country')]);
    if ($a) $n['address'] = ['@type' => 'PostalAddress'] + $a;
    if (is_numeric(seo_s('biz_lat')) && is_numeric(seo_s('biz_lng'))) $n['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float)seo_s('biz_lat'), 'longitude' => (float)seo_s('biz_lng')];
    if (seo_s('biz_price') !== '') $n['priceRange'] = seo_s('biz_price');
    $area = array_values(array_filter(array_map('trim', explode("\n", seo_s('biz_area')))));
    if ($area) $n['areaServed'] = $area;
    $same = array_values(array_filter(array_merge([setting('instagram')], explode("\n", seo_s('biz_same'))), fn($u) => preg_match('~^https?://~i', trim((string)$u))));
    if ($same) $n['sameAs'] = array_values(array_unique(array_map('trim', $same)));
    return $n;
}

function seo_head(array $m, array $site, string $origin): string {
    $esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $title = (string)$m['title']; $desc = (string)($m['description'] ?? ''); $canon = (string)$m['canonical'];
    $robots = (string)($m['robots'] ?? '');
    if (seo_s('site_noindex') === '1' || !empty($GLOBALS['preview'])) $robots = 'noindex,nofollow';
    $img = (string)($m['image'] ?? '');
    if ($img === '' && ($d = clean_image(seo_s('og_default')))) $img = $origin . $d;
    $o = '<meta name="robots" content="' . $esc($robots ?: 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1') . '">' . "\n";
    if ($desc !== '') $o .= '<meta name="description" content="' . $esc($desc) . '">' . "\n";
    $o .= '<link rel="canonical" href="' . $esc($canon) . '">' . "\n";
    foreach (['prev', 'next'] as $rel) if (!empty($m[$rel])) $o .= '<link rel="' . $rel . '" href="' . $esc($m[$rel]) . '">' . "\n";
    $og = ['og:type' => $m['type'] ?? 'website', 'og:title' => $title, 'og:site_name' => $site['title'], 'og:url' => $canon, 'og:locale' => 'ru_RU'];
    if ($desc !== '') $og['og:description'] = $desc;
    if ($img !== '') { $og['og:image'] = $img; $og['og:image:alt'] = $title; }
    if (($m['type'] ?? '') === 'article' && !empty($m['published'])) $og['article:published_time'] = $m['published'];
    foreach ($og as $k => $v) $o .= '<meta property="' . $k . '" content="' . $esc($v) . '">' . "\n";
    $o .= '<meta name="twitter:card" content="' . ($img !== '' ? 'summary_large_image' : 'summary') . '"><meta name="twitter:title" content="' . $esc($title) . '">';
    if ($desc !== '') $o .= '<meta name="twitter:description" content="' . $esc($desc) . '">';
    if ($img !== '') $o .= '<meta name="twitter:image" content="' . $esc($img) . '">';
    $o .= "\n";
    foreach (['google' => 'google-site-verification', 'yandex' => 'yandex-verification', 'bing' => 'msvalidate.01'] as $k => $name)
        if (seo_s('v_' . $k) !== '') $o .= '<meta name="' . $name . '" content="' . $esc(seo_s('v_' . $k)) . '">' . "\n";

    // JSON-LD
    $bizId = $origin . '/#business'; $siteId = $origin . '/#website';
    $g = [seo_business($origin, $bizId),
          ['@type' => 'WebSite', '@id' => $siteId, 'url' => $origin . '/', 'name' => $site['title'], 'inLanguage' => 'ru', 'publisher' => ['@id' => $bizId]]];
    if (!empty($m['schema'])) foreach ($m['schema'] as $node) $g[] = $node;
    else $g[] = array_filter(['@type' => 'WebPage', '@id' => $canon . '#webpage', 'url' => $canon, 'name' => $title, 'description' => $desc ?: null, 'isPartOf' => ['@id' => $siteId], 'primaryImageOfPage' => $img !== '' ? ['@type' => 'ImageObject', 'url' => $img] : null, 'inLanguage' => 'ru']);
    if (!empty($m['crumbs']) && count($m['crumbs']) > 1) {
        $items = []; foreach (array_values($m['crumbs']) as $i => $c) $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $origin . $c[1]];
        $g[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
    $o .= '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $g], SEO_JSON) . '</script>' . "\n";
    return $o . seo_analytics_head();
}

// ---------------------------------------------------------------- аналитика

function seo_valid(string $type, string $v): string {
    $v = trim($v);
    return match ($type) {
        'ga' => preg_match('~^G-[A-Z0-9]{4,15}$~', strtoupper($v)) ? strtoupper($v) : '',
        'gtm' => preg_match('~^GTM-[A-Z0-9]{4,12}$~', strtoupper($v)) ? strtoupper($v) : '',
        'ym' => preg_match('~^\d{5,12}$~', $v) ? $v : '',
        default => '',
    };
}
function seo_an_ids(): array {
    return ['ga' => seo_valid('ga', setting('an_ga')), 'gtm' => seo_valid('gtm', setting('an_gtm')), 'ym' => seo_valid('ym', setting('an_ym'))];
}
function seo_an_active(): bool { return current_user() === null && empty($GLOBALS['preview']); }

const SEO_AN_JS = <<<'JS'
var w=window,d=document,L=false,sent=/[?&]sent=1/.test(location.search);
if(sent){try{history.replaceState(null,'',location.pathname+location.hash)}catch(e){}}
function st(){try{return localStorage.getItem('pcms_consent')}catch(e){return null}}
function custom(){var t=d.getElementById('pcms-an-custom');if(!t)return;[].slice.call(t.content.cloneNode(true).childNodes).forEach(function(x){if(x.nodeName==='SCRIPT'){var s=d.createElement('script');[].forEach.call(x.attributes,function(a){s.setAttribute(a.name,a.value)});s.text=x.text;d.head.appendChild(s)}else d.head.appendChild(x)})}
w.pcmsGoal=function(n,p,g){if(!L)return;try{if(C.ym&&w.ym)ym(C.ym,'reachGoal',n,p)}catch(e){}try{if(w.gtag)gtag('event',g||n,p||{});else if(w.dataLayer)dataLayer.push({event:g||n})}catch(e){}};
function load(){
 if(L)return;L=true;w.dataLayer=w.dataLayer||[];
 if(C.gtm){dataLayer.push({'gtm.start':+new Date(),event:'gtm.js'});var s=d.createElement('script');s.async=1;s.src='https://www.googletagmanager.com/gtm.js?id='+C.gtm;d.head.appendChild(s)}
 if(C.ga){w.gtag=function(){dataLayer.push(arguments)};gtag('js',new Date());gtag('config',C.ga);var g=d.createElement('script');g.async=1;g.src='https://www.googletagmanager.com/gtag/js?id='+C.ga;d.head.appendChild(g)}
 if(C.ym){(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();k=e.createElement(t);a=e.getElementsByTagName(t)[0];k.async=1;k.src=r;a.parentNode.insertBefore(k,a)})(w,d,'script','https://mc.yandex.ru/metrika/tag.js','ym');ym(C.ym,'init',C.yo)}
 custom();
 if(C.goals&&sent)pcmsGoal('form_submit',null,'generate_lead');
}
if(C.goals)d.addEventListener('click',function(e){var a=e.target.closest&&e.target.closest('a[href]');if(!a)return;var h=a.getAttribute('href')||'';if(/^tel:/i.test(h))pcmsGoal('click_phone');else if(/^mailto:/i.test(h))pcmsGoal('click_email');else if(/(instagram\.com|t\.me|wa\.me|vk\.com|facebook\.com)/i.test(h))pcmsGoal('click_social',{url:h});},true);
function banner(){
 var b=d.createElement('div'),s=d.createElement('span');b.setAttribute('role','dialog');
 b.style.cssText='position:fixed;left:16px;right:16px;bottom:16px;max-width:560px;margin:0 auto;z-index:9999;background:#fff;color:#222;border:1px solid rgba(0,0,0,.12);border-radius:14px;padding:14px 16px;font:14px/1.45 system-ui,sans-serif;box-shadow:0 8px 30px rgba(0,0,0,.15);display:flex;gap:10px;align-items:center;flex-wrap:wrap';
 s.style.cssText='flex:1 1 260px';s.textContent=C.txt;b.appendChild(s);
 [['Принять','y'],['Отклонить','n']].forEach(function(x){var u=d.createElement('button');u.type='button';u.textContent=x[0];u.style.cssText='font:inherit;padding:7px 14px;border-radius:99px;cursor:pointer;border:1px solid #222;'+(x[1]==='y'?'background:#222;color:#fff':'background:#fff;color:#222');u.onclick=function(){try{localStorage.setItem('pcms_consent',x[1])}catch(e){}b.remove();if(x[1]==='y')load()};b.appendChild(u)});
 d.body.appendChild(b)}
var s0=st();
if(!C.consent||s0==='y')load();
else if(s0===null)d.readyState==='loading'?d.addEventListener('DOMContentLoaded',banner):banner();
JS;

function seo_analytics_head(): string {
    if (!seo_an_active()) return '';
    $id = seo_an_ids(); $custom = trim(setting('an_custom'));
    if (!$id['ga'] && !$id['gtm'] && !$id['ym'] && $custom === '') return '';
    $consent = setting('an_consent') === '1';
    $cfg = ['ga' => $id['ga'], 'gtm' => $id['gtm'], 'ym' => $id['ym'] !== '' ? (int)$id['ym'] : 0,
        'yo' => ['clickmap' => setting('an_ym_clickmap', '1') === '1', 'trackLinks' => setting('an_ym_links', '1') === '1',
                 'accurateTrackBounce' => setting('an_ym_bounce', '1') === '1', 'webvisor' => setting('an_ym_webvisor', '0') === '1'],
        'consent' => $consent, 'goals' => setting('an_goals', '1') === '1',
        'txt' => setting('an_consent_text') ?: 'Сайт использует cookie и сервисы аналитики, чтобы работать лучше. Разрешить?'];
    $o = '<script>(function(){var C=' . json_encode($cfg, SEO_JSON) . ';' . SEO_AN_JS . '})();</script>' . "\n";
    if ($custom !== '') $o .= $consent ? '<template id="pcms-an-custom">' . $custom . '</template>' . "\n" : $custom . "\n";
    return $o;
}
function seo_analytics_body(): string {
    if (!seo_an_active() || setting('an_consent') === '1') return '';
    $id = seo_an_ids(); $o = '';
    if ($id['gtm']) $o .= '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . $id['gtm'] . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';
    if ($id['ym']) $o .= '<noscript><div><img src="https://mc.yandex.ru/watch/' . $id['ym'] . '" style="position:absolute;left:-9999px" alt=""></div></noscript>';
    return $o;
}

// ---------------------------------------------------------------- sitemap / robots / llms / IndexNow

/** Все индексируемые URL: [loc, lastmod, imgs[], title, type]. */
function seo_urls(string $origin): array {
    if (seo_s('site_noindex') === '1') return [];
    $out = []; $latest = '';
    $keep = function (array $r, string $own) use ($origin): bool {
        if (!empty($r['noindex'])) return false;
        $c = trim((string)($r['canonical'] ?? '')); return $c === '' || rtrim(seo_abs($c, $origin), '/') === rtrim($origin . $own, '/');
    };
    $bump = function (string $d) use (&$latest) { if ($d > $latest) $latest = $d; };
    $home = (int)setting('home_page_id', '0');
    $hp = $home ? db_row('SELECT updated_at, created_at, cover FROM pages WHERE id = ? AND is_published = 1', [$home]) : null;
    $items = [];
    foreach (db_all('SELECT * FROM pages WHERE is_published = 1 AND id <> ? ORDER BY sort, id', [$home]) as $p) {
        if (!$keep($p, '/' . $p['slug'])) continue;
        $mod = (string)($p['updated_at'] ?: $p['created_at']); $bump($mod);
        $items[] = ['/' . $p['slug'], $mod, $p['cover'] ? [clean_image((string)$p['cover'])] : [], $p['meta_title'] ?: $p['title'], 'page'];
    }
    $gal = [];
    foreach (db_all('SELECT g.*, GREATEST(COALESCE(g.updated_at, g.created_at), COALESCE((SELECT MAX(p.created_at) FROM photos p WHERE p.gallery_id = g.id), g.created_at)) AS lm FROM galleries g WHERE g.is_published = 1 ORDER BY g.sort, g.id') as $g) {
        if (!$keep($g, '/portfolio/' . $g['slug'])) continue;
        $imgs = array_map(fn($p) => upload_url((int)$g['id'], $p['file']), db_all('SELECT file FROM photos WHERE gallery_id = ? ORDER BY sort, id LIMIT 50', [$g['id']]));
        $bump((string)$g['lm']);
        $gal[] = ['/portfolio/' . $g['slug'], (string)$g['lm'], $imgs, $g['meta_title'] ?: $g['title'], 'gallery'];
    }
    $posts = [];
    foreach (db_all('SELECT * FROM posts WHERE is_published = 1 AND published_at <= NOW() ORDER BY published_at DESC, id DESC LIMIT 5000') as $p) {
        if (!$keep($p, '/blog/' . $p['slug'])) continue;
        $mod = (string)($p['updated_at'] ?: $p['published_at']); $bump($mod);
        $posts[] = ['/blog/' . $p['slug'], $mod, $p['cover'] ? [clean_image((string)$p['cover'])] : [], $p['meta_title'] ?: $p['title'], 'post'];
    }
    $out[] = ['/', $latest ?: ($hp['updated_at'] ?? ''), $hp && $hp['cover'] ? [clean_image((string)$hp['cover'])] : [], setting('site_title'), 'home'];
    if ($gal) $out[] = ['/portfolio', $latest, [], 'Портфолио', 'index'];
    foreach ($gal as $x) $out[] = $x;
    foreach ($items as $x) $out[] = $x;
    if ($posts) {
        $out[] = ['/blog', $posts[0][1], [], 'Блог', 'index'];
        foreach (db_all('SELECT c.slug, (SELECT MAX(p.published_at) FROM posts p WHERE p.category_id = c.id AND p.is_published = 1 AND p.published_at <= NOW()) AS lm FROM categories c ORDER BY c.sort, c.id') as $c)
            if ($c['lm']) $out[] = ['/blog/category/' . $c['slug'], (string)$c['lm'], [], 'Рубрика', 'index'];
        foreach ($posts as $x) $out[] = $x;
    }
    return $out;
}
function seo_sitemap(string $origin): never {
    header('Content-Type: application/xml; charset=utf-8');
    $x = fn($s) => htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
    foreach (seo_urls($origin) as [$loc, $mod, $imgs]) {
        echo '<url><loc>' . $x($origin . ($loc === '/' ? '/' : $loc)) . '</loc>' . ($mod ? '<lastmod>' . $x(seo_iso($mod)) . '</lastmod>' : '');
        foreach (array_filter($imgs) as $im) echo '<image:image><image:loc>' . $x($origin . $im) . '</image:loc></image:image>';
        echo '</url>';
    }
    echo '</urlset>'; exit;
}
function seo_robots_txt(string $origin): never {
    header('Content-Type: text/plain; charset=utf-8');
    if (seo_s('site_noindex') === '1') { echo "User-agent: *\nDisallow: /\n"; exit; }
    echo "User-agent: *\nDisallow: /admin/\nDisallow: /install.php\nDisallow: /*?preview=\nDisallow: /*?pvd=\nDisallow: /*?pvpage=\nDisallow: /*?pvpost=\nAllow: /uploads/\nAllow: /themes/\n";
    echo "Clean-param: sent&preview&pvd&pvpage&pvpost\n";
    $extra = trim(strip_tags(seo_s('robots_extra'))); if ($extra !== '') echo $extra . "\n";
    if (seo_s('block_ai') === '1') foreach (['GPTBot', 'CCBot', 'Google-Extended', 'Applebot-Extended', 'ClaudeBot', 'anthropic-ai', 'Bytespider', 'meta-externalagent'] as $b) echo "\nUser-agent: $b\nDisallow: /\n";
    echo "\nSitemap: $origin/sitemap.xml\n"; exit;
}
function seo_llms_txt(string $origin): never {
    header('Content-Type: text/plain; charset=utf-8');
    if (seo_s('llms', '1') !== '1' || seo_s('site_noindex') === '1') { http_response_code(404); exit('Not found'); }
    echo '# ' . setting('site_title') . "\n\n" . (setting('site_description') !== '' ? '> ' . setting('site_description') . "\n\n" : '');
    $by = [];
    foreach (seo_urls($origin) as [$loc, , , $t, $type]) $by[$type][] = '- [' . $t . '](' . $origin . $loc . ')';
    foreach (['gallery' => 'Портфолио', 'page' => 'Страницы', 'post' => 'Блог'] as $k => $h) if (!empty($by[$k])) echo "## $h\n" . implode("\n", array_slice($by[$k], 0, 30)) . "\n\n";
    exit;
}
function seo_indexnow_key(): string {
    $k = seo_s('indexnow_key');
    if (!preg_match('~^[a-f0-9]{32}$~', $k)) { $k = bin2hex(random_bytes(16)); set_setting('seo_indexnow_key', $k); }
    return $k;
}
/** @return array{0:bool,1:string} */
function seo_indexnow_send(string $origin, array $urls): array {
    $host = (string)parse_url($origin, PHP_URL_HOST);
    if ($host === '' || $host === 'localhost' || !str_contains($host, '.')) return [false, 'Сайт работает не на публичном домене — отправлять нечего.'];
    if (!$urls) return [false, 'Нет адресов для отправки.'];
    $key = seo_indexnow_key();
    $body = json_encode(['host' => $host, 'key' => $key, 'keyLocation' => "$origin/$key.txt", 'urlList' => array_slice($urls, 0, 10000)], SEO_JSON);
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json; charset=utf-8\r\n", 'content' => $body, 'timeout' => 12, 'ignore_errors' => true]]);
    $res = @file_get_contents('https://api.indexnow.org/indexnow', false, $ctx);
    $code = 0; foreach ($http_response_header ?? [] as $h) if (preg_match('~^HTTP/\S+\s+(\d{3})~', $h, $mm)) $code = (int)$mm[1];
    if ($res === false && $code === 0) return [false, 'Не удалось связаться с IndexNow (проверьте, что на хостинге разрешены исходящие запросы).'];
    return in_array($code, [200, 202], true) ? [true, 'Отправлено адресов: ' . count($urls) . '. Поисковики получат уведомление (Яндекс, Bing и др.).'] : [false, "IndexNow вернул код $code."];
}

// ---------------------------------------------------------------- редактор: поля SEO сущности

function seo_fields(array $row, string $prefix, bool $meta = false, bool $og = false): string {
    $site = setting('site_title');
    $h = '<div class="seo-box" data-prefix="' . e($prefix) . '" data-host="' . e((string)parse_url(seo_origin(), PHP_URL_HOST)) . '" data-tail="' . e(' ' . seo_s('sep', '—') . ' ' . (trim(seo_s('brand')) ?: $site)) . '">';
    $h .= '<label>Ключевая фраза<input name="focus_kw" maxlength="120" value="' . e($row['focus_kw'] ?? '') . '" placeholder="например: свадебный фотограф Мозырь"></label>';
    if ($meta) $h .= '<label>SEO-заголовок<input name="meta_title" maxlength="190" value="' . e($row['meta_title'] ?? '') . '"></label>'
        . '<label>SEO-описание<textarea name="meta_description" rows="3" maxlength="300">' . e($row['meta_description'] ?? '') . '</textarea></label>';
    if ($og) $h .= '<label>Изображение для соцсетей / сниппета<input name="og_image" value="' . e($row['og_image'] ?? '') . '" placeholder="/uploads/..."></label>';
    $h .= '<div class="seo-preview"></div><ul class="seo-checks"></ul>';
    $h .= '<label>Canonical (если нужен другой адрес)<input name="canonical" value="' . e($row['canonical'] ?? '') . '" placeholder="по умолчанию — адрес этой страницы"></label>';
    $h .= '<label class="check"><input type="checkbox" name="noindex" value="1"' . (!empty($row['noindex']) ? ' checked' : '') . '> Скрыть от поисковиков (noindex)</label></div>';
    return $h;
}
function seo_save(string $table, int $id, bool $meta = false, bool $og = false): void {
    if (!in_array($table, ['pages', 'posts', 'galleries'], true)) return;
    $can = trim((string)($_POST['canonical'] ?? ''));
    if ($can !== '' && !preg_match('~^(https?://\S+|/\S*)$~i', $can)) $can = '';
    $set = ['noindex=?', 'canonical=?', 'focus_kw=?'];
    $args = [empty($_POST['noindex']) ? 0 : 1, mb_substr($can, 0, 255), mb_substr(trim((string)($_POST['focus_kw'] ?? '')), 0, 120)];
    if ($meta) { $set[] = 'meta_title=?'; $set[] = 'meta_description=?'; $args[] = mb_substr(trim((string)($_POST['meta_title'] ?? '')), 0, 190); $args[] = mb_substr(trim((string)($_POST['meta_description'] ?? '')), 0, 300); }
    if ($og) { $set[] = 'og_image=?'; $args[] = clean_image((string)($_POST['og_image'] ?? '')); }
    $args[] = $id;
    db_exec("UPDATE `$table` SET " . implode(', ', $set) . ' WHERE id = ?', $args);
}

// ---------------------------------------------------------------- аудит

function seo_text_words(?string $blocksJson): int {
    $n = 0; $d = json_decode((string)$blocksJson, true);
    if (is_array($d)) array_walk_recursive($d, function ($v) use (&$n) {
        if (is_string($v) && strlen($v) > 3 && $v[0] !== '/' && !preg_match('~^(https?:|#)~', $v)) $n += preg_match_all('~\p{L}{2,}~u', strip_tags($v));
    });
    return $n;
}
/** @return array{rows:array,sum:array} */
function seo_audit(): array {
    $rows = [];
    $add = function (string $type, array $r, string $url, string $edit, bool $img, int $words, int $noalt = 0) use (&$rows) {
        $mt = trim((string)($r['meta_title'] ?? '')); $md = trim((string)($r['meta_description'] ?? '')); $eff = $mt ?: (string)$r['title'];
        $rows[] = ['type' => $type, 'title' => $r['title'], 'eff' => $eff, 'md' => $md, 'url' => $url, 'edit' => $edit, 'img' => $img, 'words' => $words, 'noalt' => $noalt,
                   'noindex' => !empty($r['noindex']), 'kw' => trim((string)($r['focus_kw'] ?? '')), 'slug' => (string)$r['slug'], 'mt' => $mt];
    };
    foreach (db_all('SELECT * FROM pages WHERE is_published = 1 ORDER BY sort, id') as $p) {
        $has = $p['cover'] !== '' || $p['og_image'] !== ''; $b = json_decode((string)$p['blocks'], true) ?: [];
        if (!$has) foreach ($b as $x) if (!empty($x['data']['image'])) { $has = true; break; }
        $add('Страница', $p, '/' . $p['slug'], '/admin/?r=page&id=' . $p['id'], $has, seo_text_words($p['blocks']));
    }
    $alts = []; foreach (db_all("SELECT gallery_id, SUM(alt = '') AS n FROM photos GROUP BY gallery_id") as $a) $alts[(int)$a['gallery_id']] = (int)$a['n'];
    foreach (db_all('SELECT * FROM galleries WHERE is_published = 1 ORDER BY sort, id') as $g)
        $add('Галерея', $g, '/portfolio/' . $g['slug'], '/admin/?r=gallery&id=' . $g['id'], (bool)$g['cover_id'], preg_match_all('~\p{L}{2,}~u', strip_tags((string)$g['description'])), $alts[(int)$g['id']] ?? 0);
    foreach (db_all('SELECT * FROM posts WHERE is_published = 1 ORDER BY published_at DESC') as $p)
        $add('Запись', $p, '/blog/' . $p['slug'], '/admin/?r=post&id=' . $p['id'], $p['cover'] !== '' || $p['og_image'] !== '', seo_text_words($p['blocks']));
    $cnt = fn(string $k) => array_count_values(array_filter(array_map(fn($r) => mb_strtolower($r[$k]), $rows)));
    $dt = $cnt('eff'); $dd = $cnt('md'); $sum = ['e' => 0, 'w' => 0, 'ok' => 0, 'n' => count($rows)];
    foreach ($rows as &$r) {
        $is = [];
        $tl = mb_strlen($r['eff']); $dl = mb_strlen($r['md']);
        if ($r['mt'] === '') $is[] = ['i', 'SEO-заголовок не задан — берётся название'];
        if ($tl > 65) $is[] = ['w', "Заголовок длинный ($tl зн.) — обрежется в выдаче"];
        elseif ($tl < 20) $is[] = ['w', "Заголовок слишком короткий ($tl зн.)"];
        if ($dl === 0) $is[] = ['w', 'Нет SEO-описания'];
        elseif ($dl > 170) $is[] = ['w', "Описание длинное ($dl зн.)"];
        elseif ($dl < 70) $is[] = ['w', "Описание короткое ($dl зн.)"];
        if (($dt[mb_strtolower($r['eff'])] ?? 0) > 1) $is[] = ['e', 'Заголовок повторяется на другой странице'];
        if ($r['md'] !== '' && ($dd[mb_strtolower($r['md'])] ?? 0) > 1) $is[] = ['w', 'Описание повторяется на другой странице'];
        if (!$r['img']) $is[] = ['w', 'Нет изображения для соцсетей и сниппета'];
        if ($r['type'] === 'Запись' && $r['words'] < 300) $is[] = ['w', "Мало текста ({$r['words']} слов, лучше от 300)"];
        elseif ($r['words'] < 100) $is[] = ['i', "Мало текста ({$r['words']} слов)"];
        if ($r['noalt'] > 0) $is[] = ['i', "Фото без подписи (alt): {$r['noalt']}"];
        if ($r['kw'] !== '') {
            if (mb_stripos($r['eff'], $r['kw']) === false) $is[] = ['w', 'Ключевой фразы нет в заголовке'];
            if (mb_stripos($r['md'], $r['kw']) === false) $is[] = ['w', 'Ключевой фразы нет в описании'];
        }
        if ($r['noindex']) $is[] = ['i', 'Скрыта от поисковиков (noindex)'];
        $r['issues'] = $is; $r['score'] = max(0, 100 - 20 * count(array_filter($is, fn($x) => $x[0] === 'e')) - 7 * count(array_filter($is, fn($x) => $x[0] === 'w')));
        $sum[array_filter($is, fn($x) => $x[0] === 'e') ? 'e' : (array_filter($is, fn($x) => $x[0] === 'w') ? 'w' : 'ok')]++;
    }
    unset($r);
    usort($rows, fn($a, $b) => $a['score'] <=> $b['score']);
    return ['rows' => $rows, 'sum' => $sum];
}
