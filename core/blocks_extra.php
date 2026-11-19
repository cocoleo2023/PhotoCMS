<?php
declare(strict_types=1);

function block_types_extra(): array {
    $up = ['_s' => ['anim' => 'up']];
    $ratios = ['auto' => 'Как у фото', '1-1' => 'Квадрат 1:1', '4-3' => '4:3', '3-2' => '3:2', '16-9' => '16:9', '4-5' => 'Вертикаль 4:5'];
    $fonts = ['head' => 'Заголовочный', 'body' => 'Основной', 'quote' => 'Для цитат', 'accent' => 'Акцентный'];
    return [
        'slider' => ['label' => 'Слайдер', 'hint' => 'Слайды с текстом или фото из галереи',
            'fields' => [['k' => 'gallery_id', 't' => 'gallery', 'l' => 'Фото из галереи (или добавьте свои слайды ниже)'], f_select('limit', 'Сколько фото из галереи', ['12' => '12', '6' => '6', '9' => '9', '18' => '18', '0' => 'Все']),
                ['k' => 'slides', 't' => 'repeater', 'l' => 'Свои слайды', 'add' => 'Добавить слайд', 'sub' => [f_image(), f_text('title', 'Заголовок'), f_text('text', 'Текст'), f_text('btn_text', 'Текст кнопки'), f_text('btn_url', 'Ссылка кнопки')]]],
            'design' => array_merge(slider_fields(true), [f_range('ov', 'Затемнение под текстом', 0, 70, 25, 5, '%', 'Слайдер и лента'),
                f_select('kb', 'Медленное приближение (при плавной смене)', ['off' => 'Выключено', 'on' => 'Включено'], 'Слайдер и лента'),
                f_range('gap', 'Расстояние между слайдами', 0, 48, 0, 2, 'px', 'Слайдер и лента'), f_range('radius', 'Скругление углов', 0, 40, 0, 1, 'px', 'Слайдер и лента'),
                f_select('wide', 'Ширина', ['box' => 'В рамках сайта', 'full' => 'Во всю ширину экрана'], 'Слайдер и лента')]),
            'default' => ['slides' => []] + $up],
        'features' => ['label' => 'Преимущества', 'hint' => 'Колонки с фото или значком и текстом',
            'fields' => [f_text('title', 'Заголовок'), f_text('intro', 'Вводный текст', 'textarea'),
                ['k' => 'items', 't' => 'repeater', 'l' => 'Колонки', 'add' => 'Добавить колонку', 'sub' => [f_text('icon', 'Значок (эмодзи или символ)'), f_image(), f_text('title', 'Заголовок'), f_text('text', 'Текст', 'textarea'), f_text('link_url', 'Ссылка')]]],
            'design' => [f_range('cols', 'Колонок', 2, 4, 3, 1, '', 'Вид блока'), f_select('card', 'Вид колонки', ['plain' => 'Без рамки', 'card' => 'Карточка', 'outline' => 'Контур'], 'Вид блока'),
                f_select('ratio', 'Пропорции фото', $ratios, 'Вид блока'), f_select('align', 'Выравнивание', ['left' => 'По левому краю', 'center' => 'По центру'], 'Вид блока'),
                f_range('gap', 'Расстояние между колонками', 8, 96, 40, 4, 'px', 'Вид блока'), f_range('stagger', 'Задержка между колонками', 0, 300, 90, 10, 'мс', 'Вид блока'),
                f_select('hover', 'При наведении', ['lift' => 'Приподнимание', 'none' => 'Без эффекта'], 'Вид блока')],
            'default' => ['items' => []] + $up],
        'reviews' => ['label' => 'Отзывы', 'hint' => 'Слайдер или сетка отзывов',
            'fields' => [f_text('title', 'Заголовок'), ['k' => 'items', 't' => 'repeater', 'l' => 'Отзывы', 'add' => 'Добавить отзыв', 'sub' => [f_text('text', 'Текст отзыва', 'textarea'), f_text('author', 'Автор'), f_text('role', 'Подпись (например, «свадьба 2025»)'), f_image('photo', 'Фото автора')]]],
            'design' => [f_select('layout', 'Показ', ['slider' => 'Слайдер', 'grid' => 'Сетка'], 'Вид блока'), f_range('cols', 'Колонок (для сетки)', 1, 3, 2, 1, '', 'Вид блока'),
                f_select('style', 'Вид отзыва', ['plain' => 'Без рамки', 'card' => 'Карточка'], 'Вид блока'), f_select('fs', 'Размер текста', ['m' => 'Обычный', 'l' => 'Крупный', 'xl' => 'Очень крупный'], 'Вид блока'),
                f_select('align', 'Выравнивание', ['center' => 'По центру', 'left' => 'По левому краю'], 'Вид блока'), f_range('auto', 'Автопрокрутка (0 = выкл)', 0, 12, 0, 1, 'с', 'Вид блока')],
            'default' => ['items' => []] + $up],
        'counters' => ['label' => 'Цифры', 'hint' => 'Крупные числа с подписями и счётчиком',
            'fields' => [f_text('title', 'Заголовок'), ['k' => 'items', 't' => 'repeater', 'l' => 'Показатели', 'add' => 'Добавить показатель', 'sub' => [f_text('number', 'Число'), f_text('suffix', 'После числа (+, %, лет…)'), f_text('label', 'Подпись')]]],
            'design' => [f_range('cols', 'Колонок', 2, 5, 4, 1, '', 'Вид блока'), f_range('size', 'Размер чисел', 60, 160, 100, 5, '%', 'Вид блока'), f_select('align', 'Выравнивание', ['center' => 'По центру', 'left' => 'По левому краю'], 'Вид блока'),
                f_select('lines', 'Разделители', ['none' => 'Без линий', 'lines' => 'Тонкие линии сверху'], 'Вид блока'), f_select('count', 'Счётчик при появлении', ['on' => 'Считать от нуля', 'off' => 'Сразу итог'], 'Вид блока'),
                f_range('stagger', 'Задержка между показателями', 0, 300, 90, 10, 'мс', 'Вид блока')],
            'default' => ['items' => []] + $up],
        'faq' => ['label' => 'Вопросы и ответы', 'hint' => 'Раскрывающийся список (улучшает SEO)',
            'fields' => [f_text('title', 'Заголовок'), ['k' => 'items', 't' => 'repeater', 'l' => 'Вопросы', 'add' => 'Добавить вопрос', 'sub' => [f_text('q', 'Вопрос'), f_text('a', 'Ответ', 'textarea')]]],
            'design' => [f_select('style', 'Вид', ['lines' => 'Линии', 'cards' => 'Карточки', 'plain' => 'Без оформления'], 'Вид блока'), f_select('open', 'Вначале', ['closed' => 'Все закрыты', 'first' => 'Первый открыт'], 'Вид блока'),
                f_select('width', 'Ширина', ['narrow' => 'Узкая', 'wide' => 'Широкая'], 'Вид блока'), f_select('schema', 'Разметка для поисковиков (FAQPage)', ['on' => 'Добавить', 'off' => 'Не добавлять'], 'Вид блока')],
            'default' => ['items' => []] + $up],
        'marquee' => ['label' => 'Бегущая строка', 'hint' => 'Крупный текст, который плывёт по экрану',
            'fields' => [f_text('text', 'Текст'), f_text('sep', 'Разделитель (например, ✦)')],
            'design' => [f_range('dur', 'Время одного прохода', 8, 120, 30, 2, 'с', 'Вид блока'), f_select('reverse', 'Направление', ['no' => 'Справа налево', 'yes' => 'Слева направо'], 'Вид блока'),
                f_range('size', 'Размер текста', 14, 200, 64, 2, 'px', 'Вид блока'), f_select('look', 'Вид', ['fill' => 'Заливка', 'outline' => 'Контур'], 'Вид блока'),
                f_select('font', 'Шрифт', $fonts, 'Вид блока'), f_select('pause', 'Пауза при наведении', ['on' => 'Да', 'off' => 'Нет'], 'Вид блока')],
            'default' => ['text' => 'Свадьбы · Love story · Семейные истории', 'sep' => '✦'], 'sdef' => ['pt' => 24, 'pb' => 24]],
        'video' => ['label' => 'Видео', 'hint' => 'YouTube или Vimeo, загрузка по клику',
            'fields' => [f_text('url', 'Ссылка на видео (YouTube или Vimeo)'), f_image('poster', 'Обложка'), f_text('caption', 'Подпись')],
            'design' => [f_select('ratio', 'Пропорции', ['16-9' => '16:9', '21-9' => '21:9', '4-3' => '4:3', '1-1' => 'Квадрат'], 'Вид блока'), f_select('width', 'Ширина', ['wide' => 'Широкое', 'narrow' => 'Узкое', 'full' => 'Во весь экран'], 'Вид блока'),
                f_range('radius', 'Скругление углов', 0, 40, 0, 1, 'px', 'Вид блока')], 'default' => $up],
        'posts' => ['label' => 'Записи блога', 'hint' => 'Последние записи из блога',
            'fields' => [f_text('title', 'Заголовок'), ['k' => 'cat', 't' => 'category', 'l' => 'Рубрика'], f_select('count', 'Сколько записей', ['3' => '3', '4' => '4', '6' => '6', '9' => '9', '12' => '12'])],
            'design' => [f_select('layout', 'Вариант оформления', ['grid' => 'Сетка карточек', 'list' => 'Список с фото сбоку', 'magazine' => 'Журнал', 'masonry' => 'Каскад', 'minimal' => 'Только текст'], 'Вид блока'),
                f_select('card', 'Вид карточки', ['plain' => 'Без рамки', 'card' => 'Карточка', 'overlay' => 'Текст поверх фото'], 'Вид блока'), f_range('cols', 'Колонок', 2, 4, 3, 1, '', 'Вид блока'),
                f_select('ratio', 'Пропорции фото', ['4-3' => '4:3', '3-2' => '3:2', '16-9' => '16:9', '1-1' => 'Квадрат', '4-5' => 'Вертикаль 4:5'], 'Вид блока'), f_select('link', 'Ссылка «Весь блог»', ['1' => 'Показывать', '0' => 'Скрыть'], 'Вид блока')],
            'default' => ['title' => 'Из блога', 'count' => '3'] + $up],
        'divider' => ['label' => 'Разделитель', 'hint' => 'Линия или орнамент между блоками',
            'fields' => [f_text('orn', 'Орнамент (символ в центре)')],
            'design' => [f_select('style', 'Вид', ['line' => 'Линия', 'double' => 'Двойная линия', 'dots' => 'Точки', 'orn' => 'Линия с орнаментом'], 'Вид блока'), f_range('thick', 'Толщина линии', 1, 6, 1, 1, 'px', 'Вид блока'),
                f_select('color', 'Цвет', ['line' => 'Светлый', 'accent' => 'Акцентный', 'ink' => 'Цвет текста'], 'Вид блока')],
            'default' => ['orn' => '✦'], 'sdef' => ['pt' => 24, 'pb' => 24]],
        'content_gallery' => ['label' => 'Галерея контента', 'hint' => 'Записи блога или страницы в виде красивой сетки/галереи',
            'fields' => [
                f_text('title', 'Заголовок'),
                f_select('source', 'Источник', ['posts' => 'Записи блога', 'pages' => 'Страницы', 'page_cat' => 'Категория страниц']),
                ['k' => 'category_id', 't' => 'category', 'l' => 'Рубрика блога (для записей)'],
                f_string('page_cat', 'ID категории страниц (для page_cat)', '0'),
                f_select('count', 'Сколько показать', ['3' => '3', '4' => '4', '6' => '6', '8' => '8', '9' => '9', '12' => '12', '0' => 'Все']),
                f_select('link', 'Ссылка «Смотреть все»', ['1' => 'Показывать', '0' => 'Скрыть']),
            ],
            'design' => [
                f_select('layout', 'Вид', ['grid' => 'Сетка', 'masonry' => 'Каскад', 'magazine' => 'Журнал (первая крупно)', 'overlay' => 'Текст поверх фото', 'filmstrip' => 'Горизонтальная лента'], 'Вид блока'),
                f_range('cols', 'Колонок', 2, 4, 3, 1, '', 'Вид блока'),
                f_select('ratio', 'Пропорции фото', ['4-5' => '4:5', '3-4' => '3:4', '1-1' => '1:1', '4-3' => '4:3', '16-9' => '16:9', 'auto' => 'Как у фото'], 'Вид блока'),
                f_range('gap', 'Расстояние', 8, 64, 24, 4, 'px', 'Вид блока'),
                f_range('radius', 'Скругление', 0, 40, 0, 1, 'px', 'Вид блока'),
                f_select('hover', 'При наведении', ['zoom' => 'Увеличение', 'lift' => 'Приподнимание', 'dim' => 'Затемнение', 'none' => 'Нет'], 'Вид блока'),
                f_select('show_title', 'Заголовок карточки', ['1' => 'Да', '0' => 'Нет'], 'Вид блока'),
                f_select('show_meta', 'Дата / рубрика', ['1' => 'Да', '0' => 'Нет'], 'Вид блока'),
            ],
            'default' => ['source' => 'posts', 'count' => '6', 'link' => '1', 'layout' => 'grid', 'cols' => 3, 'ratio' => '4-5', 'gap' => 24, 'radius' => 0, 'hover' => 'zoom', 'show_title' => '1', 'show_meta' => '1', 'page_cat' => '0'] + ['_s' => ['anim' => 'up']]],
        'columns' => ['label' => 'Колонки', 'hint' => 'Сетка с настраиваемыми пропорциями (HTML в каждой колонке)',
            'fields' => [
                f_select('cols', 'Число колонок', ['2' => '2', '3' => '3', '4' => '4']),
                f_text('ratios', 'Пропорции (через двоеточие, напр. 1:2:1 или 30:70)', ''),
                f_range('gap', 'Расстояние между колонками', 0, 80, 24, 4, 'px'),
                f_select('valign', 'Выравнивание по вертикали', ['top' => 'Сверху', 'center' => 'По центру', 'stretch' => 'Растянуть']),
                f_text('c1', 'Колонка 1 (HTML или текст)', 'textarea'),
                f_text('c2', 'Колонка 2 (HTML или текст)', 'textarea'),
                f_text('c3', 'Колонка 3 (HTML или текст)', 'textarea'),
                f_text('c4', 'Колонка 4 (HTML или текст)', 'textarea'),
            ],
            'design' => [
                f_select('stack_m', 'На телефоне', ['stack' => 'Друг под другом', 'keep' => 'Оставить колонки']),
            ],
            'default' => ['cols' => '2', 'ratios' => '1:1', 'gap' => 24, 'valign' => 'top', 'c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'stack_m' => 'stack'] + ['_s' => ['anim' => 'up']]],
    ];
}

/** Final catalogue: core + extra blocks in a sensible order for the picker. */
function block_types(): array {
    static $all = null;
    if ($all === null) {
        $m = array_merge(block_types_core(), block_types_extra());
        $all = [];
        foreach (['hero', 'text', 'image', 'two_col', 'gallery', 'content_gallery', 'slider', 'features', 'columns', 'quote', 'reviews', 'counters', 'pricing', 'faq', 'cta', 'contact', 'marquee', 'video', 'posts', 'divider', 'spacer', 'html'] as $k) if (isset($m[$k])) $all[$k] = $m[$k];
    }
    return $all;
}

function video_embed(string $u): ?string {
    if (preg_match('~^https://(?:www\.)?(?:youtube\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $u, $m)) return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
    if (preg_match('~^https://(?:www\.)?vimeo\.com/(?:video/)?(\d{6,12})~', $u, $m)) return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
    return null;
}

function extra_block_parts(string $type, array $d, array $s, bool $preview): ?array {
    $h = fn(string $k) => $d[$k] ?? '';
    $rows = fn(string $k) => array_values(array_filter(is_array($d[$k] ?? null) ? $d[$k] : [], 'is_array'));
    switch ($type) {
    case 'slider':
        $items = [];
        if ((int)$h('gallery_id') > 0 && ($g = db_row('SELECT * FROM galleries WHERE id = ?' . ($preview ? '' : ' AND is_published = 1'), [(int)$h('gallery_id')]))) {
            $lim = (int)$h('limit');
            foreach (db_all('SELECT * FROM photos WHERE gallery_id = ? ORDER BY sort, id' . ($lim > 0 ? ' LIMIT ' . $lim : ''), [$g['id']]) as $i => $p)
                $items[] = '<img src="' . e(upload_url((int)$g['id'], $p['file'])) . '" width="' . (int)$p['width'] . '" height="' . (int)$p['height'] . '" alt="' . e($p['alt'] ?: $g['title']) . '"' . ($i ? ' loading="lazy"' : '') . '>';
        } else {
            foreach ($rows('slides') as $i => $sl) {
                $img = clean_image((string)($sl['image'] ?? '')); if (!$img && ($sl['title'] ?? '') === '') continue;
                $cap = (($sl['title'] ?? '') !== '' ? '<h2>' . e($sl['title']) . '</h2>' : '') . (($sl['text'] ?? '') !== '' ? '<p>' . e($sl['text']) . '</p>' : '') . btn((string)($sl['btn_text'] ?? ''), (string)($sl['btn_url'] ?? ''), 'on-dark');
                $items[] = ($img ? '<img src="' . e($img) . '" alt="' . e($sl['title'] ?? '') . '"' . ($i ? ' loading="lazy"' : '') . '>' : '') . ($cap !== '' ? '<div class="sl-cap">' . $cap . '</div>' : '');
            }
        }
        if (!$items) return $preview ? ['cls' => 'b-note', 'html' => '<p>В слайдере пока нет фото: выберите галерею или добавьте свои слайды.</p>'] : null;
        return ['cls' => 'b-slider', 'bleed' => $h('wide') === 'full', 'html' => render_slider($items, $d + ['label' => 'Слайдер'])];
    case 'features':
        $o = ''; $ratio = $h('ratio'); $ar = RATIO_CSS[$ratio] ?? 'auto';
        foreach ($rows('items') as $i => $it) {
            $img = clean_image((string)($it['image'] ?? '')); $t = (string)($it['title'] ?? ''); $link = clean_url((string)($it['link_url'] ?? ''));
            $o .= '<div class="ft"' . anim_attr($s, $i, (int)$h('stagger')) . '>' . (($it['icon'] ?? '') !== '' ? '<div class="ft-ico" aria-hidden="true">' . e($it['icon']) . '</div>' : '')
                . ($img ? '<div class="ft-img"><img src="' . e($img) . '" alt="' . e($t) . '" loading="lazy"></div>' : '')
                . ($t !== '' ? '<h3>' . ($link ? '<a href="' . e($link) . '">' . e($t) . '</a>' : e($t)) . '</h3>' : '') . (($it['text'] ?? '') !== '' ? paragraphs((string)$it['text']) : '') . '</div>';
        }
        return ['cls' => 'b-features ftc-' . ($h('card') ?: 'plain') . ' a-' . ($h('align') === 'center' ? 'center' : 'left') . ($h('hover') === 'lift' ? ' ft-lift' : ''), 'cascade' => true,
            'vars' => ['--cols' => max(2, min(4, (int)$h('cols'))), '--gap' => max(8, min(96, (int)$h('gap'))) . 'px', '--fr' => $ar],
            'html' => ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . ($h('intro') !== '' ? '<div class="intro">' . paragraphs((string)$h('intro')) . '</div>' : '') . '<div class="ft-grid">' . $o . '</div>'];
    case 'reviews':
        $items = [];
        foreach ($rows('items') as $it) {
            if (($it['text'] ?? '') === '') continue;
            $ph = clean_image((string)($it['photo'] ?? ''));
            $items[] = '<blockquote class="rv"><p>' . nl2br(e($it['text'])) . '</p><footer>' . ($ph ? '<img src="' . e($ph) . '" alt="" loading="lazy">' : '') . '<span>' . e($it['author'] ?? '') . (($it['role'] ?? '') !== '' ? '<small>' . e($it['role']) . '</small>' : '') . '</span></footer></blockquote>';
        }
        if (!$items) return null;
        $cls = 'b-reviews rv-' . ($h('style') === 'card' ? 'card' : 'plain') . ' rvf-' . (in_array($h('fs'), ['l', 'xl'], true) ? $h('fs') : 'm') . ' a-' . ($h('align') === 'left' ? 'left' : 'center');
        $head = $h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '';
        if ($h('layout') === 'grid') {
            $g = ''; foreach ($items as $i => $it) $g .= '<div class="rv-c"' . anim_attr($s, $i, 90) . '>' . $it . '</div>';
            return ['cls' => $cls . ' rv-grid', 'cascade' => true, 'vars' => ['--cols' => max(1, min(3, (int)$h('cols')))], 'html' => $head . '<div class="rv-wrap">' . $g . '</div>'];
        }
        return ['cls' => $cls, 'html' => $head . render_slider($items, ['mode' => 'slide', 'per' => 1, 'auto' => $h('auto'), 'autoh' => true, 'arrows' => 'on', 'dots' => 'on', 'label' => 'Отзывы'])];
    case 'counters':
        $o = '';
        foreach ($rows('items') as $i => $it) {
            $num = preg_replace('~[^0-9.]~', '', (string)($it['number'] ?? '')); if ($num === '') continue;
            $dec = str_contains($num, '.') ? strlen(substr($num, strpos($num, '.') + 1)) : 0; $suf = (string)($it['suffix'] ?? '');
            if (preg_match('~^\p{L}{2,}~u', $suf)) $suf = "\u{00A0}" . $suf;   // «12 лет»: keep the space the text field trimmed
            $o .= '<div class="ctr"' . anim_attr($s, $i, (int)$h('stagger')) . '><div class="num"' . ($h('count') === 'on' ? ' data-count="' . e($num) . '" data-dec="' . $dec . '" data-suf="' . e($suf) . '"' : '') . '>' . e($num . $suf) . '</div>'
                . (($it['label'] ?? '') !== '' ? '<div class="lab">' . e($it['label']) . '</div>' : '') . '</div>';
        }
        if ($o === '') return null;
        return ['cls' => 'b-counters a-' . ($h('align') === 'left' ? 'left' : 'center') . ($h('lines') === 'lines' ? ' ct-lines' : ''), 'cascade' => true,
            'vars' => ['--cols' => max(2, min(5, (int)$h('cols'))), '--cs' => round(max(60, min(160, (int)$h('size'))) / 100, 2)],
            'html' => ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . '<div class="ct-grid">' . $o . '</div>'];
    case 'faq':
        $o = ''; $ld = []; $first = $h('open') === 'first';
        foreach ($rows('items') as $i => $it) {
            if (($it['q'] ?? '') === '') continue;
            $o .= '<details class="faq-i"' . ($first && !$o ? ' open' : '') . '><summary>' . e($it['q']) . '</summary><div class="faq-a">' . paragraphs((string)($it['a'] ?? '')) . '</div></details>';
            $ld[] = ['@type' => 'Question', 'name' => $it['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string)($it['a'] ?? '')]];
        }
        if ($o === '') return null;
        $json = $h('schema') !== 'off' && $ld ? '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ld], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>' : '';
        return ['cls' => 'b-faq faq-' . (in_array($h('style'), ['cards', 'plain'], true) ? $h('style') : 'lines') . ($h('width') === 'wide' ? ' faq-wide' : ''),
            'html' => ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . $o . $json];
    case 'marquee':
        if ($h('text') === '') return null;
        $unit = '<span class="mq-i">' . e($h('text')) . '</span>' . ($h('sep') !== '' ? '<span class="mq-s" aria-hidden="true">' . e($h('sep')) . '</span>' : '');
        $grp = '<div class="mq-g">' . str_repeat($unit, 6) . '</div>';
        return ['cls' => 'b-marquee mq-' . ($h('look') === 'outline' ? 'outline' : 'fill') . ' ff-' . (in_array($h('font'), ['head', 'body', 'quote', 'accent'], true) ? $h('font') : 'head') . ($h('pause') === 'off' ? '' : ' mq-pause'),
            'bleed' => true, 'attrs' => ' aria-label="' . e($h('text')) . '"',
            'vars' => ['--mq-s' => max(8, min(120, (int)$h('dur'))) . 's', '--ms' => max(14, min(200, (int)$h('size'))), '--mq-dir' => $h('reverse') === 'yes' ? 'reverse' : 'normal'],
            'html' => '<div class="mq-track" aria-hidden="true">' . $grp . $grp . '</div>'];
    case 'video':
        $url = trim((string)$h('url')); $emb = video_embed($url);
        if (!$emb) return $preview ? ['cls' => 'b-note', 'html' => '<p>Вставьте ссылку на видео YouTube или Vimeo.</p>'] : null;
        $poster = clean_image((string)$h('poster')); $ratio = ['16-9' => '16 / 9', '21-9' => '21 / 9', '4-3' => '4 / 3', '1-1' => '1 / 1'][$h('ratio')] ?? '16 / 9';
        $w = in_array($h('width'), ['narrow', 'wide', 'full'], true) ? $h('width') : 'wide';
        return ['tag' => 'figure', 'cls' => 'b-video w-' . $w, 'bleed' => $w === 'full', 'vars' => ['--ar' => $ratio, '--rad' => max(0, min(40, (int)$h('radius'))) . 'px'],
            'html' => '<a class="vid" href="' . e($url) . '" data-video="' . e($emb) . '" rel="noopener">' . ($poster ? '<img src="' . e($poster) . '" alt="" loading="lazy">' : '') . '<span class="vid-play" aria-hidden="true">▶</span><span class="visually-hidden">Смотреть видео</span></a>'
                . ($h('caption') !== '' ? '<figcaption>' . e($h('caption')) . '</figcaption>' : '')];
    case 'posts':
        $opts = ['layout' => $h('layout'), 'card' => $h('card'), 'cols' => $h('cols'), 'ratio' => $h('ratio')] + blog_opts();
        $posts = blog_fetch((int)$h('count') ?: 3, (int)$h('cat'), 0, $preview);
        if (!$posts) return $preview ? ['cls' => 'b-note', 'html' => '<p>В блоге пока нет опубликованных записей.</p>'] : null;
        return ['cls' => 'b-posts', 'html' => ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . render_post_cards($posts, $opts) . ((string)$h('link') !== '0' ? '<p class="more"><a href="/blog">Весь блог</a></p>' : '')];
    case 'divider':
        $st = in_array($h('style'), ['line', 'double', 'dots', 'orn'], true) ? $h('style') : 'line';
        $inner = $st === 'dots' ? '<span class="dv-t">•••</span>' : ($st === 'orn' ? '<span class="dv-l"></span><span class="dv-t">' . e($h('orn')) . '</span><span class="dv-l"></span>' : '<span class="dv-l"></span>');
        return ['cls' => 'b-divider dv-' . $st . ' dvc-' . (in_array($h('color'), ['accent', 'ink'], true) ? $h('color') : 'line'), 'vars' => ['--th' => max(1, min(6, (int)$h('thick'))) . 'px'], 'html' => '<div role="separator">' . $inner . '</div>'];
    case 'content_gallery':
        $src = in_array($h('source'), ['posts', 'pages', 'page_cat'], true) ? $h('source') : 'posts';
        $limit = (int)$h('count');
        $items = [];
        if ($src === 'posts') {
            $cat = (int)$h('category_id');
            foreach (blog_fetch($limit ?: 50, $cat, 0, $preview) as $po) {
                $img = clean_image((string)($po['cover'] ?? ''));
                $items[] = [
                    'url' => '/blog/' . $po['slug'],
                    'title' => $po['title'],
                    'img' => $img,
                    'meta' => !empty($po['published_at']) ? date('d.m.Y', strtotime($po['published_at'])) : '',
                ];
            }
            $moreUrl = '/blog';
        } else {
            $sql = 'SELECT id, title, slug, cover, og_image FROM pages WHERE is_published = 1';
            $params = [];
            if ($src === 'page_cat') {
                $pc = (int)$h('page_cat');
                if ($pc > 0) { $sql .= ' AND category_id = ?'; $params[] = $pc; }
            }
            $sql .= ' ORDER BY sort, id';
            if ($limit > 0) $sql .= ' LIMIT ' . $limit;
            foreach (db_all($sql, $params) as $pg) {
                $img = clean_image((string)($pg['cover'] ?: $pg['og_image'] ?: ''));
                $items[] = [
                    'url' => '/' . $pg['slug'],
                    'title' => $pg['title'],
                    'img' => $img,
                    'meta' => '',
                ];
            }
            $moreUrl = '/';
        }
        if (!$items) return $preview ? ['cls' => 'b-note', 'html' => '<p>Нет элементов для отображения.</p>'] : null;
        $layout = in_array($h('layout'), ['grid', 'masonry', 'magazine', 'overlay', 'filmstrip'], true) ? $h('layout') : 'grid';
        $cols = max(2, min(4, (int)$h('cols')));
        $ratio = $h('ratio') === 'auto' ? 'auto' : (RATIO_CSS[$h('ratio')] ?? '4 / 5');
        $hover = in_array($h('hover'), ['zoom', 'lift', 'dim'], true) ? $h('hover') : 'none';
        $rad = max(0, min(40, (int)$h('radius')));
        $gap = max(8, min(64, (int)$h('gap')));
        $cards = '';
        foreach ($items as $i => $it) {
            $img = $it['img'] ? '<div class="cg-img"><img src="' . e($it['img']) . '" alt="" loading="lazy"></div>' : '<div class="cg-img cg-empty"></div>';
            $title = $h('show_title') !== '0' ? '<h3>' . e($it['title']) . '</h3>' : '';
            $meta = ($h('show_meta') !== '0' && $it['meta'] !== '') ? '<small>' . e($it['meta']) . '</small>' : '';
            $cards .= '<a class="cg-card" href="' . e($it['url']) . '">' . $img . '<div class="cg-body">' . $title . $meta . '</div></a>';
        }
        $more = ((string)$h('link') !== '0' && $src === 'posts') ? '<p class="more"><a href="' . e($moreUrl) . '">Смотреть все</a></p>' : '';
        return [
            'cls' => 'b-content-gallery cg-' . $layout . ' hv-' . $hover,
            'vars' => ['--cols' => $cols, '--gap' => $gap . 'px', '--rad' => $rad . 'px', '--ar' => $ratio === 'auto' ? '4 / 5' : $ratio],
            'html' => ($h('title') !== '' ? '<h2>' . e($h('title')) . '</h2>' : '') . '<div class="cg-grid">' . $cards . '</div>' . $more,
        ];
    case 'columns':
        $n = max(2, min(4, (int)$h('cols')));
        $ratios = preg_split('~[:\s]+~', trim((string)$h('ratios')));
        $fr = [];
        for ($i = 0; $i < $n; $i++) {
            $v = isset($ratios[$i]) && is_numeric($ratios[$i]) ? max(0.1, (float)$ratios[$i]) : 1;
            $fr[] = $v . 'fr';
        }
        $va = in_array($h('valign'), ['center', 'stretch'], true) ? $h('valign') : 'top';
        $align = ['top' => 'start', 'center' => 'center', 'stretch' => 'stretch'][$va];
        $cells = '';
        for ($i = 1; $i <= $n; $i++) {
            $html = (string)$h('c' . $i);
            if ($html === '' && !$preview) continue;
            // Allow limited HTML from admin
            $cells .= '<div class="col-c">' . ($html !== '' ? $html : ($preview ? '<p class="muted">Колонка ' . $i . '</p>' : '')) . '</div>';
        }
        if ($cells === '') return null;
        return [
            'cls' => 'b-columns va-' . $va . ($h('stack_m') === 'keep' ? ' keep-m' : ''),
            'vars' => ['--cols' => implode(' ', $fr), '--gap' => max(0, min(80, (int)$h('gap'))) . 'px', '--va' => $align],
            'html' => '<div class="cols-grid">' . $cells . '</div>',
        ];
    }
    return null;
}
