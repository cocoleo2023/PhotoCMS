<?php
declare(strict_types=1);
require dirname(__DIR__) . '/core/bootstrap.php';
require_belarus_admin();

const LAYOUTS = GALLERY_MODES;

function json_out(array $d, int $code = 200): never {
    http_response_code($code); header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE); exit;
}
function post_only(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; } csrf_check(); }

function admin_field(array $f, $val, string $name): string {
    $l = e($f['l']);
    if ($f['t'] === 'range') {
        $v = (int)($val ?? $f['def']);
        return '<label class="rng"><span>' . $l . ' <output>' . $v . e($f['unit']) . '</output></span><input type="range" name="' . e($name) . '" min="' . $f['min'] . '" max="' . $f['max'] . '" step="' . $f['step'] . '" value="' . $v . '" oninput="this.previousElementSibling.querySelector(\'output\').textContent=this.value+\'' . e($f['unit']) . '\'"></label>';
    }
    if ($f['t'] === 'color') return '<label class="clr"><span>' . $l . '</span><input type="color" name="' . e($name) . '" value="' . e((string)($val ?: $f['def'])) . '"></label>';
    if ($f['t'] === 'string') return '<label>' . $l . '<input name="' . e($name) . '" value="' . e((string)($val ?? $f['def'])) . '" maxlength="200"></label>';
    $o = '';
    foreach ($f['o'] as $k => $lab) $o .= '<option value="' . e((string)$k) . '"' . ((string)($val ?? array_key_first($f['o'])) === (string)$k ? ' selected' : '') . '>' . e($lab) . '</option>';
    return '<label>' . $l . '<select name="' . e($name) . '">' . $o . '</select></label>';
}

function head(string $title, string $active = '', bool $wide = false): void {
    $nav = ['dashboard' => 'Обзор', 'galleries' => 'Галереи', 'pages' => 'Страницы', 'page_categories' => 'Категории', 'posts' => 'Блог', 'design' => 'Дизайн', 'seo' => 'SEO', 'messages' => 'Заявки', 'settings' => 'Настройки'];
    $unread = (int)db_value('SELECT COUNT(*) FROM messages WHERE is_read = 0');
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="robots" content="noindex"><title>' . e($title) . ' — админка</title><link rel="stylesheet" href="/admin/assets/admin.css"></head><body>';
    echo '<header class="top"><a class="brand" href="/admin/">' . e(setting('site_title', 'Сайт')) . '</a><nav>';
    foreach ($nav as $k => $label) echo '<a href="/admin/?r=' . $k . '"' . ($active === $k ? ' class="on"' : '') . '>' . $label . ($k === 'messages' && $unread ? ' <span class="badge">' . $unread . '</span>' : '') . '</a>';
    echo '</nav><span class="grow"></span><a href="/" target="_blank" rel="noopener">Открыть сайт</a>';
    echo '<form method="post" action="/admin/?r=logout">' . csrf_field() . '<button class="link">Выйти</button></form></header><main class="wrap' . ($wide ? ' wide' : '') . '">';
    if ($f = flash()) echo '<div class="flash ' . e($f['type']) . '">' . e($f['msg']) . '</div>';
}
function foot(string $script = ''): void {
    echo '</main>' . ($script ? '<script src="/admin/assets/' . $script . '"></script>' : '') . '<script src="/admin/assets/seo.js"></script></body></html>';
}

$r = $_GET['r'] ?? 'dashboard';

// ---------- login / logout ----------
if ($r === 'login') {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (attempt_login((string)($_POST['email'] ?? ''), (string)($_POST['pass'] ?? ''))) redirect('/admin/');
        $err = 'Неверный email или пароль';
    }
    ?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Вход</title><link rel="stylesheet" href="/admin/assets/admin.css"></head>
    <body class="auth"><main class="card auth-card"><h1>Вход в админку</h1>
    <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="stack"><?= csrf_field() ?>
    <label>Email<input type="email" name="email" required autofocus></label>
    <label>Пароль<input type="password" name="pass" required></label>
    <button class="btn primary">Войти</button></form></main></body></html><?php
    exit;
}
if ($r === 'logout') { post_only(); logout(); redirect('/admin/?r=login'); }

require_login();

