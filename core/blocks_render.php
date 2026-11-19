<?php
declare(strict_types=1);

function gallery_mode(string $want, string $fallback): string {
    if (isset(GALLERY_MODES[$want])) return $want;
    return isset(GALLERY_MODES[$fallback]) ? $fallback : 'masonry';
}

/** Generic carousel. $items are ready-made inner HTML strings. JS in themes/air/ui.js */
function render_slider(array $items, array $o, string $extraAttr = ''): string {
    $n = count($items); if (!$n) return '';
    $mode = in_array($o['mode'] ?? 'slide', ['slide', 'fade', 'strip'], true) ? $o['mode'] : 'slide';
    $per = $mode === 'slide' ? max(1, min(4, (int)($o['per'] ?? 1))) : 1;
    $auto = max(0, min(12, (int)($o['auto'] ?? 0)));
    $vars = ['--h' => max(30, min(100, (int)($o['sh'] ?? 70))) . 'vh', '--per' => $per, '--sgap' => ($mode === 'fade' ? 0 : max(0, min(64, (int)($o['gap'] ?? 0)))) . 'px',
        '--rad' => max(0, min(40, (int)($o['radius'] ?? 0))) . 'px', '--ov-c' => 'rgba(10,14,16,' . round(max(0, min(70, (int)($o['ov'] ?? 25))) / 100, 2) . ')'];
    $cls = 'sl sl-' . $mode . (($o['fit'] ?? 'cover') === 'contain' ? ' fit-contain' : '') . (($o['kb'] ?? 'off') === 'on' ? ' kb' : '') . (!empty($o['autoh']) ? ' sl-auto' : '');
    $h = '<div class="' . $cls . '" data-slider data-auto="' . ($auto * 1000) . '" role="region" aria-roledescription="carousel" aria-label="' . e((string)($o['label'] ?? 'Слайдер')) . '"' . style_attr($vars) . $extraAttr . '><div class="sl-track">';
    foreach ($items as $i => $html) $h .= '<div class="sl-item" role="group" aria-roledescription="slide" aria-label="' . ($i + 1) . ' / ' . $n . '">' . $html . '</div>';
    $h .= '</div>';
    if ($n > 1 && ($o['arrows'] ?? 'on') !== 'off') $h .= '<button type="button" class="sl-btn sl-prev" aria-label="Назад">‹</button><button type="button" class="sl-btn sl-next" aria-label="Вперёд">›</button>';
    if ($n > 1 && ($o['dots'] ?? 'on') !== 'off') $h .= '<div class="sl-dots"></div>';
    return $h . '</div>';
}

function render_gallery_grid(array $g, array $photos, array $o): string {
    $mode = gallery_mode((string)($o['mode'] ?? ''), (string)($g['layout'] ?? ''));
    $reveal = (string)($o['reveal'] ?? 'up'); $reveal = isset(ANIM_LABELS[$reveal]) ? $reveal : 'none';
    $lb = ($o['lightbox'] ?? 'on') !== 'off';
    $capt = in_array($o['capt'] ?? 'none', ['hover', 'below'], true) ? $o['capt'] : 'none';
    $fl = in_array($o['filter'] ?? '', ['bw', 'bwhover', 'muted', 'warm', 'vivid'], true) ? ' f-' . $o['filter'] : '';
    $fr = in_array($o['frame'] ?? '', ['shadow', 'border', 'matte'], true) ? ' fr-' . $o['frame'] : '';
    $look = $fl . $fr . ' cp-' . $capt;
    $dim = fn(array $p) => ' width="' . (int)$p['width'] . '" height="' . (int)$p['height'] . '"';

    if ($mode === 'slider' || $mode === 'filmstrip') {
        $items = [];
        foreach ($photos as $i => $p) {
            $img = '<img src="' . e(upload_url((int)$g['id'], $p['file'])) . '"' . $dim($p) . ' alt="' . e($p['alt'] ?: $g['title']) . '"' . ($i > 0 ? ' loading="lazy"' : '') . '>';
            if ($lb) $img = '<a href="' . e(upload_url((int)$g['id'], $p['file'])) . '">' . $img . '</a>';
            $items[] = $img . ($capt !== 'none' && $p['alt'] !== '' ? '<span class="cap">' . e($p['alt']) . '</span>' : '');
        }
        return '<div class="gal-sl' . $look . '">' . render_slider($items, ['mode' => $mode === 'slider' ? 'slide' : 'strip', 'label' => (string)$g['title']] + $o, $lb ? ' data-lightbox' : '') . '</div>';
    }

    $cols = max(2, min(6, (int)$o['cols'])); $st = max(0, (int)$o['stagger']);
    $ar = ($o['ratio'] ?? 'auto') === 'auto' ? '1 / 1' : (RATIO_CSS[$o['ratio']] ?? '1 / 1');
    $vars = ['--cols' => $cols, '--cols-t' => min($cols, 3), '--cols-m' => min($cols, 2), '--gap' => (int)$o['gap'] . 'px', '--rad' => (int)$o['radius'] . 'px', '--ar' => $ar, '--rowh' => max(100, min(600, (int)($o['rowh'] ?? 260))) . 'px'];
    $hover = in_array($o['hover'] ?? '', ['zoom', 'dim', 'lift'], true) ? $o['hover'] : 'none';
    $html = '<div class="photos ' . $mode . ' hv-' . $hover . $look . '"' . ($lb ? ' data-lightbox' : '') . style_attr($vars) . '>';
    foreach ($photos as $i => $p) {
        $style = '--delay:' . (($i % $cols) * $st) . 'ms' . ($mode === 'justified' ? ';--r:' . round($p['width'] / max(1, $p['height']), 4) : '');
        $anim = ($reveal !== 'none' ? ' data-anim="' . e($reveal) . '"' : '') . ' style="' . e($style) . '"';
        $c = '';
        if ($mode === 'collage') { $k = $i % 7; $c = ' class="' . ($k === 0 ? 'c-big' : ($k === 3 ? 'c-wide' : ($k === 5 ? 'c-tall' : ''))) . '"'; }
        $img = '<img src="' . e(upload_url((int)$g['id'], $p['thumb'])) . '"' . $dim($p) . ' alt="' . e($p['alt'] ?: $g['title']) . '"' . ($i > 2 ? ' loading="lazy"' : '') . '>'
            . ($capt !== 'none' && $p['alt'] !== '' ? '<span class="cap">' . e($p['alt']) . '</span>' : '');
        $html .= $lb ? '<a href="' . e(upload_url((int)$g['id'], $p['file'])) . '"' . $c . $anim . '>' . $img . '</a>' : '<div' . $c . $anim . '>' . $img . '</div>';
    }
    return $html . '</div>';
}

