<?php
declare(strict_types=1);

const RATIO_LABELS = ['auto' => 'Как у фото', '1-1' => 'Квадрат 1:1', '4-3' => 'Горизонт 4:3', '3-2' => 'Горизонт 3:2', '16-9' => 'Широкий 16:9', '3-4' => 'Вертикаль 3:4', '4-5' => 'Вертикаль 4:5'];
const RATIO_CSS = ['auto' => 'auto', '1-1' => '1 / 1', '4-3' => '4 / 3', '3-2' => '3 / 2', '16-9' => '16 / 9', '3-4' => '3 / 4', '4-5' => '4 / 5'];
const ANIM_LABELS = ['none' => 'Без анимации', 'fade' => 'Плавное появление', 'up' => 'Выезд снизу', 'down' => 'Выезд сверху', 'left' => 'Выезд слева', 'right' => 'Выезд справа', 'zoom' => 'Увеличение', 'blur' => 'Из размытия', 'wipe' => 'Открытие снизу', 'wipel' => 'Открытие слева', 'flip' => 'Переворот', 'tilt' => 'Наклон'];
const GALLERY_MODES = ['masonry' => 'Каскад (masonry)', 'grid' => 'Ровная сетка', 'justified' => 'Ряды одной высоты', 'collage' => 'Коллаж (крупные и мелкие)', 'slider' => 'Слайдер', 'filmstrip' => 'Лента с прокруткой', 'stack' => 'Колонкой, по одному'];

/* ---------- field builders ---------- */
function f_text(string $k, string $l, string $t = 'text'): array { return ['k' => $k, 't' => $t, 'l' => $l]; }
function f_image(string $k = 'image', string $l = 'Фото'): array { return ['k' => $k, 't' => 'image', 'l' => $l]; }
function f_select(string $k, string $l, array $o, string $g = ''): array { return ['k' => $k, 't' => 'select', 'l' => $l, 'o' => $o, 'g' => $g]; }
function f_range(string $k, string $l, int $min, int $max, int $def, int $step = 1, string $unit = '', string $g = ''): array {
    return ['k' => $k, 't' => 'range', 'l' => $l, 'min' => $min, 'max' => $max, 'def' => $def, 'step' => $step, 'unit' => $unit, 'g' => $g];
}

/** Settings shared by every block (padding, width, background, appearance animation, visibility). */
function common_style_fields(): array {
    return [
        f_range('pt', 'Отступ сверху', 0, 240, 72, 8, 'px', 'Размеры и отступы'),
        f_range('pb', 'Отступ снизу', 0, 240, 72, 8, 'px', 'Размеры и отступы'),
        f_range('w', 'Ширина блока', 30, 100, 100, 1, '%', 'Размеры и отступы'),
        f_select('bg', 'Фон блока', ['none' => 'Без фона', 'soft' => 'Светлая подложка', 'dark' => 'Тёмная подложка', 'accent' => 'Акцентный цвет'], 'Фон'),
        ['k' => 'bgimg', 't' => 'image', 'l' => 'Фоновое фото', 'g' => 'Фон'],
        f_range('bgov', 'Затемнение фонового фото', 0, 90, 40, 5, '%', 'Фон'),
        f_range('bgpar', 'Параллакс фонового фото', 0, 100, 0, 5, '%', 'Фон'),
        f_select('tf', 'Шрифт заголовка', ['inherit' => 'Как на сайте', 'head' => 'Заголовочный', 'body' => 'Основной', 'quote' => 'Для цитат', 'accent' => 'Акцентный'], 'Эффекты текста'),
        f_select('tfx', 'Эффект заголовка', ['none' => 'Без эффекта', 'words' => 'Появление по словам', 'letters' => 'Появление по буквам', 'typewriter' => 'Печатная машинка', 'gradient' => 'Градиент', 'outline' => 'Контурный текст', 'underline' => 'Прорисовка подчёркивания'], 'Эффекты текста'),
        f_select('anim', 'Появление при прокрутке', ANIM_LABELS, 'Анимация'),
        f_select('dur', 'Скорость', ['normal' => 'Обычная', 'fast' => 'Быстрая', 'slow' => 'Медленная'], 'Анимация'),
        f_range('delay', 'Задержка', 0, 1200, 0, 50, 'мс', 'Анимация'),
        f_select('hide', 'Где показывать', ['none' => 'Везде', 'hide-m' => 'Скрыть на телефоне', 'hide-d' => 'Скрыть на компьютере'], 'Видимость'),
    ];
}

