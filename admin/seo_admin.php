<?php
declare(strict_types=1);
// Раздел «SEO»: аудит, настройки, организация (schema.org), аналитика, редиректы и 404, индексация.

const SEO_SPEC = [
    'main' => ['seo_sep' => 'sep', 'seo_brand' => 'text', 'seo_home_title' => 'text', 'seo_base_url' => 'origin', 'seo_og_default' => 'img',
        'seo_site_noindex' => 'bool', 'seo_force_https' => 'bool', 'seo_www' => 'www', 'seo_block_ai' => 'bool', 'seo_llms' => 'bool', 'seo_robots_extra' => 'multi',
        'seo_v_google' => 'verify', 'seo_v_yandex' => 'verify', 'seo_v_bing' => 'verify'],
    'biz' => ['seo_biz_type' => 'biztype', 'seo_biz_name' => 'text', 'seo_biz_logo' => 'img', 'seo_biz_street' => 'text', 'seo_biz_city' => 'text', 'seo_biz_region' => 'text',
        'seo_biz_zip' => 'text', 'seo_biz_country' => 'cc', 'seo_biz_lat' => 'num', 'seo_biz_lng' => 'num', 'seo_biz_price' => 'text', 'seo_biz_area' => 'multi', 'seo_biz_same' => 'urls'],
    'an' => ['an_ga' => 'ga', 'an_gtm' => 'gtm', 'an_ym' => 'ym', 'an_ym_webvisor' => 'bool', 'an_ym_clickmap' => 'bool', 'an_ym_links' => 'bool', 'an_ym_bounce' => 'bool',
        'an_consent' => 'bool', 'an_consent_text' => 'text', 'an_goals' => 'bool', 'an_custom' => 'raw'],
];

function seo_clean(string $type, string $v): string {
    $v = trim($v);
    switch ($type) {
        case 'bool': return $v === '1' ? '1' : '0';
        case 'sep': return in_array($v, ['—', '|', '·', '-', '•', '/'], true) ? $v : '—';
        case 'text': return mb_substr(strip_tags($v), 0, 190);
        case 'multi': return mb_substr(trim(implode("\n", array_filter(array_map('trim', explode("\n", strip_tags($v)))))), 0, 2000);
        case 'origin': $v = rtrim($v, '/'); return preg_match('~^https?://[^/\s]+$~i', $v) ? $v : '';
        case 'img': return clean_image($v);
        case 'www': return in_array($v, ['www', 'bare'], true) ? $v : '';
        case 'ga': case 'gtm': case 'ym': return seo_valid($type, $v);
        case 'biztype': return in_array($v, ['Photographer', 'ProfessionalService', 'LocalBusiness', 'Organization'], true) ? $v : 'Photographer';
        case 'cc': return preg_match('~^[A-Za-z]{2}$~', $v) ? strtoupper($v) : '';
        case 'num': return is_numeric($v) ? $v : '';
        case 'urls': return implode("\n", array_filter(array_map('trim', explode("\n", $v)), fn($u) => preg_match('~^https?://\S+$~i', $u)));
        case 'verify':
            if (preg_match('~content=["\']([^"\']+)["\']~i', $v, $m)) $v = $m[1];
            return preg_match('~^[A-Za-z0-9_=.\-]{8,100}$~', $v) ? $v : '';
        case 'raw': return mb_substr($v, 0, 20000);
    }
    return '';
}

