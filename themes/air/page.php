<?php $first = $blocks[0]['type'] ?? ''; $GLOBALS['hero_first'] = ($first === 'hero'); ?>
<?php if ($is_home && $first !== 'hero'): ?><h1 class="visually-hidden"><?= e($site['title']) ?></h1><?php endif; ?>
<?php if (!$is_home && $first !== 'hero'): ?><header class="page-title"><h1><?= e($page['title']) ?></h1></header><?php endif; ?>
<?= $blocks_html ?>
<?php if (!$blocks): ?><p class="empty">Эта страница пока пустая.</p><?php endif; ?>