/** data-anim + stagger attributes for children of a "cascade" block. */
function anim_attr(array $s, int $i, int $step, string $style = ''): string {
    $a = (string)($s['anim'] ?? 'none');
    $d = $i * $step + (int)($s['delay'] ?? 0);
    $st = trim($style . ($a !== 'none' ? '--delay:' . $d . 'ms' : ''), ';');
    return ($a !== 'none' && isset(ANIM_LABELS[$a]) ? ' data-anim="' . e($a) . '"' . (in_array($s['dur'] ?? '', ['fast', 'slow'], true) ? ' data-dur="' . e($s['dur']) . '"' : '') : '') . ($st !== '' ? ' style="' . e($st) . '"' : '');
}

/** Wraps block markup in the common frame: padding, background (colour or photo), visibility, appearance animation, text effect. */
function block_wrap(string $type, array $d, array $p): string {
    $s = array_replace(style_defaults($type), is_array($d['_s'] ?? null) ? $d['_s'] : []);
    $bs = 'bs bs-' . $type . (!empty($p['bleed']) ? ' bs-bleed' : '');
    if (in_array($s['bg'], ['soft', 'dark', 'accent'], true)) $bs .= ' bg-' . $s['bg'];
    if (in_array($s['hide'], ['hide-m', 'hide-d'], true)) $bs .= ' ' . $s['hide'];
    $bsStyle = '--pt:' . max(0, min(240, (int)$s['pt'])) . 'px;--pb:' . max(0, min(240, (int)$s['pb'])) . 'px';
    $bg = '';
    if ($img = clean_image((string)($s['bgimg'] ?? ''))) {
        [$pxc, $pxa] = par_attrs((int)($s['bgpar'] ?? 0));
        $bs .= ' has-bgimg';
        $bsStyle .= ';--bg-ov:rgba(10,14,16,' . round(max(0, min(90, (int)$s['bgov'])) / 100, 2) . ')';
        $bg = '<div class="bgm' . $pxc . '"' . $pxa . ' aria-hidden="true"><img src="' . e($img) . '" alt="" loading="lazy"></div>';
    }
    $vars = ($p['vars'] ?? []) + ['--w' => max(30, min(100, (int)$s['w']))];
    $attr = '';
    if (empty($p['cascade']) && isset(ANIM_LABELS[$s['anim']]) && $s['anim'] !== 'none') {
        $attr .= ' data-anim="' . e($s['anim']) . '"';
        if (in_array($s['dur'], ['fast', 'slow'], true)) $attr .= ' data-dur="' . e($s['dur']) . '"';
        if ((int)$s['delay'] > 0) $vars['--delay'] = (int)$s['delay'] . 'ms';
    }
    $cls = $p['cls'];
    if (in_array($s['tf'] ?? '', ['head', 'body', 'quote', 'accent'], true)) $cls .= ' tf-' . $s['tf'];
    if (in_array($s['tfx'] ?? '', ['words', 'letters', 'typewriter', 'gradient', 'outline', 'underline'], true)) $attr .= ' data-tfx="' . e($s['tfx']) . '"';
    $tag = $p['tag'] ?? 'section';
    return '<div class="' . $bs . '" style="' . e($bsStyle) . '">' . $bg
        . '<' . $tag . ' class="b ' . $cls . '"' . $attr . style_attr($vars) . ($p['attrs'] ?? '') . '>' . $p['html'] . '</' . $tag . '></div>';
}
