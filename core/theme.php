<?php
declare(strict_types=1);

/* ---------- font library (self-hosted, Latin + Cyrillic) ---------- */
function font_registry(): array {
    // key => [css family, file, min weight, max weight, kind, label, size factor]
    return [
        'cormorant'   => ['Cormorant Garamond', 'cormorant', 300, 700, 'serif', 'Cormorant Garamond: изящная антиква', 1.14],
        'playfair'    => ['Playfair Display', 'playfair', 400, 900, 'serif', 'Playfair Display: контрастная антиква', 1.0],
        'lora'        => ['Lora', 'lora', 400, 700, 'serif', 'Lora: мягкая антиква', 1.0],
        'ebgaramond'  => ['EB Garamond', 'ebgaramond', 400, 800, 'serif', 'EB Garamond: классическая гарнитура', 1.08],
        'ptserif'     => ['PT Serif', 'ptserif', 400, 400, 'serif', 'PT Serif: читаемая антиква', 1.0],
        'forum'       => ['Forum', 'forum', 400, 400, 'serif', 'Forum: римская антиква', 1.05],
        'philosopher' => ['Philosopher', 'philosopher', 400, 400, 'serif', 'Philosopher: гуманистическая', 1.05],
        'inter'       => ['Inter', 'inter', 100, 900, 'sans', 'Inter: нейтральная гротеск', 1.0],
        'manrope'     => ['Manrope', 'manrope', 200, 800, 'sans', 'Manrope: современная гротеск', 1.0],
        'montserrat'  => ['Montserrat', 'montserrat', 100, 900, 'sans', 'Montserrat: геометрическая', 0.95],
        'raleway'     => ['Raleway', 'raleway', 100, 900, 'sans', 'Raleway: элегантная гротеск', 1.0],
        'jost'        => ['Jost', 'jost', 100, 900, 'sans', 'Jost: геометрия в духе Futura', 1.02],
        'nunito'      => ['Nunito', 'nunito', 200, 1000, 'sans', 'Nunito: округлая, дружелюбная', 1.0],
        'tenorsans'   => ['Tenor Sans', 'tenorsans', 400, 400, 'sans', 'Tenor Sans: лёгкая, журнальная', 1.0],
        'oswald'      => ['Oswald', 'oswald', 200, 700, 'display', 'Oswald: узкая плакатная', 0.92],
        'unbounded'   => ['Unbounded', 'unbounded', 200, 900, 'display', 'Unbounded: смелая широкая', 0.82],
        'marck'       => ['Marck Script', 'marck', 400, 400, 'script', 'Marck Script: рукописная', 1.2],
        'caveat'      => ['Caveat', 'caveat', 400, 700, 'script', 'Caveat: рукописная лёгкая', 1.25],
    ];
}
function font_options(): array { $o = []; foreach (font_registry() as $k => $f) $o[$k] = $f[5]; return $o; }
function font_stack(string $key): string {
    $f = font_registry()[$key] ?? font_registry()['inter'];
    $fb = ['serif' => 'Georgia,"Times New Roman",serif', 'sans' => 'system-ui,"Segoe UI",Roboto,Arial,sans-serif', 'display' => 'Impact,"Arial Narrow",sans-serif', 'script' => 'cursive'][$f[4]];
    return '"' . $f[0] . '",' . $fb;
}
function font_face_css(array $keys): string {
    $css = ''; $reg = font_registry();
    foreach (array_unique($keys) as $k) {
        if (!isset($reg[$k])) continue;
        [$name, $file, $w0, $w1] = $reg[$k];
        $css .= '@font-face{font-family:"' . $name . '";src:url(/themes/air/fonts/' . $file . '.woff2) format("woff2");font-weight:' . ($w0 === $w1 ? 400 : "$w0 $w1") . ';font-style:normal;font-display:swap}';
    }
    return $css;
}