// ---------- galleries: actions ----------
if ($r === 'gallery_create') {
    post_only();
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') { flash('Введите название галереи', 'err'); redirect('/admin/?r=galleries'); }
    $id = db_insert('INSERT INTO galleries (title, slug, layout, sort, created_at) VALUES (?, ?, ?, ?, NOW())',
        [$title, unique_slug('galleries', slugify($title)), 'masonry', (int)db_value('SELECT COALESCE(MAX(sort),0)+1 FROM galleries')]);
    redirect('/admin/?r=gallery&id=' . $id);
}
if ($r === 'gallery_save') {
    post_only(); $id = (int)($_POST['id'] ?? 0);
    $g = db_row('SELECT * FROM galleries WHERE id = ?', [$id]) or redirect('/admin/?r=galleries');
    $title = trim((string)($_POST['title'] ?? '')) ?: $g['title'];
    $slug = unique_slug('galleries', slugify(trim((string)($_POST['slug'] ?? '')) ?: $title), $id);
    $layout = array_key_exists($_POST['layout'] ?? '', LAYOUTS) ? $_POST['layout'] : 'masonry';
    $opts = sanitize_fields(gallery_look_fields(), is_array($_POST['opts'] ?? null) ? $_POST['opts'] : []);
    $parent = (int)($_POST['parent_id'] ?? 0);
    if ($parent === $id) $parent = 0;
    db_exec('UPDATE galleries SET title=?, slug=?, description=?, layout=?, opts=?, is_published=?, sort=?, parent_id=? WHERE id=?',
        [$title, $slug, (string)($_POST['description'] ?? ''), $layout, json_encode($opts), empty($_POST['is_published']) ? 0 : 1, (int)($_POST['sort'] ?? 0), $parent ?: null, $id]);
    seo_save('galleries', $id, true, true); seo_url_changed('/portfolio/' . $g['slug'], '/portfolio/' . $slug);
    flash('Галерея сохранена'); redirect('/admin/?r=gallery&id=' . $id);
}
if ($r === 'gallery_delete') {
    post_only(); $id = (int)($_POST['id'] ?? 0);
    foreach (db_all('SELECT * FROM photos WHERE gallery_id = ?', [$id]) as $p) delete_photo_files($id, $p);
    $dir = ROOT . '/uploads/g' . $id; if (is_dir($dir)) @rmdir($dir);
    db_exec('DELETE FROM photos WHERE gallery_id = ?', [$id]); db_exec('DELETE FROM galleries WHERE id = ?', [$id]);
    flash('Галерея удалена'); redirect('/admin/?r=galleries');
}
if ($r === 'gallery_upload') {
    post_only(); $id = (int)($_GET['id'] ?? 0);
    if (!db_row('SELECT id FROM galleries WHERE id = ?', [$id])) json_out(['error' => 'Галерея не найдена'], 404);
    try {
        $res = process_upload($_FILES['photo'] ?? [], $id);
        $sort = (int)db_value('SELECT COALESCE(MAX(sort),0)+1 FROM photos WHERE gallery_id = ?', [$id]);
        $pid = db_insert('INSERT INTO photos (gallery_id, file, thumb, width, height, sort, created_at) VALUES (?,?,?,?,?,?,NOW())',
            [$id, $res['file'], $res['thumb'], $res['width'], $res['height'], $sort]);
        if (!db_value('SELECT cover_id FROM galleries WHERE id = ?', [$id])) db_exec('UPDATE galleries SET cover_id = ? WHERE id = ?', [$pid, $id]);
        json_out(['id' => $pid, 'thumb' => upload_url($id, $res['thumb'])]);
    } catch (Throwable $ex) { json_out(['error' => $ex->getMessage()], 422); }
}
if ($r === 'photo_delete') {
    post_only(); $pid = (int)($_POST['id'] ?? 0);
    $p = db_row('SELECT * FROM photos WHERE id = ?', [$pid]) or json_out(['error' => 'Нет такого фото'], 404);
    delete_photo_files((int)$p['gallery_id'], $p);
    db_exec('DELETE FROM photos WHERE id = ?', [$pid]);
    $g = db_row('SELECT cover_id FROM galleries WHERE id = ?', [$p['gallery_id']]);
    if ($g && (int)$g['cover_id'] === $pid) {
        $next = db_value('SELECT id FROM photos WHERE gallery_id = ? ORDER BY sort LIMIT 1', [$p['gallery_id']]);
        db_exec('UPDATE galleries SET cover_id = ? WHERE id = ?', [$next ?: null, $p['gallery_id']]);
    }
    json_out(['ok' => true]);
}
if ($r === 'photo_alt') {
    post_only();
    db_exec('UPDATE photos SET alt = ? WHERE id = ?', [mb_substr(trim((string)($_POST['alt'] ?? '')), 0, 255), (int)($_POST['id'] ?? 0)]);
    json_out(['ok' => true]);
}
if ($r === 'photo_cover') {
    post_only();
    $p = db_row('SELECT id, gallery_id FROM photos WHERE id = ?', [(int)($_POST['id'] ?? 0)]) or json_out(['error' => 'Нет такого фото'], 404);
    db_exec('UPDATE galleries SET cover_id = ? WHERE id = ?', [$p['id'], $p['gallery_id']]);
    json_out(['ok' => true]);
}
if ($r === 'photos_order') {
    post_only(); $gid = (int)($_POST['gallery_id'] ?? 0);
    $ids = array_values(array_filter(array_map('intval', explode(',', (string)($_POST['ids'] ?? '')))));
    foreach ($ids as $i => $pid) db_exec('UPDATE photos SET sort = ? WHERE id = ? AND gallery_id = ?', [$i + 1, $pid, $gid]);
    json_out(['ok' => true]);
}

if ($r === 'media_upload') {
    post_only();
    try { $res = process_upload($_FILES['photo'] ?? [], 0); json_out(['url' => upload_url(0, $res['file'])]); }
    catch (Throwable $ex) { json_out(['error' => $ex->getMessage()], 422); }
}
if ($r === 'message_read') { post_only(); db_exec('UPDATE messages SET is_read = 1 WHERE id = ?', [(int)($_POST['id'] ?? 0)]); redirect('/admin/?r=messages'); }
if ($r === 'message_delete') { post_only(); db_exec('DELETE FROM messages WHERE id = ?', [(int)($_POST['id'] ?? 0)]); flash('Заявка удалена'); redirect('/admin/?r=messages'); }

if ($r === 'page_preview') {
    post_only(); $pid = (int)($_POST['id'] ?? 0);
    if (!db_row('SELECT id FROM pages WHERE id = ?', [$pid])) json_out(['error' => 'Страница не найдена'], 404);
    $_SESSION['pv'][$pid] = ['blocks' => sanitize_blocks((string)($_POST['blocks'] ?? '[]')), 'title' => mb_substr(trim((string)($_POST['title'] ?? '')), 0, 190)];
    json_out(['ok' => true]);
}

