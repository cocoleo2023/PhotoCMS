<?php
declare(strict_types=1);

function ru_date(string $dt): string {
    static $m = [1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    $t = strtotime($dt); if (!$t) return '';
    return (int)date('j', $t) . ' ' . $m[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function blog_opts(): array {
    return ['layout' => dz('blog_layout'), 'card' => dz('blog_card'), 'cols' => dz('blog_cols'), 'ratio' => dz('blog_ratio'), 'per' => (int)dz('blog_per'),
        'date' => dz('blog_date'), 'cat' => dz('blog_cat'), 'excerpt' => dz('blog_excerpt'), 'time' => dz('blog_time'),
        'cover' => dz('blog_post_cover'), 'width' => dz('blog_post_width'), 'related' => dz('blog_related'), 'title' => dz('blog_title'), 'intro' => dz('blog_intro')];
}

function blog_where(int $catId, bool $preview): array {
    $w = $preview ? '1=1' : 'p.is_published = 1 AND p.published_at <= NOW()'; $p = [];
    if ($catId > 0) { $w .= ' AND p.category_id = ?'; $p[] = $catId; }
    return [$w, $p];
}
function blog_fetch(int $limit, int $catId = 0, int $offset = 0, bool $preview = false): array {
    [$w, $p] = blog_where($catId, $preview);
    return db_all('SELECT p.*, c.title AS cat_title, c.slug AS cat_slug FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE ' . $w
        . ' ORDER BY p.published_at DESC, p.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), $p);
}
function blog_count(int $catId = 0, bool $preview = false): int {
    [$w, $p] = blog_where($catId, $preview);
    return (int)db_value('SELECT COUNT(*) FROM posts p WHERE ' . $w, $p);
}

function post_text(array $post): string {
    $t = '';
    foreach (json_decode((string)($post['blocks'] ?? ''), true) ?: [] as $b) {
        foreach (['title', 'subtitle', 'body', 'text', 'code'] as $k) if (!empty($b['data'][$k]) && is_string($b['data'][$k])) $t .= ' ' . strip_tags($b['data'][$k]);
        foreach (['items', 'slides', 'plans'] as $k) foreach ($b['data'][$k] ?? [] as $row) if (is_array($row)) foreach (['text', 'a', 'items'] as $kk) if (!empty($row[$kk]) && is_string($row[$kk])) $t .= ' ' . strip_tags($row[$kk]);
    }
    return trim(preg_replace('~\s+~u', ' ', $t));
}
function post_excerpt(array $p, int $len = 170): string {
    $x = trim((string)($p['excerpt'] ?? '')); if ($x !== '') return $x;
    return mb_strimwidth(post_text($p), 0, $len, '…', 'UTF-8');
}
function post_read_min(array $p): int { return max(1, (int)ceil(mb_substr_count(post_text($p), ' ') / 170)); }
function post_url(array $p): string { return '/blog/' . $p['slug']; }

function render_post_cards(array $posts, array $o): string {
    $layout = in_array($o['layout'] ?? '', ['grid', 'list', 'magazine', 'masonry', 'minimal'], true) ? $o['layout'] : 'grid';
    $card = in_array($o['card'] ?? '', ['plain', 'card', 'overlay'], true) ? $o['card'] : 'plain';
    if (in_array($layout, ['list', 'minimal'], true) && $card === 'overlay') $card = 'card';
    $vars = ['--cols' => max(2, min(4, (int)($o['cols'] ?? 3))), '--pr' => RATIO_CSS[$o['ratio'] ?? '4-3'] ?? '4 / 3'];
    $h = '<div class="posts pl-' . $layout . ' pk-' . $card . '"' . style_attr($vars) . '>';
    foreach ($posts as $i => $p) {
        $url = e(post_url($p)); $cover = clean_image((string)$p['cover']);
        $meta = [];
        if (($o['date'] ?? '1') === '1') $meta[] = '<time datetime="' . e(substr((string)$p['published_at'], 0, 10)) . '">' . e(ru_date((string)$p['published_at'])) . '</time>';
        if (($o['cat'] ?? '1') === '1' && !empty($p['cat_title'])) $meta[] = '<a href="/blog/category/' . e($p['cat_slug']) . '">' . e($p['cat_title']) . '</a>';
        if (($o['time'] ?? '1') === '1') $meta[] = post_read_min($p) . ' мин чтения';
        $lead = $layout === 'magazine' && $i === 0 ? ' pc-lead' : '';
        $h .= '<article class="pc' . $lead . ($cover ? '' : ' no-img') . '" data-anim="up" style="--delay:' . (($i % 3) * 80) . 'ms">'
            . ($cover ? '<a class="pc-img" href="' . $url . '" tabindex="-1" aria-hidden="true"><img src="' . e($cover) . '" alt="" loading="' . ($i < 2 ? 'eager' : 'lazy') . '"></a>' : '')
            . '<div class="pc-body">' . ($meta ? '<div class="pc-meta">' . implode('<span aria-hidden="true"> · </span>', $meta) . '</div>' : '')
            . '<h3><a href="' . $url . '">' . e($p['title']) . '</a></h3>' . (($o['excerpt'] ?? '1') === '1' && ($ex = post_excerpt($p)) !== '' ? '<p>' . e($ex) . '</p>' : '') . '</div></article>';
    }
    return $h . '</div>';
}