/** Slider settings shared by the slider block and the gallery's slider/strip modes. */
function slider_fields(bool $block): array {
    $f = [f_range('sh', 'Высота слайдера', 30, 100, 70, 5, 'vh', 'Слайдер и лента'), f_range('per', 'Слайдов на экране', 1, 4, 1, 1, '', 'Слайдер и лента'),
        f_select('fit', 'Фото в рамке', ['cover' => 'Заполнить рамку', 'contain' => 'Целиком, с полями'], 'Слайдер и лента'),
        f_range('auto', 'Автопрокрутка (0 = выкл)', 0, 12, 0, 1, 'с', 'Слайдер и лента'),
        f_select('arrows', 'Стрелки', ['on' => 'Показывать', 'off' => 'Скрыть'], 'Слайдер и лента'), f_select('dots', 'Точки', ['on' => 'Показывать', 'off' => 'Скрыть'], 'Слайдер и лента')];
    if ($block) array_unshift($f, f_select('mode', 'Смена слайдов', ['slide' => 'Прокрутка', 'fade' => 'Плавная смена', 'strip' => 'Лента (фото разной ширины)'], 'Слайдер и лента'));
    return $f;
}

/** Look of a photo grid: used by gallery blocks and by each gallery's own page. */
function gallery_look_fields(): array {
    return array_merge([
        f_range('cols', 'Колонок', 2, 6, 3, 1, '', 'Сетка'),
        f_range('gap', 'Расстояние между фото', 0, 64, 24, 2, 'px', 'Сетка'),
        f_select('ratio', 'Пропорции (для ровной сетки)', RATIO_LABELS, 'Сетка'),
        f_range('rowh', 'Высота ряда («Ряды одной высоты», коллаж)', 140, 520, 260, 10, 'px', 'Сетка'),
        f_range('radius', 'Скругление углов', 0, 40, 0, 1, 'px', 'Сетка'),
        f_select('filter', 'Обработка фото', ['none' => 'Без обработки', 'bw' => 'Чёрно-белые', 'bwhover' => 'Чёрно-белые, цвет при наведении', 'muted' => 'Приглушённые цвета', 'warm' => 'Тёплый тон', 'vivid' => 'Насыщенные'], 'Оформление фото'),
        f_select('frame', 'Рамка', ['none' => 'Без рамки', 'shadow' => 'Тень', 'border' => 'Тонкая рамка', 'matte' => 'Паспарту'], 'Оформление фото'),
        f_select('capt', 'Подписи (из описания фото)', ['none' => 'Без подписей', 'hover' => 'При наведении', 'below' => 'Под фото'], 'Оформление фото'),
        f_select('hover', 'При наведении', ['zoom' => 'Приближение', 'dim' => 'Затемнение соседних', 'lift' => 'Приподнимание', 'none' => 'Без эффекта'], 'Анимация фото'),
        f_select('reveal', 'Появление фото', ['up' => 'Выезд снизу', 'fade' => 'Плавно', 'zoom' => 'Увеличение', 'blur' => 'Из размытия', 'left' => 'Слева', 'wipe' => 'Открытие снизу', 'flip' => 'Переворот', 'none' => 'Без анимации'], 'Анимация фото'),
        f_range('stagger', 'Задержка между фото', 0, 250, 70, 10, 'мс', 'Анимация фото'),
        f_select('lightbox', 'Просмотр по клику', ['on' => 'Открывать фото крупно', 'off' => 'Без просмотра'], 'Анимация фото'),
    ], slider_fields(false));
}