function seo_admin_actions(string $r): void {
    $back = function (string $t, string $m = '', string $ty = 'ok'): never { if ($m !== '') flash($m, $ty); redirect('/admin/?r=seo&t=' . $t); };
    if ($r === 'seo_save') {
        post_only(); $tab = (string)($_POST['tab'] ?? 'main');
        if (!isset(SEO_SPEC[$tab])) $back('main');
        $bad = [];
        foreach (SEO_SPEC[$tab] as $k => $type) {
            $raw = (string)($_POST[$k] ?? ''); $val = seo_clean($type, $raw);
            if ($raw !== '' && $val === '' && !in_array($type, ['bool', 'www', 'multi', 'urls'], true)) $bad[] = $k;
            set_setting($k, $val);
        }
        $back($tab, $bad ? 'Сохранено, но не принято (неверный формат): ' . implode(', ', $bad) : 'Настройки сохранены', $bad ? 'err' : 'ok');
    }
    if ($r === 'redirect_add') {
        post_only();
        seo_redirect_add((string)($_POST['from'] ?? ''), (string)($_POST['to'] ?? ''), (int)($_POST['code'] ?? 301))
            ? $back('redirects', 'Редирект добавлен') : $back('redirects', 'Проверьте адреса: они не должны совпадать и не должны быть пустыми', 'err');
    }
    if ($r === 'redirect_import') {
        post_only(); $n = 0;
        foreach (preg_split('~\R~', (string)($_POST['list'] ?? '')) as $line) {
            $line = trim($line); if ($line === '' || $line[0] === '#') continue;
            $p = preg_split('~[\s;,]+~', $line, 2); if (count($p) === 2 && seo_redirect_add($p[0], $p[1])) $n++;
        }
        $back('redirects', "Импортировано редиректов: $n");
    }
    if ($r === 'redirect_del') { post_only(); db_exec('DELETE FROM redirects WHERE id = ?', [(int)($_POST['id'] ?? 0)]); $back('redirects', 'Редирект удалён'); }
    if ($r === 'log404_del') { post_only(); db_exec('DELETE FROM log404 WHERE id = ?', [(int)($_POST['id'] ?? 0)]); $back('redirects'); }
    if ($r === 'log404_clear') { post_only(); db_exec('DELETE FROM log404'); $back('redirects', 'Журнал 404 очищен'); }
    if ($r === 'indexnow_send') {
        post_only(); $o = seo_origin();
        [$ok, $msg] = seo_indexnow_send($o, array_map(fn($u) => $o . $u[0], seo_urls($o)));
        $back('index', $msg, $ok ? 'ok' : 'err');
    }
}