// ---------- pages: actions ----------
if ($r === 'page_create') {
    post_only();
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') { flash('Введите название страницы', 'err'); redirect('/admin/?r=pages'); }
    $id = db_insert('INSERT INTO pages (title, slug, sort, created_at) VALUES (?, ?, ?, NOW())',
        [$title, unique_slug('pages', slugify($title)), (int)db_value('SELECT COALESCE(MAX(sort),0)+1 FROM pages')]);
    redirect('/admin/?r=page&id=' . $id);
}
if ($r === 'page_save') {
    post_only(); $id = (int)($_POST['id'] ?? 0);
    $pg = db_row('SELECT * FROM pages WHERE id = ?', [$id]) or redirect('/admin/?r=pages');
    $title = trim((string)($_POST['title'] ?? '')) ?: $pg['title'];
    $slug = unique_slug('pages', slugify(trim((string)($_POST['slug'] ?? '')) ?: $title), $id);
    if (in_array($slug, ['admin', 'portfolio', 'blog', 'uploads', 'themes', 'core', 'sitemap-xml', 'robots-txt', 'feed-xml', 'contact'], true)) $slug .= '-page';
    $blocks = sanitize_blocks((string)($_POST['blocks'] ?? '[]'));
    $parent = (int)($_POST['parent_id'] ?? 0);
    if ($parent === $id) $parent = 0;
    $menuParent = (int)($_POST['menu_parent'] ?? 0);
    if ($menuParent === $id) $menuParent = 0;
    $og = clean_image((string)($_POST['og_image'] ?? ''));
    $cover = clean_image((string)($_POST['cover'] ?? ''));
    $catId = (int)($_POST['category_id'] ?? 0) ?: null;
    db_exec('UPDATE pages SET title=?, slug=?, content=?, blocks=?, meta_title=?, meta_description=?, og_image=?, cover=?, category_id=?, is_published=?, show_in_menu=?, sort=?, parent_id=?, menu_parent=? WHERE id=?',
        [$title, $slug, '', json_encode($blocks, JSON_UNESCAPED_UNICODE), mb_substr(trim((string)($_POST['meta_title'] ?? '')), 0, 190),
         mb_substr(trim((string)($_POST['meta_description'] ?? '')), 0, 300), $og ?: '', $cover ?: '', $catId,
         empty($_POST['is_published']) ? 0 : 1, empty($_POST['show_in_menu']) ? 0 : 1, (int)($_POST['sort'] ?? 0),
         $parent ?: null, $menuParent ?: null, $id]);
    seo_save('pages', $id); seo_url_changed('/' . $pg['slug'], '/' . $slug);
    if (!empty($_POST['is_home'])) set_setting('home_page_id', (string)$id);
    elseif ((int)setting('home_page_id', '0') === $id) set_setting('home_page_id', '0');
    flash('Страница сохранена'); redirect('/admin/?r=page&id=' . $id);
}
if ($r === 'page_delete') {
    post_only(); db_exec('DELETE FROM pages WHERE id = ?', [(int)($_POST['id'] ?? 0)]);
    flash('Страница удалена'); redirect('/admin/?r=pages');
}

// ---------- design: actions ----------
if ($r === 'design_save') {
    post_only();
    foreach (sanitize_fields(array_values(design_flat()), $_POST) as $k => $v) set_setting($k, (string)$v);
    unset($_SESSION['pv_design']); flash('Дизайн сохранён'); redirect('/admin/?r=design');
}
if ($r === 'design_preview') {
    post_only();
    $_SESSION['pv_design'] = array_map('strval', sanitize_fields(array_values(design_flat()), $_POST));
    json_out(['ok' => true]);
}
if ($r === 'design_reset') {
    post_only();
    foreach (array_keys(design_flat()) as $k) db_exec('DELETE FROM settings WHERE k = ?', [$k]);
    unset($_SESSION['pv_design']); flash('Дизайн сброшен к стандартному'); redirect('/admin/?r=design');
}

// ---------- blog: actions ----------
if ($r === 'cat_create') {
    post_only(); $t = trim((string)($_POST['title'] ?? ''));
    if ($t !== '') db_insert('INSERT INTO categories (title, slug, sort) VALUES (?, ?, ?)', [mb_substr($t, 0, 190), unique_slug('categories', slugify($t)), (int)db_value('SELECT COALESCE(MAX(sort),0)+1 FROM categories')]);
    redirect('/admin/?r=posts');
}
if ($r === 'cat_delete') {
    post_only(); $cid = (int)($_POST['id'] ?? 0);
    db_exec('UPDATE posts SET category_id = NULL WHERE category_id = ?', [$cid]); db_exec('DELETE FROM categories WHERE id = ?', [$cid]);
    flash('Рубрика удалена'); redirect('/admin/?r=posts');
}
if ($r === 'post_create') {
    post_only(); $t = trim((string)($_POST['title'] ?? ''));
    if ($t === '') { flash('Введите название записи', 'err'); redirect('/admin/?r=posts'); }
    $id = db_insert('INSERT INTO posts (title, slug, blocks, published_at, created_at) VALUES (?, ?, ?, NOW(), NOW())', [mb_substr($t, 0, 190), unique_slug('posts', slugify($t)), '[]']);
    redirect('/admin/?r=post&id=' . $id);
}
if ($r === 'post_save') {
    post_only(); $id = (int)($_POST['id'] ?? 0);
    $po = db_row('SELECT * FROM posts WHERE id = ?', [$id]) or redirect('/admin/?r=posts');
    $title = trim((string)($_POST['title'] ?? '')) ?: $po['title'];
    $slug = unique_slug('posts', slugify(trim((string)($_POST['slug'] ?? '')) ?: $title), $id);
    if (in_array($slug, ['category', 'page'], true)) $slug .= '-post';
    $ts = strtotime(str_replace('T', ' ', (string)($_POST['published_at'] ?? ''))) ?: time();
    $cat = (int)($_POST['category_id'] ?? 0); if ($cat && !db_value('SELECT COUNT(*) FROM categories WHERE id = ?', [$cat])) $cat = 0;
    db_exec('UPDATE posts SET title=?, slug=?, excerpt=?, cover=?, blocks=?, category_id=?, is_published=?, published_at=?, meta_title=?, meta_description=? WHERE id=?',
        [$title, $slug, mb_substr(trim((string)($_POST['excerpt'] ?? '')), 0, 600), clean_image((string)($_POST['cover'] ?? '')), json_encode(sanitize_blocks((string)($_POST['blocks'] ?? '[]')), JSON_UNESCAPED_UNICODE),
         $cat ?: null, empty($_POST['is_published']) ? 0 : 1, date('Y-m-d H:i:s', $ts), mb_substr(trim((string)($_POST['meta_title'] ?? '')), 0, 190), mb_substr(trim((string)($_POST['meta_description'] ?? '')), 0, 300), $id]);
    seo_save('posts', $id, false, true); seo_url_changed('/blog/' . $po['slug'], '/blog/' . $slug);
    flash('Запись сохранена'); redirect('/admin/?r=post&id=' . $id);
}
if ($r === 'post_delete') { post_only(); db_exec('DELETE FROM posts WHERE id = ?', [(int)($_POST['id'] ?? 0)]); flash('Запись удалена'); redirect('/admin/?r=posts'); }
if ($r === 'post_preview') {
    post_only(); $pid = (int)($_POST['id'] ?? 0);
    if (!db_row('SELECT id FROM posts WHERE id = ?', [$pid])) json_out(['error' => 'Запись не найдена'], 404);
    $_SESSION['pvp'][$pid] = ['blocks' => sanitize_blocks((string)($_POST['blocks'] ?? '[]')), 'title' => mb_substr(trim((string)($_POST['title'] ?? '')), 0, 190)];
    json_out(['ok' => true]);
}