/* ---------- colour palettes ---------- */
function palettes(): array {
    $p = fn(string $l, string $bg, string $ink, string $muted, string $line, string $accent, string $soft, string $deep) => ['label' => $l, 'c' => compact('bg', 'ink', 'muted', 'line', 'accent', 'soft', 'deep')];
    return [
        'air'      => $p('Белый воздух', '#ffffff', '#1c2124', '#6b7479', '#e5e8e9', '#3f6a62', '#f4f5f4', '#14181a'),
        'sand'     => $p('Песок', '#fbf8f3', '#2a2622', '#7d756c', '#e8e1d6', '#a8714a', '#f2ece2', '#231f1b'),
        'mist'     => $p('Туман', '#f7f9fb', '#1b2430', '#66717f', '#e1e7ee', '#3b5b8f', '#edf1f6', '#121a24'),
        'sage'     => $p('Шалфей', '#f8faf7', '#1f2a24', '#6b786f', '#e1e8df', '#5b7f67', '#eef3ec', '#16201a'),
        'blush'    => $p('Пудра', '#fffafa', '#2b2224', '#85747a', '#f0e3e5', '#b5667a', '#f9eff0', '#26191d'),
        'ice'      => $p('Лёд', '#fcfeff', '#12202b', '#5f7384', '#dde9f1', '#1f7aa8', '#eef6fb', '#0d1b26'),
        'lavender' => $p('Лаванда', '#fbfaff', '#231f33', '#75708a', '#e8e5f4', '#6f5bb0', '#f2f0fb', '#1a1630'),
        'olive'    => $p('Олива', '#fafaf5', '#25261d', '#76785f', '#e6e7d8', '#6b7a2e', '#f0f1e3', '#1c1e14'),
        'mono'     => $p('Графика', '#ffffff', '#111111', '#6e6e6e', '#e4e4e4', '#111111', '#f3f3f3', '#0a0a0a'),
        'graphite' => $p('Графит (тёмная)', '#14181a', '#eef1f2', '#9aa4a9', '#2a3134', '#8fc4b5', '#1b2023', '#0e1112'),
        'noir'     => $p('Ночь (тёмная)', '#161416', '#f3eee9', '#a39a94', '#2c282b', '#e0a96d', '#1e1b1e', '#0f0d0f'),
    ];
}
function color_luma(string $hex): float {
    $h = ltrim($hex, '#'); [$r, $g, $b] = [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))];
    return ($r * 299 + $g * 587 + $b * 114) / 1000;
}
function palette_colors(): array {
    $all = palettes(); $key = dz('palette');
    if ($key !== 'custom' && isset($all[$key])) return $all[$key]['c'];
    $c = $all['air']['c'];
    foreach (array_keys($c) as $k) { $v = setting('c_' . $k); if (preg_match('~^#[0-9a-fA-F]{6}$~', $v)) $c[$k] = $v; }
    return $c;
}

/* ---------- design settings schema (drives the "Дизайн" page) ---------- */
function f_color(string $k, string $l, string $def, string $g = ''): array { return ['k' => $k, 't' => 'color', 'l' => $l, 'def' => $def, 'g' => $g]; }
function f_string(string $k, string $l, string $def = '', string $g = ''): array { return ['k' => $k, 't' => 'string', 'l' => $l, 'def' => $def, 'g' => $g]; }
function onoff(): array { return ['1' => 'Да', '0' => 'Нет']; }

