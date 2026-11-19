<?php
$bo = blog_opts(); $cover = clean_image((string)$post['cover']); $wide = $bo['width'] === 'wide';
$GLOBALS['hero_first'] = $cover && $bo['cover'] === 'hero';
$meta_parts = [];
$meta_parts[] = '<time datetime="' . e(substr((string)$post['published_at'], 0, 10)) . '">' . e(ru_date((string)$post['published_at'])) . '</time>';
if (!empty($post['cat_title'])) $meta_parts[] = '<a href="/blog/category/' . e($post['cat_slug']) . '">' . e($post['cat_title']) . '</a>';
$meta_parts[] = post_read_min($post) . ' мин чтения';
?>
<article class="post <?= $wide ? 'post-wide' : 'post-narrow' ?>">
<?php if ($cover && $bo['cover'] === 'hero'): ?>
  <div class="post-hero"><img src="<?= e($cover) ?>" alt="" fetchpriority="high"><div class="post-hero-in"><div class="pc-meta"><?= implode('<span aria-hidden="true"> · </span>', $meta_parts) ?></div><h1><?= e($post['title']) ?></h1></div></div>
<?php else: ?>
  <header class="post-head"><div class="pc-meta"><?= implode('<span aria-hidden="true"> · </span>', $meta_parts) ?></div><h1><?= e($post['title']) ?></h1>
  <?php if (trim((string)$post['excerpt']) !== ''): ?><p class="lead"><?= e($post['excerpt']) ?></p><?php endif; ?></header>
  <?php if ($cover && $bo['cover'] === 'inline'): ?><figure class="post-cover"><img src="<?= e($cover) ?>" alt="<?= e($post['title']) ?>"></figure><?php endif; ?>
<?php endif; ?>
<div class="post-body"><?= $blocks_html ?></div>
<?php if ($prev || $next): ?><nav class="post-nav" aria-label="Другие записи"><?php if ($prev): ?><a href="/blog/<?= e($prev['slug']) ?>" rel="prev"><small>← Предыдущая</small><?= e($prev['title']) ?></a><?php else: ?><span></span><?php endif; ?><?php if ($next): ?><a href="/blog/<?= e($next['slug']) ?>" rel="next"><small>Следующая →</small><?= e($next['title']) ?></a><?php endif; ?></nav><?php endif; ?>
<?php if ($related): ?><section class="bs post-related"><div class="b b-posts"><h2>Читайте также</h2><?= render_post_cards($related, ['layout' => 'grid', 'card' => $bo['card'], 'cols' => 3] + $bo) ?></div></section><?php endif; ?>
</article>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post['title'], 'datePublished' => date('c', strtotime((string)$post['published_at'])),
    'image' => $meta['image'] ?: null, 'description' => $meta['description'], 'mainEntityOfPage' => $meta['canonical'], 'author' => ['@type' => 'Person', 'name' => $site['title']]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