/** Block catalogue: drives the admin editor (via JSON) and server-side sanitising. */
function block_types_core(): array {
    $par = fn(int $def) => f_range('parallax', 'Параллакс (сдвиг при прокрутке)', 0, 100, $def, 5, '%', 'Вид блока');
    $up = ['_s' => ['anim' => 'up']];
    return [
        'hero' => ['label' => 'Обложка', 'hint' => 'Большое фото с заголовком',
            'fields' => [f_image(), f_text('title', 'Заголовок'), f_text('subtitle', 'Подзаголовок'), f_text('btn_text', 'Текст кнопки'), f_text('btn_url', 'Ссылка кнопки'),
                f_select('height', 'Высота', ['full' => 'На весь экран', 'tall' => 'Высокая', 'medium' => 'Средняя'])],
            'design' => [f_range('overlay', 'Затемнение фото', 0, 80, 45, 5, '%', 'Вид блока'), f_range('ts', 'Размер заголовка', 50, 150, 100, 5, '%', 'Вид блока'),
                f_select('valign', 'Положение текста', ['bottom' => 'Внизу слева', 'center' => 'По центру'], 'Вид блока'), $par(35),
                f_select('kb', 'Медленное приближение фото', ['none' => 'Выключено', 'zoom' => 'Включено'], 'Вид блока')],
            'sdef' => ['pt' => 0, 'pb' => 0], 'default' => ['height' => 'full']],
        'text' => ['label' => 'Текст', 'hint' => 'Заголовок и абзацы',
            'fields' => [f_text('title', 'Заголовок'), f_text('body', 'Текст (абзацы разделяйте пустой строкой)', 'textarea'),
                f_select('align', 'Выравнивание', ['left' => 'По левому краю', 'center' => 'По центру'])],
            'design' => [f_select('fs', 'Размер текста', ['m' => 'Обычный', 's' => 'Мелкий', 'l' => 'Крупный', 'xl' => 'Очень крупный'], 'Вид блока'), f_range('mw', 'Ширина колонки', 24, 64, 38, 1, 'rem', 'Вид блока')],
            'default' => $up],
        'image' => ['label' => 'Фото', 'hint' => 'Одно изображение с подписью',
            'fields' => [f_image(), f_text('caption', 'Подпись'), f_text('alt', 'Описание для поисковиков'),
                f_select('width', 'Ширина', ['wide' => 'Широкое', 'narrow' => 'Узкое', 'full' => 'Во весь экран'])],
            'design' => [f_select('ratio', 'Пропорции рамки', RATIO_LABELS, 'Вид блока'), f_range('radius', 'Скругление углов', 0, 40, 0, 1, 'px', 'Вид блока'), $par(0),
                f_select('filter', 'Обработка фото', ['none' => 'Без обработки', 'bw' => 'Чёрно-белое', 'bwhover' => 'Чёрно-белое, цвет при наведении', 'muted' => 'Приглушённые цвета', 'warm' => 'Тёплый тон', 'vivid' => 'Насыщенное'], 'Эффекты'),
                f_select('frame', 'Рамка', ['none' => 'Без рамки', 'shadow' => 'Тень', 'border' => 'Тонкая рамка', 'matte' => 'Паспарту'], 'Эффекты'),
                f_select('hover', 'При наведении', ['none' => 'Без эффекта', 'zoom' => 'Приближение', 'lift' => 'Приподнимание'], 'Эффекты')],
            'default' => $up],
        'two_col' => ['label' => 'Фото и текст', 'hint' => 'Фото рядом с текстом',
            'fields' => [f_image(), f_text('title', 'Заголовок'), f_text('body', 'Текст', 'textarea'), f_text('alt', 'Описание фото'),
                f_select('side', 'Фото', ['left' => 'Слева', 'right' => 'Справа'])],
            'design' => [f_range('split', 'Доля фото', 30, 70, 53, 1, '%', 'Вид блока'),
                f_select('ratio', 'Пропорции фото', ['4-5' => 'Вертикаль 4:5', '3-4' => 'Вертикаль 3:4', '1-1' => 'Квадрат 1:1', '4-3' => 'Горизонт 4:3'], 'Вид блока'),
                f_select('valign', 'Текст по вертикали', ['center' => 'По центру', 'top' => 'Сверху'], 'Вид блока'), f_range('radius', 'Скругление углов', 0, 40, 0, 1, 'px', 'Вид блока'), $par(0)],
            'default' => $up],
        'gallery' => ['label' => 'Галерея', 'hint' => 'Фото из вашей галереи',
            'fields' => [['k' => 'gallery_id', 't' => 'gallery', 'l' => 'Галерея'], f_text('title', 'Заголовок'),
                f_select('limit', 'Сколько фото', ['9' => '9', '6' => '6', '12' => '12', '18' => '18', '0' => 'Все']),
                f_select('link', 'Ссылка «Смотреть всю галерею»', ['1' => 'Показывать', '0' => 'Скрыть'])],
            'design' => array_merge([f_select('mode', 'Вид галереи', ['inherit' => 'Как в настройках галереи'] + GALLERY_MODES, 'Сетка')], gallery_look_fields()),
            'default' => ['limit' => '9', 'link' => '1']],
        'quote' => ['label' => 'Цитата', 'hint' => 'Отзыв или короткая фраза',
            'fields' => [f_text('text', 'Текст', 'textarea'), f_text('author', 'Автор')],
            'design' => [f_select('fs', 'Размер текста', ['m' => 'Обычный', 'l' => 'Крупный', 'xl' => 'Очень крупный'], 'Вид блока')], 'default' => $up],
        'pricing' => ['label' => 'Цены и пакеты', 'hint' => 'Колонки с тарифами',
            'fields' => [f_text('title', 'Заголовок'),
                ['k' => 'plans', 't' => 'repeater', 'l' => 'Пакеты', 'add' => 'Добавить пакет', 'sub' => [f_text('name', 'Название'), f_text('price', 'Цена'), f_text('items', 'Что входит (каждый пункт с новой строки)', 'textarea')]]],
            'design' => [], 'default' => ['plans' => []] + $up],
        'cta' => ['label' => 'Призыв к действию', 'hint' => 'Фраза и кнопка',
            'fields' => [f_text('title', 'Заголовок'), f_text('text', 'Текст'), f_text('btn_text', 'Текст кнопки'), f_text('btn_url', 'Ссылка кнопки')], 'design' => [], 'default' => $up],
        'contact' => ['label' => 'Форма заявки', 'hint' => 'Имя, контакты и сообщение',
            'fields' => [f_text('title', 'Заголовок'), f_text('text', 'Текст над формой', 'textarea')], 'design' => [], 'default' => ['title' => 'Напишите мне'] + $up],
        'spacer' => ['label' => 'Отступ', 'hint' => 'Пустое место между блоками', 'nostyle' => true,
            'fields' => [f_range('h', 'Высота', 0, 400, 80, 8, 'px')], 'design' => [], 'default' => []],
        'html' => ['label' => 'HTML', 'hint' => 'Свой код, для опытных', 'fields' => [f_text('code', 'HTML-код', 'textarea')], 'design' => [], 'default' => []],
    ];
}