function design_fields(): array {
    $pal = array_merge(array_map(fn($p) => $p['label'], palettes()), ['custom' => 'Свои цвета']);
    $ratios = ['4-3' => '4:3', '3-2' => '3:2', '16-9' => '16:9', '1-1' => 'Квадрат', '4-5' => 'Вертикаль 4:5'];
    $fonts = font_options();
    return [
        'Палитра' => array_merge([f_select('palette', 'Цветовая схема', $pal)], array_map(null, [
            f_color('c_bg', 'Фон', '#ffffff'), f_color('c_ink', 'Текст', '#1c2124'), f_color('c_muted', 'Второстепенный текст', '#6b7479'), f_color('c_line', 'Линии', '#e5e8e9'),
            f_color('c_accent', 'Акцент', '#3f6a62'), f_color('c_soft', 'Светлая подложка блоков', '#f4f5f4'), f_color('c_deep', 'Тёмная подложка блоков', '#14181a')])),
        'Шрифты' => [
            f_select('f_head', 'Заголовки', $fonts), f_select('f_body', 'Основной текст', $fonts) + ['def' => 'manrope'], f_select('f_quote', 'Цитаты и отзывы', $fonts), f_select('f_accent', 'Акцентный (подписи, бегущая строка)', $fonts) + ['def' => 'marck'],
            f_select('f_ui', 'Меню и кнопки', ['body' => 'Как основной текст', 'head' => 'Как заголовки', 'accent' => 'Акцентный']),
            f_select('h_weight', 'Начертание заголовков', ['400' => 'Обычное', '300' => 'Тонкое', '500' => 'Среднее', '600' => 'Полужирное', '700' => 'Жирное']),
            f_range('h_size', 'Размер заголовков', 70, 140, 100, 5, '%'), f_range('h_ls', 'Межбуквенное расстояние заголовков', -4, 12, -1, 1, '/100 em'),
            f_select('h_case', 'Регистр заголовков', ['none' => 'Как написано', 'uppercase' => 'ВЕРХНИЙ РЕГИСТР']),
            f_range('b_size', 'Размер основного текста', 90, 130, 100, 5, '%'), f_range('b_lh', 'Межстрочный интервал', 140, 220, 170, 5, '%'),
            f_select('q_style', 'Цитаты', ['normal' => 'Прямой шрифт', 'italic' => 'Курсив']),
        ],
        'Размеры и расстояния' => [
            f_select('width', 'Ширина содержимого', ['1280' => 'Стандартная (1280 px)', '1100' => 'Узкая (1100 px)', '1440' => 'Широкая (1440 px)', '1680' => 'Очень широкая (1680 px)', 'full' => 'На всю ширину экрана']),
            f_range('sp', 'Расстояния между блоками', 40, 220, 100, 5, '%'), f_range('gs', 'Расстояния внутри блоков (колонки, сетки)', 40, 220, 100, 5, '%'),
            f_range('radius', 'Скругление карточек и полей', 0, 40, 0, 1, 'px'),
            f_select('btn_style', 'Кнопки', ['pill' => 'Овальные', 'round' => 'Со скруглением как у карточек', 'square' => 'Прямоугольные']),
        ],
        'Шапка' => [
            f_select('header_layout', 'Расположение элементов', ['split' => 'Логотип слева, меню справа', 'center' => 'Всё по центру', 'logo-center' => 'Логотип по центру, меню по краям', 'menu-left' => 'Меню слева, логотип справа']),
            f_select('header_order', 'Порядок блоков', ['logo-nav-cta' => 'Лого · Меню · Кнопка', 'logo-cta-nav' => 'Лого · Кнопка · Меню', 'nav-logo-cta' => 'Меню · Лого · Кнопка']),
            f_range('logo_size', 'Размер названия / логотипа', 70, 180, 100, 5, '%'),
            f_range('header_h', 'Высота шапки', 48, 140, 72, 2, 'px'),
            f_range('header_pad_y', 'Вертикальные отступы', 0, 48, 16, 2, 'px'),
            f_color('header_bg', 'Фон шапки', ''),
            f_range('header_bg_a', 'Прозрачность фона', 0, 100, 100, 5, '%'),
            f_color('header_bg_scroll', 'Фон при прокрутке (sticky)', ''),
            f_range('header_bg_scroll_a', 'Прозрачность фона при прокрутке', 0, 100, 95, 5, '%'),
            f_select('header_blur', 'Размытие фона (glass)', ['0' => 'Нет', '1' => 'Да']),
            f_color('header_link', 'Цвет ссылок меню', ''),
            f_color('header_link_h', 'Цвет ссылок при наведении', ''),
            f_color('header_link_scroll', 'Цвет ссылок при прокрутке', ''),
            f_select('header_sticky', 'Закреплять при прокрутке', ['0' => 'Нет', '1' => 'Да']),
            f_select('header_over', 'Поверх обложки (прозрачная)', ['0' => 'Нет', '1' => 'Да']),
            f_color('header_over_link', 'Цвет ссылок поверх обложки', '#ffffff'),
            f_select('header_border', 'Нижняя граница', ['0' => 'Нет', '1' => 'Тонкая линия']),
            f_string('header_cta_text', 'Текст кнопки в шапке', ''),
            f_string('header_cta_url', 'Ссылка кнопки в шапке', ''),
            f_select('header_cta_style', 'Стиль кнопки', ['solid' => 'Заливка', 'outline' => 'Контур', 'text' => 'Текстовая']),
            f_select('header_dropdown', 'Выпадающее меню', ['hover' => 'По наведению', 'click' => 'По клику']),
            f_range('header_dd_radius', 'Скругление выпадающего меню', 0, 24, 12, 1, 'px'),
            f_color('header_dd_bg', 'Фон выпадающего меню', ''),
        ],
        'Мобильное меню' => [
            f_select('mbtn_style', 'Стиль кнопки «Меню»', ['pill' => 'Капсула с текстом', 'icon' => 'Только иконка', 'minimal' => 'Минимальная']),
            f_string('mbtn_label', 'Текст кнопки', 'Меню'),
            f_select('mbtn_icon', 'Иконка', ['bars' => 'Три линии', 'dots' => 'Три точки', 'grid' => 'Сетка']),
            f_color('mbtn_bg', 'Фон кнопки', ''),
            f_color('mbtn_color', 'Цвет иконки/текста', ''),
            f_color('mbtn_border', 'Цвет рамки', ''),
            f_color('mbtn_bg_h', 'Фон при наведении', ''),
            f_color('mbtn_color_h', 'Цвет при наведении', ''),
            f_color('mbtn_border_h', 'Рамка при наведении', ''),
            f_range('mbtn_radius', 'Скругление кнопки', 0, 99, 99, 1, 'px'),
            f_select('mnav_style', 'Вид открытого меню', ['dropdown' => 'Выпадает вниз', 'drawer' => 'Шторка справа', 'fullscreen' => 'На весь экран']),
            f_color('mnav_bg', 'Фон мобильного меню', ''),
            f_color('mnav_link', 'Цвет ссылок в мобильном меню', ''),
            f_range('mnav_fs', 'Размер ссылок', 90, 160, 110, 5, '%'),
        ],
        'Виджет контактов' => [
            f_select('cw_on', 'Показывать кнопку-визитку', ['1' => 'Да', '0' => 'Нет']),
            f_string('cw_label', 'Подпись на кнопке', 'Связаться'),
            f_color('cw_btn_bg', 'Фон кнопки', ''),
            f_color('cw_btn_color', 'Цвет иконки/текста', ''),
            f_color('cw_btn_bg_h', 'Фон при наведении', ''),
            f_string('cw_name', 'Имя на визитке', ''),
            f_string('cw_role', 'Подпись (фотограф, …)', ''),
            f_string('cw_photo', 'Фото (путь или URL)', ''),
            f_string('cw_text', 'Короткий текст', ''),
            f_string('cw_telegram', 'Telegram (ссылка или @username)', ''),
            f_string('cw_whatsapp', 'WhatsApp (номер или ссылка)', ''),
            f_string('cw_vk', 'VK (ссылка)', ''),
            f_select('cw_show_email', 'Показывать email из настроек', ['1' => 'Да', '0' => 'Нет']),
            f_select('cw_show_phone', 'Показывать телефон из настроек', ['1' => 'Да', '0' => 'Нет']),
        ],
        'Подвал' => [
            f_select('footer_layout', 'Расположение', ['center' => 'По центру', 'left' => 'Слева', 'split' => 'Текст слева, ссылки справа', 'cols' => 'Колонки']),
            f_range('footer_pad_y', 'Вертикальные отступы', 16, 120, 48, 4, 'px'),
            f_color('footer_bg', 'Фон подвала', ''),
            f_color('footer_color', 'Цвет текста', ''),
            f_color('footer_link', 'Цвет ссылок', ''),
            f_color('footer_link_h', 'Цвет ссылок при наведении', ''),
            f_select('footer_border', 'Верхняя граница', ['0' => 'Нет', '1' => 'Тонкая линия']),
            f_string('footer_cta_text', 'Текст кнопки в подвале', ''),
            f_string('footer_cta_url', 'Ссылка кнопки в подвале', ''),
            f_select('footer_show_social', 'Показывать контакты/соцсети', ['1' => 'Да', '0' => 'Нет']),
            f_select('footer_show_menu', 'Показывать ссылки меню', ['0' => 'Нет', '1' => 'Да']),
        ],
        'Эффекты' => [
            f_select('fx', 'Анимации и параллакс', ['1' => 'Включены', '0' => 'Выключены везде']),
            f_select('fx_speed', 'Скорость анимаций', ['1' => 'Обычная', '0.6' => 'Быстрая', '1.5' => 'Медленная', '2' => 'Очень медленная']),
            f_select('page_fade', 'Плавное открытие страниц', ['1' => 'Да', '0' => 'Нет']),
            f_select('smooth', 'Плавная прокрутка к якорям', ['1' => 'Да', '0' => 'Нет']),
        ],
        'Блог' => [
            f_string('blog_title', 'Заголовок раздела', 'Блог'), f_string('blog_intro', 'Подзаголовок', ''),
            f_select('blog_layout', 'Вариант оформления', ['grid' => 'Сетка карточек', 'list' => 'Список с фото сбоку', 'magazine' => 'Журнал (первая запись крупно)', 'masonry' => 'Каскад', 'minimal' => 'Минимализм: только текст']),
            f_select('blog_card', 'Вид карточки', ['plain' => 'Без рамки', 'card' => 'Карточка', 'overlay' => 'Текст поверх фото']),
            f_range('blog_cols', 'Колонок', 2, 4, 3, 1, ''), f_select('blog_ratio', 'Пропорции фото в карточке', $ratios), f_range('blog_per', 'Записей на странице', 4, 24, 9, 1, ''),
            f_select('blog_date', 'Показывать дату', onoff()), f_select('blog_cat', 'Показывать рубрику', onoff()), f_select('blog_excerpt', 'Показывать анонс', onoff()), f_select('blog_time', 'Показывать время чтения', onoff()),
            f_select('blog_post_cover', 'Обложка записи', ['hero' => 'Большая, на всю ширину', 'inline' => 'Внутри текста', 'none' => 'Без обложки']),
            f_select('blog_post_width', 'Ширина записи', ['narrow' => 'Узкая (удобно читать)', 'wide' => 'Широкая']), f_select('blog_related', 'Похожие записи под текстом', onoff()),
        ],
    ];
}
function design_flat(): array { $o = []; foreach (design_fields() as $fs) foreach ($fs as $f) $o[$f['k']] = $f; return $o; }
/** Design setting with its default from the schema. */
function dz(string $key): string {
    static $defs = null;
    if ($defs === null) { $defs = field_defaults(array_values(design_flat())); }
    return setting($key, (string)($defs[$key] ?? ''));
}


