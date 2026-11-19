<header class="page-title"><h1>Портфолио</h1></header>
<div class="bs"><section class="b b-gallery"><?php if (!$galleries): ?><p class="empty">Скоро здесь появятся работы.</p><?php endif; ?>
<div class="cards"><?php foreach ($galleries as $i => $g): ?>
<a class="card" href="/portfolio/<?= e($g['slug']) ?>" data-anim="up" style="--delay:<?= ($i % 3) * 80 ?>ms"><?php if ($g['cover']): ?><img src="<?= e(upload_url((int)$g['id'], $g['cover'])) ?>" alt="" loading="lazy"><?php endif; ?><span><?= e($g['title']) ?></span></a>
<?php endforeach; ?></div></section></div>