/* ---------- defaults & sanitising ---------- */
function field_defaults(array $fields): array {
    $o = [];
    foreach ($fields as $f) {
        $o[$f['k']] = match ($f['t']) { 'range' => $f['def'], 'select' => (string)($f['def'] ?? array_key_first($f['o'])), 'color', 'string' => (string)$f['def'], 'repeater' => [], 'gallery', 'category' => 0, default => '' };
    }
    return $o;
}
function style_defaults(string $type): array {
    $t = block_types()[$type] ?? [];
    return array_replace(field_defaults(common_style_fields()), $t['sdef'] ?? []);
}
function block_defaults(string $type): array {
    $t = block_types()[$type] ?? ['fields' => [], 'design' => []];
    return field_defaults(array_merge($t['fields'], $t['design']));
}

function clean_url(string $u): string {
    $u = trim($u);
    if ($u === '') return '';
    return preg_match('~^(/(?!/)|#|https?://|mailto:|tel:)~i', $u) ? $u : '';
}
function clean_image(string $u): string { return preg_match('~^/uploads/[A-Za-z0-9_./-]+$~', $u) && !str_contains($u, '..') ? $u : ''; }

function sanitize_fields(array $fields, array $in): array {
    $out = [];
    foreach ($fields as $f) {
        $v = $in[$f['k']] ?? null;
        switch ($f['t']) {
            case 'repeater':
                $rows = [];
                foreach (is_array($v) ? array_slice($v, 0, 30) : [] as $row) if (is_array($row)) $rows[] = sanitize_fields($f['sub'], $row);
                $out[$f['k']] = $rows; break;
            case 'image': $out[$f['k']] = clean_image((string)$v); break;
            case 'select': $out[$f['k']] = array_key_exists((string)$v, $f['o']) ? (string)$v : (string)($f['def'] ?? array_key_first($f['o'])); break;
            case 'color': $out[$f['k']] = preg_match('~^#[0-9a-fA-F]{6}$~', (string)$v) ? strtolower((string)$v) : $f['def']; break;
            case 'string': $out[$f['k']] = mb_substr(trim((string)$v), 0, 200); break;
            case 'category': $out[$f['k']] = (int)$v; break;
            case 'range': $n = ($v === null || $v === '' || !is_numeric($v)) ? $f['def'] : (int)round((float)$v); $out[$f['k']] = max($f['min'], min($f['max'], $n)); break;
            case 'gallery': $out[$f['k']] = (int)$v; break;
            case 'textarea': $out[$f['k']] = mb_substr(str_replace("\r", '', (string)$v), 0, $f['k'] === 'code' ? 20000 : 6000); break;
            default: $out[$f['k']] = mb_substr(trim((string)$v), 0, 300);
        }
        if (str_ends_with($f['k'], '_url')) $out[$f['k']] = clean_url($out[$f['k']]);
    }
    return $out;
}