// ---------- settings: actions ----------
if ($r === 'page_cat_create') {
    post_only();
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') { flash('Введите название категории', 'err'); redirect('/admin/?r=page_categories'); }
    $parent = (int)($_POST['parent_id'] ?? 0) ?: null;
    db_insert('INSERT INTO page_categories (title, slug, parent_id, sort) VALUES (?,?,?,?)',
        [$title, unique_slug('page_categories', slugify($title)), $parent, (int)db_value('SELECT COALESCE(MAX(sort),0)+1 FROM page_categories')]);
    flash('Категория создана'); redirect('/admin/?r=page_categories');
}
if ($r === 'page_cat_delete') {
    post_only();
    $id = (int)($_POST['id'] ?? 0);
    db_exec('UPDATE pages SET category_id = NULL WHERE category_id = ?', [$id]);
    db_exec('UPDATE page_categories SET parent_id = NULL WHERE parent_id = ?', [$id]);
    db_exec('DELETE FROM page_categories WHERE id = ?', [$id]);
    flash('Категория удалена'); redirect('/admin/?r=page_categories');
}
if ($r === 'settings_save') {
    post_only();
    foreach (['site_title', 'site_description', 'footer_text', 'contact_email', 'contact_phone', 'instagram'] as $k) set_setting($k, trim((string)($_POST[$k] ?? '')));
    // Custom header/footer HTML (trusted admin only)
    set_setting('header_html', (string)($_POST['header_html'] ?? ''));
    set_setting('footer_html', (string)($_POST['footer_html'] ?? ''));
    set_setting('admin_by_only', empty($_POST['admin_by_only']) ? '0' : '1');
    flash('Настройки сохранены'); redirect('/admin/?r=settings');
}
if ($r === 'password_save') {
    post_only(); $u = current_user();
    $row = db_row('SELECT pass_hash FROM users WHERE id = ?', [$u['id']]);
    $new = (string)($_POST['new'] ?? '');
    if (!password_verify((string)($_POST['current'] ?? ''), $row['pass_hash'])) flash('Текущий пароль указан неверно', 'err');
    elseif (mb_strlen($new) < 10) flash('Новый пароль — минимум 10 символов', 'err');
    else { db_exec('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]); flash('Пароль изменён'); }
    redirect('/admin/?r=settings');
}

require __DIR__ . '/seo_admin.php';
seo_admin_actions($r);

// ---------- views ----------
switch ($r) {
case 'galleries':
    head('Галереи', 'galleries');
    $rows = db_all('SELECT g.*, (SELECT COUNT(*) FROM photos p WHERE p.gallery_id = g.id) AS cnt, (SELECT thumb FROM photos p WHERE p.id = g.cover_id) AS cover, (SELECT title FROM galleries p WHERE p.id = g.parent_id) AS parent_title FROM galleries g ORDER BY g.sort, g.id');
    ?><div class="bar"><h1>Галереи</h1>
    <form method="post" action="/admin/?r=gallery_create" class="inline"><?= csrf_field() ?><input name="title" placeholder="Название новой галереи" required><button class="btn primary">Создать</button></form></div>
    <?php if (!$rows): ?><p class="empty">Здесь пока пусто. Создайте первую галерею и загрузите в неё фото.</p><?php endif; ?>
    <div class="tiles"><?php foreach ($rows as $g): ?>
    <a class="tile" href="/admin/?r=gallery&id=<?= (int)$g['id'] ?>">
      <span class="tile-img"><?php if ($g['cover']): ?><img src="<?= e(upload_url((int)$g['id'], $g['cover'])) ?>" alt=""><?php endif; ?></span>
      <strong><?= e($g['title']) ?></strong>
      <small><?= (int)$g['cnt'] ?> фото · <?= $g['is_published'] ? 'опубликована' : 'черновик' ?><?= $g['parent_title'] ? ' · в «' . e($g['parent_title']) . '»' : '' ?></small></a>
    <?php endforeach; ?></div><?php
    foot(); break;

case 'gallery':
    $id = (int)($_GET['id'] ?? 0);
    $g = db_row('SELECT * FROM galleries WHERE id = ?', [$id]) or redirect('/admin/?r=galleries');
    $photos = db_all('SELECT * FROM photos WHERE gallery_id = ? ORDER BY sort, id', [$id]);
    head($g['title'], 'galleries'); ?>
    <p class="crumb"><a href="/admin/?r=galleries">← Все галереи</a></p>
    <div class="cols">
    <form method="post" action="/admin/?r=gallery_save" class="card stack side"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <h2>Параметры</h2>
      <label>Название<input name="title" value="<?= e($g['title']) ?>" required></label>
      <label>Адрес (slug)<input name="slug" value="<?= e($g['slug']) ?>"><small class="muted">/portfolio/<?= e($g['slug']) ?></small></label>
      <label>Описание<textarea name="description" rows="4"><?= e($g['description']) ?></textarea></label>
      <?= seo_fields($g, '/portfolio/', true, true) ?>
      <label>Вид галереи<select name="layout"><?php foreach (LAYOUTS as $k => $l): ?><option value="<?= $k ?>"<?= $g['layout'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Порядок в списке<input type="number" name="sort" value="<?= (int)$g['sort'] ?>"></label>
      <label>Родительская галерея
        <select name="parent_id">
          <option value="0">— Верхний уровень —</option>
          <?php foreach (db_all('SELECT id, title FROM galleries WHERE id <> ? ORDER BY sort, id', [$id]) as $og): ?>
            <option value="<?= (int)$og['id'] ?>"<?= (int)($g['parent_id'] ?? 0) === (int)$og['id'] ? ' selected' : '' ?>><?= e($og['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <h2 class="sec">Внешний вид галереи</h2>
      <?php $go = gallery_opts($g); foreach (gallery_look_fields() as $lf) echo admin_field($lf, $go[$lf['k']] ?? null, 'opts[' . $lf['k'] . ']'); ?>
      <label class="check"><input type="checkbox" name="is_published" value="1"<?= $g['is_published'] ? ' checked' : '' ?>> Показывать на сайте</label>
      <button class="btn primary">Сохранить</button>
    </form>
    <section class="main">
      <div id="drop" class="drop" data-upload="/admin/?r=gallery_upload&id=<?= $id ?>" data-csrf="<?= e(csrf_token()) ?>">
        <p><strong>Перетащите фото сюда</strong> или <label class="pick">выберите файлы<input type="file" id="file" accept="image/jpeg,image/png,image/webp" multiple hidden></label></p>
        <p class="muted">JPG, PNG, WebP до 40 МБ. Превью и оптимизация создаются автоматически.</p>
        <div id="progress" class="progress" hidden><div></div><span></span></div>
      </div>
      <p class="muted hint">Порядок меняется перетаскиванием. Звезда задаёт обложку галереи.</p>
      <ul id="photos" class="photos" data-gallery="<?= $id ?>" data-cover="<?= (int)$g['cover_id'] ?>">
      <?php foreach ($photos as $p): ?>
        <li draggable="true" data-id="<?= (int)$p['id'] ?>"<?= (int)$g['cover_id'] === (int)$p['id'] ? ' class="is-cover"' : '' ?>>
          <img src="<?= e(upload_url($id, $p['thumb'])) ?>" alt="<?= e($p['alt']) ?>" loading="lazy">
          <div class="ph-tools"><button type="button" data-act="cover" title="Сделать обложкой">★</button><button type="button" data-act="del" title="Удалить">✕</button></div>
          <input class="alt" value="<?= e($p['alt']) ?>" placeholder="Описание фото для SEO">
        </li>
      <?php endforeach; ?></ul>
      <form method="post" action="/admin/?r=gallery_delete" onsubmit="return confirm('Удалить галерею вместе со всеми фото?')" class="danger-zone"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn danger">Удалить галерею</button></form>
    </section></div><?php
    foot('gallery.js'); break;

case 'pages':
    head('Страницы', 'pages');
    $rows = db_all('SELECT * FROM pages ORDER BY sort, id'); ?>
    <div class="bar"><h1>Страницы</h1>
    <form method="post" action="/admin/?r=page_create" class="inline"><?= csrf_field() ?><input name="title" placeholder="Например: Обо мне" required><button class="btn primary">Создать</button></form></div>
    <?php if (!$rows): ?><p class="empty">Страниц пока нет. Начните с «Обо мне», «Цены» и «Контакты».</p><?php endif; ?>
    <table class="list"><?php foreach ($rows as $pg): ?>
      <tr><td><a href="/admin/?r=page&id=<?= (int)$pg['id'] ?>"><?= e($pg['title']) ?></a></td><td class="muted">/<?= e($pg['slug']) ?></td><td><?= $pg['is_published'] ? 'опубликована' : 'черновик' ?></td></tr>
    <?php endforeach; ?></table><?php
    foot(); break;

case 'page':
    $id = (int)($_GET['id'] ?? 0);
    $pg = db_row('SELECT * FROM pages WHERE id = ?', [$id]) or redirect('/admin/?r=pages');
    $allPages = db_all('SELECT id, title, parent_id FROM pages WHERE id <> ? ORDER BY sort, id', [$id]);
    head($pg['title'], 'pages'); ?>
    <p class="crumb"><a href="/admin/?r=pages">← Все страницы</a></p>
    <form method="post" action="/admin/?r=page_save" class="cols"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <div class="grow-2 stack">
        <div class="card"><label>Название страницы<input name="title" value="<?= e($pg['title']) ?>" required></label></div>
        <input type="hidden" name="blocks" id="blocks-input">
        <div id="blocks-editor"></div>
      </div>
      <aside class="card stack side">
        <h2>Публикация и SEO</h2>
        <label>Адрес (slug)<input name="slug" value="<?= e($pg['slug']) ?>"><small class="muted">/<?= e($pg['slug']) ?></small></label>
        <label>SEO-заголовок<input name="meta_title" value="<?= e($pg['meta_title']) ?>" maxlength="190"></label>
        <label>SEO-описание<textarea name="meta_description" rows="3" maxlength="300"><?= e($pg['meta_description']) ?></textarea></label>
        <?= seo_fields($pg, '/') ?>
        <label>Open Graph изображение (URL или путь)<input name="og_image" value="<?= e($pg['og_image'] ?? '') ?>" placeholder="/uploads/..."></label>
        <label>Основное фото страницы (обложка)<input name="cover" value="<?= e($pg['cover'] ?? '') ?>" placeholder="/uploads/..."></label>
        <label>Категория страницы
          <select name="category_id">
            <option value="0">— Без категории —</option>
            <?php foreach (db_all('SELECT id, title, parent_id FROM page_categories ORDER BY sort, id') as $pc): ?>
              <option value="<?= (int)$pc['id'] ?>"<?= (int)($pg['category_id'] ?? 0) === (int)$pc['id'] ? ' selected' : '' ?>><?= $pc['parent_id'] ? '↳ ' : '' ?><?= e($pc['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <h2>Иерархия</h2>
        <label>Родительская страница (структура сайта)
          <select name="parent_id">
            <option value="0">— Верхний уровень —</option>
            <?php foreach ($allPages as $op): ?>
              <option value="<?= (int)$op['id'] ?>"<?= (int)($pg['parent_id'] ?? 0) === (int)$op['id'] ? ' selected' : '' ?>><?= e($op['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Родитель в меню (выпадающее меню)
          <select name="menu_parent">
            <option value="0">— Пункт верхнего уровня —</option>
            <?php foreach ($allPages as $op): ?>
              <option value="<?= (int)$op['id'] ?>"<?= (int)($pg['menu_parent'] ?? 0) === (int)$op['id'] ? ' selected' : '' ?>><?= e($op['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <small class="muted">Если не задан — берётся родительская страница</small>
        </label>
        <label>Порядок в меню<input type="number" name="sort" value="<?= (int)$pg['sort'] ?>"></label>
        <label class="check"><input type="checkbox" name="show_in_menu" value="1"<?= $pg['show_in_menu'] ? ' checked' : '' ?>> Показывать в меню</label>
        <label class="check"><input type="checkbox" name="is_published" value="1"<?= $pg['is_published'] ? ' checked' : '' ?>> Опубликована</label>
        <label class="check"><input type="checkbox" name="is_home" value="1"<?= (int)setting('home_page_id', '0') === $id ? ' checked' : '' ?>> Сделать главной страницей</label>
        <button class="btn primary">Сохранить</button>
        <a class="btn" href="/<?= e($pg['slug']) ?>?preview=1" target="_blank" rel="noopener">Предпросмотр</a>
        <button class="btn danger" formaction="/admin/?r=page_delete" formnovalidate onclick="return confirm('Удалить страницу?')">Удалить</button>
      </aside>
    </form>
    <script>window.PCMS = <?= json_encode(['types' => block_types(), 'blocks' => page_blocks($pg), 'style' => common_style_fields(), 'galleries' => db_all('SELECT id, title FROM galleries ORDER BY sort, id'), 'categories' => db_all('SELECT id, title FROM categories ORDER BY sort, id'), 'csrf' => csrf_token(), 'pageId' => $id, 'previewEndpoint' => 'page_preview', 'previewUrl' => '/?preview=1&pvpage=' . $id], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script><?php
    foot('blocks.js'); break;

case 'page_categories':
    head('Категории страниц', 'page_categories');
    $cats = db_all('SELECT c.*, (SELECT COUNT(*) FROM pages p WHERE p.category_id = c.id) AS cnt, (SELECT title FROM page_categories p WHERE p.id = c.parent_id) AS parent_title FROM page_categories c ORDER BY c.sort, c.id');
    ?>
    <div class="bar"><h1>Категории страниц</h1></div>
    <div class="cols">
    <form method="post" action="/admin/?r=page_cat_create" class="card stack side"><?= csrf_field() ?>
      <h2>Новая категория</h2>
      <label>Название<input name="title" required></label>
      <label>Родительская категория
        <select name="parent_id">
          <option value="0">— Верхний уровень —</option>
          <?php foreach ($cats as $c): if ($c['parent_id']) continue; ?>
            <option value="<?= (int)$c['id'] ?>"><?= e($c['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn primary">Создать</button>
    </form>
    <div class="grow-2">
      <?php if (!$cats): ?><p class="empty">Категорий пока нет.</p><?php else: ?>
      <table class="list">
        <?php foreach ($cats as $c): ?>
        <tr>
          <td><?= $c['parent_id'] ? '↳ ' : '' ?><strong><?= e($c['title']) ?></strong></td>
          <td class="muted">/<?= e($c['slug']) ?></td>
          <td><?= $c['parent_title'] ? 'в «' . e($c['parent_title']) . '»' : 'верхний уровень' ?></td>
          <td><?= (int)$c['cnt'] ?> стр.</td>
          <td>
            <form method="post" action="/admin/?r=page_cat_delete" class="inline" onsubmit="return confirm('Удалить категорию?')"><?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn danger small">Удалить</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php endif; ?>
    </div></div><?php
    foot(); break;

case 'seo':
    seo_admin_view(); break;

case 'settings':
    head('Настройки', 'settings'); ?>
    <h1>Настройки</h1>
    <div class="cols">
    <form method="post" action="/admin/?r=settings_save" class="card stack grow-2"><?= csrf_field() ?>
      <h2>Сайт</h2>
      <label>Название сайта<input name="site_title" value="<?= e(setting('site_title')) ?>"></label>
      <label>Описание для поисковиков<textarea name="site_description" rows="3"><?= e(setting('site_description')) ?></textarea></label>
      <label>Email для связи<input type="email" name="contact_email" value="<?= e(setting('contact_email')) ?>"></label>
      <label>Телефон<input name="contact_phone" value="<?= e(setting('contact_phone')) ?>"></label>
      <label>Instagram (ссылка)<input name="instagram" value="<?= e(setting('instagram')) ?>"></label>
      <label>Текст в подвале<input name="footer_text" value="<?= e(setting('footer_text')) ?>"></label>
      <h2>Шапка и подвал (HTML)</h2>
      <p class="muted">Если заполнено — заменяет стандартную шапку/подвал.. Можно использовать свой HTML и классы темы.</p>
      <label>Свой HTML шапки<textarea name="header_html" rows="5" class="mono" placeholder="Оставьте пустым для стандартной шапки"><?= e(setting('header_html')) ?></textarea></label>
      <label>Свой HTML подвала<textarea name="footer_html" rows="5" class="mono" placeholder="Оставьте пустым для стандартного подвала"><?= e(setting('footer_html')) ?></textarea></label>
      <h2>Безопасность</h2>
      <label class="check"><input type="checkbox" name="admin_by_only" value="1"<?= setting('admin_by_only', '1') === '1' ? ' checked' : '' ?>> Доступ в админку только с IP Беларуси</label>
      <p class="muted">Цвета, шрифты, анимации и оформление блога настраиваются в разделе <a href="/admin/?r=design">«Дизайн»</a>.</p>
      <button class="btn primary">Сохранить</button>
    </form>
    <form method="post" action="/admin/?r=password_save" class="card stack side"><?= csrf_field() ?>
      <h2>Смена пароля</h2>
      <label>Текущий пароль<input type="password" name="current" required autocomplete="current-password"></label>
      <label>Новый пароль<input type="password" name="new" minlength="10" required autocomplete="new-password"></label>
      <button class="btn">Изменить пароль</button>
    </form></div><?php
    foot(); break;

case 'design':
    head('Дизайн', 'design', true);
    $pc = palette_colors();
    $targets = ['/' => 'Главная', '/portfolio' => 'Портфолио', '/blog' => 'Блог'];
    foreach (db_all('SELECT slug, title FROM galleries WHERE is_published = 1 ORDER BY sort LIMIT 4') as $g) $targets['/portfolio/' . $g['slug']] = 'Галерея: ' . $g['title'];
    foreach (db_all('SELECT slug, title FROM posts ORDER BY published_at DESC LIMIT 4') as $po) $targets['/blog/' . $po['slug']] = 'Запись: ' . $po['title'];
    foreach (db_all('SELECT slug, title FROM pages WHERE is_published = 1 AND id <> ? ORDER BY sort LIMIT 6', [(int)setting('home_page_id', '0')]) as $pgx) $targets['/' . $pgx['slug']] = 'Страница: ' . $pgx['title']; ?>
    <div class="dz">
    <form method="post" action="/admin/?r=design_save" id="design-form" class="dz-form"><?= csrf_field() ?>
      <div class="dz-bar"><h1>Дизайн</h1><button class="btn primary">Сохранить</button></div>
      <p class="muted">Изменения сразу видны справа. На сайте они появятся после сохранения.</p>
      <div class="card"><h2>Готовые палитры</h2><div class="sw-grid">
      <?php foreach (palettes() as $pk => $pl): ?><button type="button" class="sw" data-pal="<?= e($pk) ?>" title="<?= e($pl['label']) ?>"><span class="dots"><?php foreach (['bg', 'soft', 'accent', 'ink', 'deep'] as $ck): ?><i style="background:<?= e($pl['c'][$ck]) ?>"></i><?php endforeach; ?></span><span><?= e($pl['label']) ?></span></button><?php endforeach; ?>
      </div></div>
      <?php foreach (design_fields() as $grp => $fields): ?>
      <details class="card dz-g"<?= in_array($grp, ['Палитра', 'Шрифты'], true) ? ' open' : '' ?>><summary><?= e($grp) ?></summary><div class="stack">
        <?php foreach ($fields as $f): $val = dz($f['k']); if ($f['t'] === 'color') $val = $pc[substr($f['k'], 2)] ?? $val; echo admin_field($f, $val, $f['k']); endforeach; ?>
      </div></details>
      <?php endforeach; ?>
      <button class="btn danger" formaction="/admin/?r=design_reset" formnovalidate onclick="return confirm('Сбросить все настройки дизайна к стандартным?')">Сбросить дизайн</button>
    </form>
    <section class="dz-pv" aria-label="Предпросмотр сайта">
      <div class="pv-bar"><select id="dz-target" aria-label="Страница для просмотра"><?php foreach ($targets as $u => $l): ?><option value="<?= e($u) ?>"><?= e($l) ?></option><?php endforeach; ?></select>
        <button type="button" class="btn small" data-w="1440">Компьютер</button><button type="button" class="btn small" data-w="820">Планшет</button><button type="button" class="btn small" data-w="390">Телефон</button><span class="grow"></span><button type="button" class="btn small" id="dz-refresh">↻ Обновить</button></div>
      <div class="pv-stage"><iframe id="dz-frame" title="Предпросмотр"></iframe></div>
    </section></div>
    <script>window.DZ = <?= json_encode(['palettes' => array_map(fn($p) => $p['c'], palettes()), 'csrf' => csrf_token()], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script><?php
    foot('design.js'); break;

case 'posts':
    head('Блог', 'posts');
    $rows = db_all('SELECT p.*, c.title AS cat FROM posts p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.published_at DESC, p.id DESC');
    $cats = db_all('SELECT * FROM categories ORDER BY sort, id'); ?>
    <div class="bar"><h1>Блог</h1>
    <form method="post" action="/admin/?r=post_create" class="inline"><?= csrf_field() ?><input name="title" placeholder="Название новой записи" required><button class="btn primary">Создать</button></form></div>
    <p class="muted">Вид блога (карточки, колонки, показ даты и рубрик) настраивается в разделе <a href="/admin/?r=design">«Дизайн»</a>, группа «Блог». Страница блога: <a href="/blog" target="_blank" rel="noopener">/blog</a>.</p>
    <div class="cols">
    <section class="main">
    <?php if (!$rows): ?><p class="empty">Записей пока нет. Создайте первую: расскажите о съёмке, дайте советы по подготовке к свадьбе или покажите историю одной пары.</p><?php endif; ?>
    <table class="list"><?php foreach ($rows as $po): ?>
      <tr><td><a href="/admin/?r=post&id=<?= (int)$po['id'] ?>"><?= e($po['title']) ?></a></td><td class="muted"><?= e($po['cat'] ?: '—') ?></td><td class="muted"><?= e(ru_date((string)$po['published_at'])) ?></td>
      <td><?= !$po['is_published'] ? 'черновик' : (strtotime((string)$po['published_at']) > time() ? 'по расписанию' : 'опубликована') ?></td></tr>
    <?php endforeach; ?></table></section>
    <aside class="card stack side"><h2>Рубрики</h2>
      <?php foreach ($cats as $c): ?><form method="post" action="/admin/?r=cat_delete" class="row between" onsubmit="return confirm('Удалить рубрику? Записи останутся без рубрики.')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><span><?= e($c['title']) ?></span><button class="link">удалить</button></form><?php endforeach; ?>
      <form method="post" action="/admin/?r=cat_create" class="inline"><?= csrf_field() ?><input name="title" placeholder="Новая рубрика" required><button class="btn small">Добавить</button></form>
    </aside></div><?php
    foot(); break;

case 'post':
    $id = (int)($_GET['id'] ?? 0);
    $po = db_row('SELECT * FROM posts WHERE id = ?', [$id]) or redirect('/admin/?r=posts');
    $cats = db_all('SELECT * FROM categories ORDER BY sort, id');
    head($po['title'], 'posts'); ?>
    <p class="crumb"><a href="/admin/?r=posts">← Все записи</a></p>
    <form method="post" action="/admin/?r=post_save" class="cols"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <div class="grow-2 stack">
        <div class="card stack">
          <label>Название записи<input name="title" value="<?= e($po['title']) ?>" required></label>
          <label>Короткий анонс (для карточки и поисковиков)<textarea name="excerpt" rows="3" maxlength="600"><?= e($po['excerpt']) ?></textarea></label>
          <div class="img-field"><span class="lbl">Обложка записи</span><input type="hidden" name="cover" id="cover-input" value="<?= e($po['cover']) ?>">
            <div class="img-prev" id="cover-prev"><?= $po['cover'] ? '<img src="' . e($po['cover']) . '" alt="">' : '<span class="muted">Фото не выбрано</span>' ?></div>
            <div class="row"><button type="button" class="btn small" id="cover-pick">Загрузить фото</button><button type="button" class="btn small" id="cover-clear">Убрать</button><input type="file" id="cover-file" accept="image/jpeg,image/png,image/webp" hidden></div><small class="muted" id="cover-st"></small></div>
        </div>
        <input type="hidden" name="blocks" id="blocks-input">
        <div id="blocks-editor"></div>
      </div>
      <aside class="card stack side">
        <h2>Публикация и SEO</h2>
        <label>Адрес (slug)<input name="slug" value="<?= e($po['slug']) ?>"><small class="muted">/blog/<?= e($po['slug']) ?></small></label>
        <label>Рубрика<select name="category_id"><option value="0">Без рубрики</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"<?= (int)$po['category_id'] === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?></select></label>
        <label>Дата публикации<input type="datetime-local" name="published_at" value="<?= e(date('Y-m-d\TH:i', strtotime((string)$po['published_at']) ?: time())) ?>"><small class="muted">Если дата в будущем, запись появится на сайте в этот момент.</small></label>
        <label>SEO-заголовок<input name="meta_title" value="<?= e($po['meta_title']) ?>" maxlength="190"></label>
        <label>SEO-описание<textarea name="meta_description" rows="3" maxlength="300"><?= e($po['meta_description']) ?></textarea></label>
        <?= seo_fields($po, '/blog/', false, true) ?>
        <label class="check"><input type="checkbox" name="is_published" value="1"<?= $po['is_published'] ? ' checked' : '' ?>> Опубликована</label>
        <button class="btn primary">Сохранить</button>
        <a class="btn" href="/blog/<?= e($po['slug']) ?>?preview=1" target="_blank" rel="noopener">Открыть запись</a>
        <button class="btn danger" formaction="/admin/?r=post_delete" formnovalidate onclick="return confirm('Удалить запись?')">Удалить</button>
      </aside>
    </form>
    <script>window.PCMS = <?= json_encode(['types' => block_types(), 'blocks' => json_decode((string)$po['blocks'], true) ?: [], 'style' => common_style_fields(), 'galleries' => db_all('SELECT id, title FROM galleries ORDER BY sort, id'), 'categories' => db_all('SELECT id, title FROM categories ORDER BY sort, id'), 'csrf' => csrf_token(), 'pageId' => $id, 'previewEndpoint' => 'post_preview', 'previewUrl' => '/?preview=1&pvpost=' . $id], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
    <script>
    (() => { const $ = id => document.getElementById(id), inp = $('cover-input'), prev = $('cover-prev'), st = $('cover-st'), file = $('cover-file');
      const draw = () => { prev.replaceChildren(); if (inp.value) { const i = document.createElement('img'); i.src = inp.value; i.alt = ''; prev.append(i); } else prev.textContent = 'Фото не выбрано'; };
      $('cover-pick').onclick = () => file.click(); $('cover-clear').onclick = () => { inp.value = ''; draw(); };
      file.onchange = async () => { const f = file.files[0]; if (!f) return; st.textContent = 'Загрузка…'; const fd = new FormData(); fd.append('photo', f);
        try { const r = await fetch('/admin/?r=media_upload', { method: 'POST', headers: { 'X-CSRF-Token': window.PCMS.csrf }, body: fd }), j = await r.json(); if (!r.ok) throw new Error(j.error || 'Ошибка'); inp.value = j.url; draw(); st.textContent = ''; } catch (e) { st.textContent = e.message; } file.value = ''; };
    })();
    </script><?php
    foot('blocks.js'); break;

case 'messages':
    head('Заявки', 'messages');
    $rows = db_all('SELECT * FROM messages ORDER BY id DESC LIMIT 200'); ?>
    <h1>Заявки</h1>
    <?php if (!$rows): ?><p class="empty">Заявок пока нет. Они появятся здесь, когда посетители заполнят форму на сайте.</p><?php endif; ?>
    <div class="stack"><?php foreach ($rows as $m): ?>
    <article class="card msg<?= $m['is_read'] ? '' : ' new' ?>">
      <header><strong><?= e($m['name']) ?></strong><span class="muted"><?= e($m['created_at']) ?></span></header>
      <p class="muted"><?= $m['email'] ? '<a href="mailto:' . e($m['email']) . '">' . e($m['email']) . '</a>' : '' ?><?= $m['email'] && $m['phone'] ? ' · ' : '' ?><?= e($m['phone']) ?></p>
      <p><?= nl2br(e($m['message'])) ?></p>
      <div class="row">
        <?php if (!$m['is_read']): ?><form method="post" action="/admin/?r=message_read"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn">Отметить прочитанной</button></form><?php endif; ?>
        <form method="post" action="/admin/?r=message_delete" onsubmit="return confirm('Удалить заявку?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn danger">Удалить</button></form>
      </div></article>
    <?php endforeach; ?></div><?php
    foot(); break;

default:
    head('Обзор', 'dashboard');
    $gc = (int)db_value('SELECT COUNT(*) FROM galleries'); $pc = (int)db_value('SELECT COUNT(*) FROM photos'); $pg = (int)db_value('SELECT COUNT(*) FROM pages'); ?>
    <h1>Обзор</h1>
    <div class="stats"><div class="card"><b><?= $gc ?></b><span>галерей</span></div><div class="card"><b><?= $pc ?></b><span>фото</span></div><div class="card"><b><?= $pg ?></b><span>страниц</span></div><div class="card"><b><?= (int)db_value('SELECT COUNT(*) FROM messages WHERE is_read = 0') ?></b><span>новых заявок</span></div></div>
    <div class="card stack"><h2>С чего начать</h2>
      <p>1. Создайте галерею и загрузите фото. 2. Соберите главную страницу из блоков и отметьте её главной. 3. Добавьте «Обо мне», «Цены» и «Контакты». 4. Настройте цвета и шрифты в настройках.</p>
      <p class="muted">Файл install.php после установки лучше удалить с сервера.</p></div><?php
    foot();
}