function seo_admin_view(): void {
    $t = (string)($_GET['t'] ?? 'audit');
    $tabs = ['audit' => 'Аудит', 'main' => 'Основное', 'biz' => 'Организация', 'an' => 'Аналитика', 'redirects' => 'Редиректы и 404', 'index' => 'Индексация'];
    if (!isset($tabs[$t])) $t = 'audit';
    head('SEO', 'seo'); ?>
    <h1>SEO и продвижение</h1>
    <nav class="tabs"><?php foreach ($tabs as $k => $l) echo '<a href="/admin/?r=seo&t=' . $k . '"' . ($t === $k ? ' class="on"' : '') . '>' . $l . '</a>'; ?></nav>
    <?php
    $v = fn(string $k, string $d = '') => e(setting($k, $d));
    $inp = fn(string $k, string $l, string $ph = '', string $hint = '') => '<label>' . $l . '<input name="' . $k . '" value="' . $v($k) . '" placeholder="' . e($ph) . '">' . ($hint ? '<small class="muted">' . $hint . '</small>' : '') . '</label>';
    $txt = fn(string $k, string $l, int $rows = 3, string $ph = '', bool $mono = false) => '<label>' . $l . '<textarea name="' . $k . '" rows="' . $rows . '"' . ($mono ? ' class="mono"' : '') . ' placeholder="' . e($ph) . '">' . $v($k) . '</textarea></label>';
    $chk = fn(string $k, string $l, string $d = '0') => '<label class="check"><input type="checkbox" name="' . $k . '" value="1"' . (setting($k, $d) === '1' ? ' checked' : '') . '> ' . $l . '</label>';
    $form = fn(string $tab) => '<form method="post" action="/admin/?r=seo_save" class="card stack">' . csrf_field() . '<input type="hidden" name="tab" value="' . $tab . '">';
    $origin = seo_origin();

    if ($t === 'audit') {
        $a = seo_audit(); $s = $a['sum']; $id = seo_an_ids();
        $chkl = [
            ['Главный адрес сайта задан (защита canonical)', seo_s('base_url') !== '', 'Основное → «Главный адрес сайта»'],
            ['Сайт открывается по HTTPS', str_starts_with($origin, 'https'), 'Включите HTTPS на хостинге и «Всегда HTTPS» в Основном'],
            ['Сайт открыт для индексации', seo_s('site_noindex') !== '1', 'Выключите «Закрыть сайт от индексации» в Основном'],
            ['Выбрана главная страница', (int)setting('home_page_id', '0') > 0, 'Страницы → отметьте «Главная»'],
            ['Описание сайта заполнено', setting('site_description') !== '', 'Настройки → «Описание для поисковиков»'],
            ['Картинка для соцсетей по умолчанию', clean_image(seo_s('og_default')) !== '', 'Основное → «Изображение по умолчанию»'],
            ['Данные организации (город, телефон)', seo_s('biz_city') !== '' && setting('contact_phone') !== '', 'Организация + Настройки → телефон'],
            ['Подтверждён Google Search Console', seo_s('v_google') !== '', 'Основное → коды подтверждения'],
            ['Подтверждён Яндекс Вебмастер', seo_s('v_yandex') !== '', 'Основное → коды подтверждения'],
            ['Яндекс Метрика подключена', $id['ym'] !== '', 'Аналитика'],
            ['Google Analytics 4 / GTM подключены', $id['ga'] !== '' || $id['gtm'] !== '', 'Аналитика'],
        ]; ?>
        <div class="cols"><div class="card stack"><h2>Чек-лист сайта</h2>
          <ul class="seo-list"><?php foreach ($chkl as [$l, $ok, $h]) echo '<li class="' . ($ok ? 'ok' : 'bad') . '"><span>' . ($ok ? '✓' : '✗') . '</span> ' . e($l) . ($ok ? '' : '<small class="muted"> — ' . e($h) . '</small>') . '</li>'; ?></ul></div>
        <div class="card stack grow-2"><h2>Страницы: <?= $s['n'] ?></h2>
          <p><span class="pill e">Ошибки: <?= $s['e'] ?></span> <span class="pill w">Замечания: <?= $s['w'] ?></span> <span class="pill ok">В порядке: <?= $s['ok'] ?></span></p>
          <p class="muted">Проверяются заголовки, описания, дубли, картинки, объём текста и ключевая фраза. Исправляйте сверху вниз.</p>
          <div class="tblwrap"><table class="tbl"><thead><tr><th>Балл</th><th>Тип</th><th>Страница</th><th>Что исправить</th></tr></thead><tbody>
          <?php foreach ($a['rows'] as $r): ?><tr>
            <td><span class="pill <?= $r['score'] >= 85 ? 'ok' : ($r['score'] >= 60 ? 'w' : 'e') ?>"><?= $r['score'] ?></span></td><td><?= e($r['type']) ?></td>
            <td><a href="<?= e($r['edit']) ?>"><?= e($r['title']) ?></a><div class="muted mono"><?= e($r['url']) ?></div></td>
            <td><?php foreach ($r['issues'] as [$lv, $tx]) echo '<div class="is ' . $lv . '">' . e($tx) . '</div>'; if (!$r['issues']) echo '<span class="muted">Всё хорошо</span>'; ?></td></tr><?php endforeach; ?>
          </tbody></table></div></div></div>
    <?php } elseif ($t === 'main') {
        echo $form('main'); ?>
        <h2>Заголовки</h2>
        <label>Разделитель в title<select name="seo_sep"><?php foreach (['—', '|', '·', '-', '•', '/'] as $s) echo '<option' . (seo_s('sep', '—') === $s ? ' selected' : '') . '>' . e($s) . '</option>'; ?></select></label>
        <?= $inp('seo_brand', 'Бренд в конце заголовка', setting('site_title'), 'Например: «Леонид Древило, фотограф в Мозыре». Пусто — название сайта.') ?>
        <?= $inp('seo_home_title', 'Заголовок главной', 'Свадебный фотограф в Мозыре — Имя', 'Если у главной страницы не задан свой SEO-заголовок.') ?>
        <h2>Адрес и индексация</h2>
        <?= $inp('seo_base_url', 'Главный адрес сайта', 'https://drevilo.com', 'Без слэша в конце. Все canonical, sitemap и og:url строятся от него, даже если открыть сайт по другому домену.') ?>
        <label>Версия домена<select name="seo_www"><option value="">Как есть</option><option value="www"<?= seo_s('www') === 'www' ? ' selected' : '' ?>>Всегда с www</option><option value="bare"<?= seo_s('www') === 'bare' ? ' selected' : '' ?>>Всегда без www</option></select></label>
        <?= $chk('seo_force_https', 'Всегда HTTPS (301 с http). Включайте, когда SSL уже работает — иначе сайт перестанет открываться.') ?>
        <?= $chk('seo_site_noindex', 'Закрыть сайт от индексации (на время разработки; на боевом сайте выключить!)') ?>
        <?= $chk('seo_block_ai', 'Запретить обучающие AI-роботы (GPTBot, CCBot, Google-Extended и др.). Поисковики не затрагиваются.') ?>
        <?= $chk('seo_llms', 'Файл /llms.txt — краткая карта сайта для AI-поиска', '1') ?>
        <?= $inp('seo_og_default', 'Изображение по умолчанию для соцсетей', '/uploads/…', 'Для страниц без своей картинки. Рекомендуется 1200×630.') ?>
        <?= $txt('seo_robots_extra', 'Свои строки для robots.txt', 3, "Disallow: /private/", true) ?>
        <h2>Подтверждение в поисковиках</h2>
        <p class="muted">Вставьте код или целиком мета-тег из Search Console / Яндекс Вебмастера / Bing.</p>
        <?= $inp('seo_v_google', 'Google Search Console') ?><?= $inp('seo_v_yandex', 'Яндекс Вебмастер') ?><?= $inp('seo_v_bing', 'Bing Webmaster') ?>
        <button class="btn primary">Сохранить</button></form>
    <?php } elseif ($t === 'biz') {
        echo $form('biz'); ?>
        <h2>Организация (schema.org)</h2>
        <p class="muted">Эти данные попадают в микроразметку JSON-LD на каждой странице: так Google и Яндекс лучше понимают, кто вы и где работаете, это помогает локальной выдаче и расширенным сниппетам. Телефон, email и Instagram берутся из «Настроек».</p>
        <label>Тип<select name="seo_biz_type"><?php foreach (['Photographer' => 'Фотограф', 'ProfessionalService' => 'Студия / услуги', 'LocalBusiness' => 'Местный бизнес', 'Organization' => 'Организация'] as $k => $l) echo '<option value="' . $k . '"' . (seo_s('biz_type', 'Photographer') === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select></label>
        <?= $inp('seo_biz_name', 'Название', setting('site_title')) ?><?= $inp('seo_biz_logo', 'Логотип / фото', '/uploads/…') ?>
        <?= $inp('seo_biz_city', 'Город', 'Мозырь') ?><?= $inp('seo_biz_region', 'Область / регион', 'Гомельская область') ?>
        <?= $inp('seo_biz_street', 'Улица, дом (если принимаете клиентов)') ?><?= $inp('seo_biz_zip', 'Индекс') ?><?= $inp('seo_biz_country', 'Страна (код)', 'BY') ?>
        <?= $inp('seo_biz_lat', 'Широта', '52.0495') ?><?= $inp('seo_biz_lng', 'Долгота', '29.2456') ?>
        <?= $inp('seo_biz_price', 'Ценовой диапазон', '$$') ?>
        <?= $txt('seo_biz_area', 'Где работаете (по строке)', 4, "Мозырь\nГомель\nМинск") ?>
        <?= $txt('seo_biz_same', 'Профили в соцсетях и каталогах (по строке)', 4, "https://t.me/…\nhttps://vk.com/…", true) ?>
        <button class="btn primary">Сохранить</button></form>
    <?php } elseif ($t === 'an') {
        echo $form('an'); ?>
        <h2>Яндекс Метрика</h2>
        <?= $inp('an_ym', 'Номер счётчика', '12345678') ?>
        <?= $chk('an_ym_clickmap', 'Карта кликов', '1') ?><?= $chk('an_ym_links', 'Отслеживание внешних ссылок', '1') ?><?= $chk('an_ym_bounce', 'Точный показатель отказов', '1') ?>
        <?= $chk('an_ym_webvisor', 'Вебвизор (записи сессий; включайте с согласием посетителей)') ?>
        <h2>Google</h2>
        <?= $inp('an_ga', 'Google Analytics 4 — ID потока', 'G-XXXXXXXXXX') ?>
        <?= $inp('an_gtm', 'Google Tag Manager — ID контейнера', 'GTM-XXXXXXX', 'Если используете GTM, GA4 лучше настроить внутри него и не указывать здесь, чтобы не считать дважды.') ?>
        <h2>Цели и приватность</h2>
        <?= $chk('an_goals', 'Автоцели: отправка формы (form_submit / generate_lead), клик по телефону, email и соцсетям (click_phone, click_email, click_social)', '1') ?>
        <?= $chk('an_consent', 'Запрашивать согласие на cookie (счётчики загружаются только после «Принять»)') ?>
        <?= $inp('an_consent_text', 'Текст баннера', 'Сайт использует cookie и сервисы аналитики, чтобы работать лучше. Разрешить?') ?>
        <?= $txt('an_custom', 'Свой код в &lt;head&gt; (пиксели VK, Meta и т.д.)', 5, '<script>…</script>', true) ?>
        <p class="muted">Администраторы, вошедшие в админку, и предпросмотр в статистику не попадают. Цели в Метрике создайте как «JavaScript-событие» с теми же идентификаторами.</p>
        <button class="btn primary">Сохранить</button></form>
    <?php } elseif ($t === 'redirects') {
        $rd = db_all('SELECT * FROM redirects ORDER BY id DESC LIMIT 500'); $lg = db_all('SELECT * FROM log404 ORDER BY hits DESC, last_at DESC LIMIT 100');
        $pf = (string)($_GET['from'] ?? ''); ?>
        <div class="cols"><div class="stack">
          <form method="post" action="/admin/?r=redirect_add" class="card stack"><?= csrf_field() ?><h2>Новый редирект</h2>
            <label>Откуда<input name="from" value="<?= e($pf) ?>" placeholder="/old-page  или  /old/*" required></label>
            <label>Куда<input name="to" placeholder="/new-page" required></label>
            <label>Тип<select name="code"><option value="301">301 — навсегда</option><option value="302">302 — временно</option></select></label>
            <small class="muted">Адрес со звёздочкой в конце действует на все вложенные: <span class="mono">/old/*</span> → <span class="mono">/blog/*</span>. Можно указывать и старые адреса с параметрами, например <span class="mono">/index.php?id=5</span>.</small>
            <button class="btn primary">Добавить</button></form>
          <form method="post" action="/admin/?r=redirect_import" class="card stack"><?= csrf_field() ?><h2>Массовый импорт</h2>
            <label>По строке: «старый новый»<textarea name="list" rows="6" class="mono" placeholder="/portfolio.html /portfolio&#10;/svadba.html /portfolio/svadba"></textarea></label>
            <small class="muted">Для переезда со старого сайта: перенесите сюда все старые адреса (из Яндекс Вебмастера или Search Console), чтобы позиции не просели. При смене адреса страницы здесь автоматически создаётся 301.</small>
            <button class="btn">Импортировать</button></form></div>
        <div class="stack grow-2"><div class="card stack"><h2>Редиректы (<?= count($rd) ?>)</h2>
          <?php if (!$rd) echo '<p class="muted">Пока нет.</p>'; else { ?><div class="tblwrap"><table class="tbl"><thead><tr><th>Откуда</th><th>Куда</th><th>Код</th><th>Переходов</th><th></th></tr></thead><tbody>
          <?php foreach ($rd as $x): ?><tr><td class="mono"><?= e($x['from_path']) ?></td><td class="mono"><?= e($x['to_url']) ?></td><td><?= (int)$x['code'] ?></td><td><?= (int)$x['hits'] ?></td>
            <td><form method="post" action="/admin/?r=redirect_del"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn danger sm" title="Удалить">✕</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php } ?></div>
        <div class="card stack"><h2>Ошибки 404 (<?= count($lg) ?>)</h2>
          <p class="muted">Адреса, которые искали посетители и роботы, но их нет на сайте. Частые — повод создать редирект.</p>
          <?php if ($lg) { ?><div class="tblwrap"><table class="tbl"><thead><tr><th>Адрес</th><th>Раз</th><th>Откуда пришли</th><th></th></tr></thead><tbody>
          <?php foreach ($lg as $x): ?><tr><td class="mono"><?= e($x['path']) ?></td><td><?= (int)$x['hits'] ?></td><td class="muted"><?= e($x['referrer']) ?></td>
            <td class="nw"><a class="btn sm" href="/admin/?r=seo&t=redirects&from=<?= e(rawurlencode($x['path'])) ?>">Редирект</a>
            <form method="post" action="/admin/?r=log404_del" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn sm">✕</button></form></td></tr><?php endforeach; ?></tbody></table></div>
          <form method="post" action="/admin/?r=log404_clear"><?= csrf_field() ?><button class="btn sm">Очистить журнал</button></form><?php } else echo '<p class="muted">Пока пусто.</p>'; ?></div></div></div>
    <?php } else {
        $urls = seo_urls($origin); $key = seo_indexnow_key(); ?>
        <div class="cols"><div class="card stack"><h2>Файлы для роботов</h2>
          <p><a href="/sitemap.xml" target="_blank" rel="noopener">/sitemap.xml</a> — страниц: <?= count($urls) ?> (с lastmod и фото)<br><a href="/robots.txt" target="_blank" rel="noopener">/robots.txt</a><br><a href="/llms.txt" target="_blank" rel="noopener">/llms.txt</a><br><a href="/feed.xml" target="_blank" rel="noopener">/feed.xml</a> (RSS блога)</p>
          <h2>IndexNow</h2><p class="muted">Мгновенное уведомление Яндекса, Bing и др. о новых и изменённых страницах. Ключ уже размещён по адресу <span class="mono">/<?= e($key) ?>.txt</span>.</p>
          <form method="post" action="/admin/?r=indexnow_send"><?= csrf_field() ?><button class="btn primary">Отправить все адреса (<?= count($urls) ?>)</button></form></div>
        <div class="card stack grow-2"><h2>Что сделать после запуска</h2><ol class="seo-steps">
          <li>В «Основном» укажите главный адрес, включите HTTPS и выключите «Закрыть от индексации».</li>
          <li><b>Яндекс Вебмастер</b>: добавьте сайт, вставьте код в «Основном», добавьте <span class="mono"><?= e($origin) ?>/sitemap.xml</span>, укажите регион (Мозырь) и подключите Метрику. Если домен тот же, что у старого сайта, проверьте «Переезд сайта» и ответы 301 старых адресов.</li>
          <li><b>Google Search Console</b>: добавьте ресурс (домен или префикс URL), подтвердите кодом, отправьте sitemap.xml.</li>
          <li><b>Bing Webmaster</b>: можно импортировать сайт из Search Console в один клик.</li>
          <li>Импортируйте старые адреса во вкладке «Редиректы», затем следите за журналом 404 первые недели.</li>
          <li>Заполните «Организацию», добавьте сайт в Яндекс Бизнес и Google Business Profile с теми же названием, адресом и телефоном.</li>
          <li>Для каждой страницы задайте ключевую фразу и добейтесь зелёных баллов в «Аудите».</li></ol></div></div>
    <?php }
    foot();
}