function sanitize_blocks(string $json): array {
    $data = json_decode($json, true);
    if (!is_array($data)) return [];
    $types = block_types(); $out = [];
    foreach (array_slice($data, 0, 60) as $b) {
        $t = is_array($b) ? ($b['type'] ?? '') : '';
        if (!isset($types[$t])) continue;
        $in = is_array($b['data'] ?? null) ? $b['data'] : [];
        $d = sanitize_fields(array_merge($types[$t]['fields'], $types[$t]['design']), $in);
        if (empty($types[$t]['nostyle'])) {
            $sin = array_replace(style_defaults($t), is_array($in['_s'] ?? null) ? $in['_s'] : []);
            $d['_s'] = sanitize_fields(common_style_fields(), $sin);
        }
        $out[] = ['type' => $t, 'data' => $d];
    }
    return $out;
}

function gallery_opts(array $g): array {
    $saved = json_decode((string)($g['opts'] ?? ''), true);
    $o = array_replace(field_defaults(gallery_look_fields()), is_array($saved) ? $saved : []);
    $o['mode'] = gallery_mode((string)($g['layout'] ?? ''), 'masonry');
    return $o;
}

/* ---------- rendering helpers ---------- */
function paragraphs(string $s): string {
    $out = '';
    foreach (preg_split('~\n{2,}~', trim($s)) as $p) if (trim($p) !== '') $out .= '<p>' . nl2br(e(trim($p))) . '</p>';
    return $out;
}
function btn(string $text, string $url, string $cls = ''): string {
    return ($text !== '' && $url !== '') ? '<a class="btn ' . $cls . '" href="' . e($url) . '">' . e($text) . '</a>' : '';
}
function style_attr(array $vars): string {
    $s = ''; foreach ($vars as $k => $v) $s .= $k . ':' . $v . ';';
    return $s === '' ? '' : ' style="' . e($s) . '"';
}
function par_attrs(int $par): array { return $par > 0 ? [' px', ' data-parallax="' . round($par / 100, 2) . '"'] : ['', '']; }

function render_blocks(array $blocks, bool $preview = false): string {
    $html = '';
    foreach ($blocks as $b) $html .= render_block((string)($b['type'] ?? ''), is_array($b['data'] ?? null) ? $b['data'] : [], $preview);
    return $html;
}

/* render_gallery_grid() and block_wrap() live in blocks_render.php */

