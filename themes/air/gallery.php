<header class="page-title"><h1><?= e($gallery['title']) ?></h1>
<?php if (trim((string)$gallery['description']) !== ''): ?><div class="lead"><?= paragraphs((string)$gallery['description']) ?></div><?php endif; ?></header>
<div class="bs bs-gallery-page"><section class="b b-gallery">
<?= render_gallery_grid($gallery, $photos, gallery_opts($gallery)) ?>
<p class="more"><a href="/">На главную</a></p></section></div>