/** Color from design setting or fallback (empty setting = use fallback). */
function hdr_color(string $key, string $fallback): string {
    $v = dz($key);
    return preg_match('~^#[0-9a-fA-F]{6}$~', $v) ? $v : $fallback;
}

/* ---------- CSS output ---------- */
function theme_style(): string {
    $c = palette_colors(); $reg = font_registry();
    $fh = isset($reg[dz('f_head')]) ? dz('f_head') : 'cormorant'; $fb = isset($reg[dz('f_body')]) ? dz('f_body') : 'manrope';
    $fq = isset($reg[dz('f_quote')]) ? dz('f_quote') : $fh; $fa = isset($reg[dz('f_accent')]) ? dz('f_accent') : 'marck';
    $ui = ['head' => $fh, 'accent' => $fa][dz('f_ui')] ?? $fb;
    $hw = max($reg[$fh][2], min($reg[$fh][3], (int)dz('h_weight')));
    $maxw = dz('width') === 'full' ? '100%' : max(900, min(1920, (int)dz('width'))) . 'px';
    $btn = ['pill' => '99px', 'round' => 'var(--radius)', 'square' => '0px'][dz('btn_style')] ?? '99px';
    $vars = [
        '--bg' => $c['bg'], '--ink' => $c['ink'], '--muted' => $c['muted'], '--line' => $c['line'], '--accent' => $c['accent'], '--soft' => $c['soft'], '--deep' => $c['deep'],
        '--on-accent' => color_luma($c['accent']) >= 150 ? '#1c2124' : '#ffffff',
        '--font-head' => font_stack($fh), '--font-body' => font_stack($fb), '--font-quote' => font_stack($fq), '--font-accent' => font_stack($fa), '--font-ui' => font_stack($ui),
        '--h-weight' => $hw, '--h-scale' => round(max(70, min(140, (int)dz('h_size'))) / 100 * $reg[$fh][6], 3), '--h-ls' => round((int)dz('h_ls') / 100, 2) . 'em',
        '--h-tt' => dz('h_case') === 'uppercase' ? 'uppercase' : 'none', '--b-scale' => round(max(90, min(130, (int)dz('b_size'))) / 100, 2), '--b-lh' => round(max(140, min(220, (int)dz('b_lh'))) / 100, 2),
        '--q-style' => dz('q_style') === 'italic' ? 'italic' : 'normal', '--maxw' => $maxw, '--sp' => round(max(40, min(220, (int)dz('sp'))) / 100, 2), '--gs' => round(max(40, min(220, (int)dz('gs'))) / 100, 2),
        '--radius' => max(0, min(40, (int)dz('radius'))) . 'px', '--btn-r' => $btn, '--dur-k' => (float)dz('fx_speed') ?: 1, '--logo-k' => round(max(70, min(180, (int)dz('logo_size'))) / 100, 2),
        // header
        '--hdr-h' => max(48, min(140, (int)dz('header_h'))) . 'px',
        '--hdr-py' => max(0, min(48, (int)dz('header_pad_y'))) . 'px',
        '--hdr-bg' => hdr_color('header_bg', $c['bg']),
        '--hdr-bg-a' => round(max(0, min(100, (int)dz('header_bg_a'))) / 100, 2),
        '--hdr-bg-s' => hdr_color('header_bg_scroll', $c['bg']),
        '--hdr-bg-s-a' => round(max(0, min(100, (int)dz('header_bg_scroll_a'))) / 100, 2),
        '--hdr-link' => hdr_color('header_link', $c['muted']),
        '--hdr-link-h' => hdr_color('header_link_h', $c['ink']),
        '--hdr-link-s' => hdr_color('header_link_scroll', $c['ink']),
        '--hdr-over-link' => hdr_color('header_over_link', '#ffffff'),
        '--hdr-dd-r' => max(0, min(24, (int)dz('header_dd_radius'))) . 'px',
        '--hdr-dd-bg' => hdr_color('header_dd_bg', $c['bg']),
        // mobile btn
        '--mbtn-bg' => hdr_color('mbtn_bg', 'transparent'),
        '--mbtn-c' => hdr_color('mbtn_color', $c['ink']),
        '--mbtn-bd' => hdr_color('mbtn_border', $c['line']),
        '--mbtn-bg-h' => hdr_color('mbtn_bg_h', 'color-mix(in srgb, ' . $c['accent'] . ' 14%, transparent)'),
        '--mbtn-c-h' => hdr_color('mbtn_color_h', $c['ink']),
        '--mbtn-bd-h' => hdr_color('mbtn_border_h', 'color-mix(in srgb, ' . $c['accent'] . ' 45%, ' . $c['line'] . ')'),
        '--mbtn-r' => max(0, min(99, (int)dz('mbtn_radius'))) . 'px',
        '--mnav-bg' => hdr_color('mnav_bg', $c['bg']),
        '--mnav-link' => hdr_color('mnav_link', $c['ink']),
        '--mnav-fs' => round(max(90, min(160, (int)dz('mnav_fs'))) / 100, 2),
        '--cw-bg' => hdr_color('cw_btn_bg', $c['accent']),
        '--cw-c' => hdr_color('cw_btn_color', color_luma(hdr_color('cw_btn_bg', $c['accent'])) >= 150 ? '#1c2124' : '#ffffff'),
        '--cw-bg-h' => hdr_color('cw_btn_bg_h', $c['ink']),
        // footer
        '--ftr-py' => max(16, min(120, (int)dz('footer_pad_y'))) . 'px',
        '--ftr-bg' => hdr_color('footer_bg', 'transparent'),
        '--ftr-c' => hdr_color('footer_color', $c['muted']),
        '--ftr-link' => hdr_color('footer_link', $c['muted']),
        '--ftr-link-h' => hdr_color('footer_link_h', $c['ink']),
    ];
    $css = font_face_css([$fh, $fb, $fq, $fa, $ui]) . ':root{' . implode(';', array_map(fn($k, $v) => "$k:$v", array_keys($vars), $vars)) . ';color-scheme:' . (color_luma($c['bg']) < 110 ? 'dark' : 'light') . '}';
    return $css . (dz('smooth') === '1' ? 'html{scroll-behavior:smooth}' : '');
}
function theme_preloads(): string {
    $reg = font_registry(); $o = '';
    foreach (array_unique([dz('f_head'), dz('f_body')]) as $k) if (isset($reg[$k])) $o .= '<link rel="preload" href="/themes/air/fonts/' . $reg[$k][1] . '.woff2" as="font" type="font/woff2" crossorigin>';
    return $o;
}