function render_block(string $type, array $d, bool $preview): string {
    if (!isset(block_types()[$type])) return '';
    $d = array_replace(block_defaults($type), $d);
    $h = fn(string $k) => $d[$k] ?? '';
    if ($type === 'spacer') return '<div class="bs bs-spacer" style="--pt:' . max(0, min(400, (int)$h('h'))) . 'px;--pb:0px" aria-hidden="true"></div>';
    if ($type === 'html') return '<section class="bs bs-html"><div class="b b-html">' . $h('code') . '</div></section>';
    $s = array_replace(style_defaults($type), is_array($d['_s'] ?? null) ? $d['_s'] : []);
    if (isset(block_types_extra()[$type])) { $px = extra_block_parts($type, $d, $s, $preview); return $px ? block_wrap($type, $d, $px) : ''; }
    $p = null;
    switch ($type) {
    case 'hero':
        $img = clean_image((string)$h('image')); $height = in_array($h('height'), ['full', 'tall', 'medium'], true) ? $h('height') : 'full';
        $par = (int)$h('parallax'); [$pxc, $pxa] = par_attrs($par);
        $cls = 'b-hero h-' . $height . ($img ? ' has-img' : '') . ($h('valign') === 'center' ? ' va-center' : '') . (($h('kb') === 'zoom' && $par === 0) ? ' kb' : '');
        $html = ($img ? '<div class="media' . $pxc . '"' . $pxa . '><img src="' . e($img) . '" alt="" fetchpriority="high"></div>' : '')
            . '<div class="b-hero-in">' . ($h('title') !== '' ? '<h1>' . e($h('title')) . '</h1>' : '') . ($h('subtitle') !== '' ? '<p>' . e($h('subtitle')) . '</p>' : '') . btn((string)$h('btn_text'), (string)$h('btn_url'), 'on-dark') . '</div>';
        $p = ['cls' => $cls, 'html' => $html, 'bleed' => true, 'vars' => ['--ov-c' => 'rgba(10,14,16,' . round(max(0, min(80, (int)$h('overlay'))) / 100, 2) . ')', '--ts' => round(max(50, min(150, (int)$h('ts'))) / 100, 2)]];
        break;
    case 'text':
        $p = ['cls' => 'b-text a-' . ($h('align') === 'center' ? 'center' : 'left'),
            'html' => ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . paragraphs((string)$h('body')),
            'vars' => ['--mw' => max(24, min(64, (int)$h('mw'))), '--fs' => ['s' => '.95rem', 'm' => '1.0625rem', 'l' => '1.25rem', 'xl' => '1.5rem'][$h('fs')] ?? '1.0625rem']];
        break;
    case 'image':
        $img = clean_image((string)$h('image')); if (!$img) return '';
        $w = in_array($h('width'), ['narrow', 'wide', 'full'], true) ? $h('width') : 'wide';
        $par = (int)$h('parallax'); $ratio = isset(RATIO_CSS[$h('ratio')]) ? $h('ratio') : 'auto'; if ($par > 0 && $ratio === 'auto') $ratio = '3-2';
        [$pxc, $pxa] = par_attrs($par);
        $html = '<div class="frame' . $pxc . '"' . $pxa . '><img src="' . e($img) . '" alt="' . e($h('alt') ?: $h('caption')) . '" loading="lazy"></div>' . ($h('caption') !== '' ? '<figcaption>' . e($h('caption')) . '</figcaption>' : '');
        $fl = in_array($h('filter'), ['bw', 'bwhover', 'muted', 'warm', 'vivid'], true) ? ' f-' . $h('filter') : ''; $fr = in_array($h('frame'), ['shadow', 'border', 'matte'], true) ? ' fr-' . $h('frame') : ''; $hv = in_array($h('hover'), ['zoom', 'lift'], true) ? ' hv1-' . $h('hover') : '';
        $p = ['tag' => 'figure', 'cls' => 'b-image w-' . $w . ($ratio !== 'auto' ? ' has-ar' : '') . $fl . $fr . $hv, 'html' => $html, 'bleed' => $w === 'full',
            'vars' => ['--rad' => max(0, min(40, (int)$h('radius'))) . 'px', '--ar' => RATIO_CSS[$ratio]]];
        break;
    case 'two_col':
        $img = clean_image((string)$h('image')); $par = (int)$h('parallax'); [$pxc, $pxa] = par_attrs($par);
        $ratio = isset(RATIO_CSS[$h('ratio')]) ? $h('ratio') : '4-5';
        $html = '<div class="two-img"><div class="frame' . $pxc . '"' . $pxa . '>' . ($img ? '<img src="' . e($img) . '" alt="' . e($h('alt') ?: $h('title')) . '" loading="lazy">' : '') . '</div></div>'
            . '<div class="two-txt">' . ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . paragraphs((string)$h('body')) . '</div>';
        $p = ['cls' => 'b-two s-' . ($h('side') === 'right' ? 'right' : 'left') . ' va-' . ($h('valign') === 'top' ? 'top' : 'center'), 'html' => $html,
            'vars' => ['--split' => max(30, min(70, (int)$h('split'))), '--ar' => RATIO_CSS[$ratio], '--rad' => max(0, min(40, (int)$h('radius'))) . 'px']];
        break;
    case 'gallery':
        // Встроенная в страницу галерея показывается всегда (даже если сама галерея не опубликована как отдельная страница).
        // Публикация галереи влияет только на раздел /portfolio/.
        $g = db_row('SELECT * FROM galleries WHERE id = ?', [(int)$h('gallery_id')]);
        if (!$g) return $preview ? '<div class="bs"><section class="b b-note"><p>Галерея не выбрана.</p></section></div>' : '';
        $limit = (int)$h('limit');
        $photos = db_all('SELECT * FROM photos WHERE gallery_id = ? ORDER BY sort, id' . ($limit > 0 ? ' LIMIT ' . $limit : ''), [$g['id']]);
        if (!$photos && !$preview) return '';
        $o = array_replace(gallery_opts($g), array_intersect_key($d, field_defaults(gallery_look_fields())));
        if (isset(GALLERY_MODES[$h('mode')])) $o['mode'] = $h('mode');
        $html = ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . render_gallery_grid($g, $photos, $o)
            . ((string)$h('link') !== '0' && !empty($g['is_published']) ? '<p class="more"><a href="/portfolio/' . e($g['slug']) . '">Смотреть всю галерею</a></p>' : '');
        $p = ['cls' => 'b-gallery', 'html' => $html];
        break;
    case 'quote':
        if ($h('text') === '') return '';
        $p = ['cls' => 'b-quote fs-' . (in_array($h('fs'), ['l', 'xl'], true) ? $h('fs') : 'm'),
            'html' => '<blockquote><p>' . nl2br(e((string)$h('text'))) . '</p>' . ($h('author') !== '' ? '<footer>' . e($h('author')) . '</footer>' : '') . '</blockquote>'];
        break;
    case 'pricing':
        $o = ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . '<div class="plans">';
        foreach (($d['plans'] ?? []) as $pl) {
            $o .= '<div class="plan"><h3>' . e($pl['name'] ?? '') . '</h3><div class="price">' . e($pl['price'] ?? '') . '</div><ul>';
            foreach (preg_split('~\n+~', trim((string)($pl['items'] ?? ''))) as $li) if (trim($li) !== '') $o .= '<li>' . e(trim($li)) . '</li>';
            $o .= '</ul></div>';
        }
        $p = ['cls' => 'b-pricing', 'html' => $o . '</div>'];
        break;
    case 'cta':
        $p = ['cls' => 'b-cta', 'html' => '<div class="narrow">' . ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . ($h('text') !== '' ? '<p>' . e($h('text')) . '</p>' : '') . btn((string)$h('btn_text'), (string)$h('btn_url'), 'solid') . '</div>'];
        break;
    case 'contact':
        $_SESSION['form_t'] = time();
        $f = $_SESSION['pflash'] ?? null; unset($_SESSION['pflash']);
        $o = '<div class="narrow" id="contact">' . ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . paragraphs((string)$h('text'));
        if ($f) $o .= '<div class="notice ' . e($f['type']) . '" role="status">' . e($f['msg']) . '</div>';
        $o .= '<form method="post" action="/contact" class="contact-form">' . csrf_field() . '<input type="hidden" name="back" value="' . e('/' . trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/')) . '">'
            . '<label>Имя<input name="name" required maxlength="190" autocomplete="name"></label>'
            . '<label>Email<input type="email" name="email" maxlength="190" autocomplete="email"></label>'
            . '<label>Телефон или мессенджер<input name="phone" maxlength="60" autocomplete="tel"></label>'
            . '<label>Сообщение<textarea name="message" rows="5" required maxlength="4000"></textarea></label>'
            . '<div class="hp" aria-hidden="true"><label>Не заполняйте<input name="website" tabindex="-1" autocomplete="off"></label></div>'
            . '<button class="btn solid">Отправить</button><small>Укажите email или телефон, чтобы я мог вам ответить.</small></form></div>';
        $p = ['cls' => 'b-contact', 'html' => $o];
        break;
    }
    return $p ? block_wrap($type, $d, $p) : '';
}

/** Blocks for a page row; legacy HTML content becomes a single html block. */
function page_blocks(array $pg): array {
    $b = json_decode((string)($pg['blocks'] ?? ''), true);
    if (is_array($b) && $b) return $b;
    if (trim((string)($pg['content'] ?? '')) !== '') return [['type' => 'html', 'data' => ['code' => $pg['content']]]];
    return [];
}
