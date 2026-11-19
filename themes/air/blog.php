<?php $bo = blog_opts(); ?>
<header class="page-title"><h1><?= e($cat ? $cat['title'] : ($bo['title'] ?: 'Блог')) ?></h1>
<?php if (!$cat && trim($bo['intro']) !== ''): ?><div class="lead"><?= paragraphs($bo['intro']) ?></div><?php endif; ?></header>
<?php if ($cats): ?><nav class="chips" aria-label="Рубрики"><a href="/blog"<?= !$cat ? ' aria-current="page"' : '' ?>>Все записи</a>
<?php foreach ($cats as $c): ?><a href="/blog/category/<?= e($c['slug']) ?>"<?= $cat && (int)$cat['id'] === (int)$c['id'] ? ' aria-current="page"' : '' ?>><?= e($c['title']) ?></a><?php endforeach; ?></nav><?php endif; ?>
<div class="bs"><div class="b b-posts">
<?php if (!$posts): ?><p class="empty">Скоро здесь появятся записи.</p><?php else: ?><?= render_post_cards($posts, $bo) ?><?php endif; ?>
<?php if ($pages > 1): $base = $cat ? '/blog/category/' . $cat['slug'] : '/blog'; ?>
<nav class="pager" aria-label="Страницы блога"><?php if ($pn > 1): ?><a href="<?= e($base . ($pn > 2 ? '?p=' . ($pn - 1) : '')) ?>" rel="prev">← Новее</a><?php endif; ?><span><?= $pn ?> из <?= $pages ?></span><?php if ($pn < $pages): ?><a href="<?= e($base . '?p=' . ($pn + 1)) ?>" rel="next">Старее →</a><?php endif; ?></nav>
<?php endif; ?>
</div></div>
