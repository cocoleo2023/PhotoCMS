<?php
$hdrOver = $site['header_over'] === '1' && !empty($GLOBALS['hero_first']);
$hdrLayout = $site['header_layout'] ?? 'split';
$hdrOrder = dz('header_order') ?: 'logo-nav-cta';
$mbtnStyle = dz('mbtn_style') ?: 'pill';
$mbtnIcon = dz('mbtn_icon') ?: 'bars';
$mnavStyle = dz('mnav_style') ?: 'dropdown';
$ddMode = dz('header_dropdown') ?: 'hover';
$bodyCls = 'hl-' . $hdrLayout
    . ($site['header_sticky'] === '1' ? ' hdr-sticky' : '')
    . ($hdrOver ? ' hdr-over' : '')
    . ($site['page_fade'] === '1' ? ' page-fade' : '')
    . ' mnav-' . $mnavStyle
    . ' dd-' . $ddMode
    . (dz('header_blur') === '1' ? ' hdr-blur' : '')
    . (dz('header_border') === '1' ? ' hdr-border' : '')
    . (dz('footer_border') === '1' ? ' ftr-border' : '');
$footCls = 'site-foot fl-' . ($site['footer_layout'] ?? 'center');
$ctaText = dz('header_cta_text');
$ctaUrl = dz('header_cta_url');
$ctaStyle = dz('header_cta_style') ?: 'solid';
$fCtaText = dz('footer_cta_text');
$fCtaUrl = dz('footer_cta_url');

// Build header parts for reordering
ob_start();
?><a class="logo" href="/"><?= e($site['title']) ?></a><?php
$partLogo = ob_get_clean();

ob_start();
?>
<nav id="nav" aria-label="Основное меню">
  <?php if ($site['has_galleries']): ?><a href="/portfolio"<?= str_starts_with($path, 'portfolio') ? ' aria-current="page"' : '' ?>>Портфолио</a><?php endif; ?>
  <?php if ($site['has_blog']): ?><a href="/blog"<?= str_starts_with($path, 'blog') ? ' aria-current="page"' : '' ?>><?= e(dz('blog_title') ?: 'Блог') ?></a><?php endif; ?>
  <?php foreach ($site['menu'] as $m): ?>
    <?php if (!empty($m['children'])): ?>
      <div class="nav-item has-sub">
        <a href="/<?= e($m['slug']) ?>" class="nav-parent"<?= $path === $m['slug'] ? ' aria-current="page"' : '' ?>><?= e($m['title']) ?> <span class="nav-caret" aria-hidden="true">▾</span></a>
        <div class="nav-sub" role="menu">
          <?php foreach ($m['children'] as $ch): ?>
            <a href="/<?= e($ch['slug']) ?>" role="menuitem"<?= $path === $ch['slug'] ? ' aria-current="page"' : '' ?>><?= e($ch['title']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <a href="/<?= e($m['slug']) ?>"<?= $path === $m['slug'] ? ' aria-current="page"' : '' ?>><?= e($m['title']) ?></a>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
<?php
$partNav = ob_get_clean();

$partCta = '';
if ($ctaText !== '' && $ctaUrl !== '') {
    $partCta = '<a class="hdr-cta btn ' . e($ctaStyle) . '" href="' . e($ctaUrl) . '">' . e($ctaText) . '</a>';
}

$mbtnLabel = dz('mbtn_label') !== '' ? dz('mbtn_label') : 'Меню';
$iconHtml = match ($mbtnIcon) {
    'dots' => '<span class="menu-btn-bars dots" aria-hidden="true"><i></i><i></i><i></i></span>',
    'grid' => '<span class="menu-btn-bars grid" aria-hidden="true"><i></i><i></i><i></i><i></i></span>',
    default => '<span class="menu-btn-bars" aria-hidden="true"><i></i><i></i><i></i></span>',
};
$mbtnCls = 'menu-btn mbtn-' . $mbtnStyle;
$partBtn = '<button class="' . e($mbtnCls) . '" type="button" aria-expanded="false" aria-controls="nav" aria-label="' . e($mbtnLabel) . '">'
    . $iconHtml
    . ($mbtnStyle !== 'icon' ? '<span class="menu-btn-label">' . e($mbtnLabel) . '</span>' : '')
    . '</button>';

$orderMap = [
    'logo-nav-cta' => [$partLogo, $partBtn, $partNav, $partCta],
    'logo-cta-nav' => [$partLogo, $partBtn, $partCta, $partNav],
    'nav-logo-cta' => [$partBtn, $partNav, $partLogo, $partCta],
];
$headerInner = implode('', $orderMap[$hdrOrder] ?? $orderMap['logo-nav-cta']);
?><!doctype html>
<html lang="ru"<?= $site['fx'] === '0' ? ' class="no-fx"' : '' ?>><head>
<script>document.documentElement.classList.add("js");setTimeout(function(){if(!window.__fx)document.documentElement.classList.add("no-fx")},4000);</script>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($meta['title']) ?></title>
<?= seo_head($meta, $site, $origin) ?><meta name="theme-color" content="<?= e(dz('c_accent') ?: '#3f6a62') ?>">
<link rel="alternate" type="application/rss+xml" title="<?= e($site['title']) ?>" href="/feed.xml">
<?= theme_preloads() ?>
<link rel="stylesheet" href="/themes/<?= e($theme) ?>/style.css">
<style><?= theme_style() ?></style>
</head><body class="<?= e($bodyCls) ?>">
<?= seo_analytics_body() ?>
<?php if ($preview): ?><div class="preview-bar">Предпросмотр. Посетители этого не видят.</div><?php endif; ?>
<a class="skip" href="#main">К содержимому</a>
<header class="site-head">
  <?php if (!empty($site['header_html'])): ?>
    <?= $site['header_html'] ?>
  <?php else: ?>
    <?= $headerInner ?>
  <?php endif; ?>
</header>
<?php if ($mnavStyle === 'drawer' || $mnavStyle === 'fullscreen'): ?>
<div class="mnav-backdrop" hidden></div>
<?php endif; ?>
<main id="main"><?= $body ?></main>
<footer class="<?= e($footCls) ?>">
  <?php if (!empty($site['footer_html'])): ?>
    <?= $site['footer_html'] ?>
  <?php else: ?>
  <div class="foot-inner">
    <p class="foot-line"><?= e($site['footer'] ?: '© ' . date('Y') . ' ' . $site['title']) ?></p>
    <?php if (dz('footer_show_menu') === '1' && !empty($site['menu'])): ?>
    <nav class="foot-menu" aria-label="Меню в подвале">
      <?php foreach ($site['menu'] as $m): ?>
        <a href="/<?= e($m['slug']) ?>"><?= e($m['title']) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if (dz('footer_show_social') !== '0'): ?>
    <p class="foot-links">
      <?php if ($site['email']): ?><a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a><?php endif; ?>
      <?php if ($site['phone']): ?><a href="tel:<?= e(preg_replace('~[^+0-9]~', '', $site['phone'])) ?>"><?= e($site['phone']) ?></a><?php endif; ?>
      <?php if ($site['instagram']): ?><a href="<?= e($site['instagram']) ?>" rel="noopener">Instagram</a><?php endif; ?>
    </p>
    <?php endif; ?>
    <?php if ($fCtaText !== '' && $fCtaUrl !== ''): ?>
      <p class="foot-cta"><a class="btn solid" href="<?= e($fCtaUrl) ?>"><?= e($fCtaText) ?></a></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</footer>
<script>
(function(){
  var btn=document.querySelector('.menu-btn');
  var backdrop=document.querySelector('.mnav-backdrop');
  function closeMenu(){
    if(!btn)return;
    btn.setAttribute('aria-expanded','false');
    document.body.classList.remove('menu-open');
    if(backdrop)backdrop.hidden=true;
  }
  function openMenu(){
    if(!btn)return;
    btn.setAttribute('aria-expanded','true');
    document.body.classList.add('menu-open');
    if(backdrop)backdrop.hidden=false;
  }
  if(btn){
    btn.addEventListener('click',function(){
      if(document.body.classList.contains('menu-open')) closeMenu(); else openMenu();
    });
  }
  if(backdrop) backdrop.addEventListener('click', closeMenu);
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape') closeMenu();
  });
  // dropdown: click mode
  if(document.body.classList.contains('dd-click')){
    document.querySelectorAll('.nav-item.has-sub > .nav-parent').forEach(function(a){
      a.addEventListener('click',function(e){
        e.preventDefault();
        var item=a.parentElement;
        var open=item.classList.contains('open');
        document.querySelectorAll('.nav-item.open').forEach(function(x){ if(x!==item) x.classList.remove('open'); });
        item.classList.toggle('open', !open);
      });
    });
    document.addEventListener('click',function(e){
      if(!e.target.closest('.nav-item')) document.querySelectorAll('.nav-item.open').forEach(function(x){ x.classList.remove('open'); });
    });
  }
  // mobile submenu toggle
  document.querySelectorAll('.nav-item.has-sub > .nav-parent').forEach(function(a){
    a.addEventListener('click',function(e){
      if(window.matchMedia('(max-width:820px)').matches){
        e.preventDefault();
        a.parentElement.classList.toggle('open');
      }
    });
  });
  // sticky scrolled class
  if(document.body.classList.contains('hdr-sticky')){
    var onScroll=function(){ document.body.classList.toggle('hdr-scrolled', window.scrollY>8); };
    window.addEventListener('scroll', onScroll, {passive:true});
    onScroll();
  }
})();
</script>
<?php
$cwOn = dz('cw_on') !== '0';
if ($cwOn):
  $cwName = dz('cw_name') !== '' ? dz('cw_name') : $site['title'];
  $cwRole = dz('cw_role');
  $cwPhoto = clean_image(dz('cw_photo'));
  $cwText = dz('cw_text');
  $cwLabel = dz('cw_label') !== '' ? dz('cw_label') : 'Связаться';
  $links = [];
  if (dz('cw_show_phone') !== '0' && $site['phone']) {
    $tel = preg_replace('~[^+0-9]~', '', $site['phone']);
    $links[] = ['href' => 'tel:' . $tel, 'label' => $site['phone'], 'icon' => 'phone'];
  }
  if (dz('cw_show_email') !== '0' && $site['email']) {
    $links[] = ['href' => 'mailto:' . $site['email'], 'label' => $site['email'], 'icon' => 'mail'];
  }
  $tg = trim(dz('cw_telegram'));
  if ($tg !== '') {
    if ($tg[0] === '@') $tg = 'https://t.me/' . substr($tg, 1);
    elseif (!str_starts_with($tg, 'http')) $tg = 'https://t.me/' . ltrim($tg, '/');
    $links[] = ['href' => $tg, 'label' => 'Telegram', 'icon' => 'tg'];
  }
  $wa = trim(dz('cw_whatsapp'));
  if ($wa !== '') {
    if (!str_starts_with($wa, 'http')) {
      $digits = preg_replace('~\D~', '', $wa);
      $wa = 'https://wa.me/' . $digits;
    }
    $links[] = ['href' => $wa, 'label' => 'WhatsApp', 'icon' => 'wa'];
  }
  if ($site['instagram']) $links[] = ['href' => $site['instagram'], 'label' => 'Instagram', 'icon' => 'ig'];
  $vk = trim(dz('cw_vk'));
  if ($vk !== '') $links[] = ['href' => $vk, 'label' => 'VK', 'icon' => 'vk'];
  $icons = [
    'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.5-1.1a2 2 0 0 1 2.1-.4c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2z"/></svg>',
    'mail' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="m22 6-10 7L2 6"/></svg>',
    'tg' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8-1.55 7.3c-.12.53-.43.66-.87.41l-2.4-1.77-1.16 1.12c-.13.13-.24.24-.49.24l.17-2.43 4.45-4.02c.19-.17-.04-.27-.3-.1l-5.5 3.46-2.37-.74c-.52-.16-.53-.52.11-.77l9.26-3.57c.43-.17.81.1.67.64z"/></svg>',
    'wa' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 14.9L2 22l5.3-1.4A10 10 0 1 0 12 2zm5.8 14.3c-.2.7-1.3 1.3-2.1 1.4-.6.1-1.3.2-3.8-.8-3.2-1.3-5.3-4.6-5.4-4.8-.2-.2-1.3-1.7-1.3-3.3 0-1.5.8-2.3 1.1-2.6.3-.3.6-.4.8-.4h.6c.2 0 .4 0 .6.5.2.6.8 2 .9 2.1.1.2.1.3 0 .5l-.4.7c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.2 1.4 2.5 1.5.3.1.5.1.7-.1l.9-1.1c.2-.2.4-.2.6-.1.3.1 1.6.8 1.9.9.3.1.5.2.5.3.1.3.1 1-.1 1.7z"/></svg>',
    'ig' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>',
    'vk' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.8 18h-1.5c-3.3 0-5.2-2.3-6.6-5.3C3.6 10.2 2.5 9 1.5 9c-.2 0-.4-.2-.4-.5V7.3c0-.3.2-.5.5-.5 1.6 0 2.9 1.1 4.1 3.2 1.4 2.4 2.3 3.5 2.9 3.5.2 0 .4-.1.4-.8V9.9c0-.9-.1-1.4-.9-1.5-.3 0-.5-.2-.5-.5 0-.3.2-.5.5-.5h3.2c.3 0 .5.2.5.5v4.3c0 .3.1.4.3.4.2 0 .4-.1.7-.5 1.1-1.4 2-3.3 2.6-4.8.1-.3.3-.4.6-.4h1.5c.4 0 .6.2.5.6-.6 1.8-1.8 3.7-2.9 5.1-.2.2-.2.4 0 .6 1.1 1.3 2.1 2.6 3 4.2.2.3 0 .6-.3.6h-1.6c-.3 0-.5-.2-.7-.5-.7-1.2-1.5-2.3-2.4-3.3-.2-.2-.4-.2-.6 0-.1.2-.3.5-.3 1v2c0 .7-.2 1-.9 1z"/></svg>',
  ];
?>
<div class="contact-widget" id="contact-widget">
  <div class="cw-panel" role="dialog" aria-label="Контакты" id="cw-panel">
    <div class="cw-head">
      <?php if ($cwPhoto): ?>
        <img class="cw-photo" src="<?= e($cwPhoto) ?>" alt="">
      <?php else: ?>
        <div class="cw-photo ph-empty" aria-hidden="true">📷</div>
      <?php endif; ?>
      <div>
        <p class="cw-name"><?= e($cwName) ?></p>
        <?php if ($cwRole !== ''): ?><p class="cw-role"><?= e($cwRole) ?></p><?php endif; ?>
      </div>
    </div>
    <?php if ($cwText !== ''): ?><p class="cw-text"><?= e($cwText) ?></p><?php endif; ?>
    <?php if ($links): ?>
    <div class="cw-links">
      <?php foreach ($links as $l): ?>
        <a href="<?= e($l['href']) ?>"<?= str_starts_with($l['href'], 'http') ? ' target="_blank" rel="noopener"' : '' ?>>
          <?= $icons[$l['icon']] ?? '' ?>
          <span><?= e($l['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <button type="button" class="cw-toggle" aria-expanded="false" aria-controls="cw-panel">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    <span class="cw-label"><?= e($cwLabel) ?></span>
  </button>
</div>
<script>
(function(){
  var w=document.getElementById('contact-widget'); if(!w) return;
  var btn=w.querySelector('.cw-toggle');
  var backdrop=null;
  function close(){
    w.classList.remove('open');
    btn.setAttribute('aria-expanded','false');
    if(backdrop){ backdrop.remove(); backdrop=null; }
  }
  function open(){
    w.classList.add('open');
    btn.setAttribute('aria-expanded','true');
    backdrop=document.createElement('button');
    backdrop.type='button';
    backdrop.className='cw-backdrop';
    backdrop.setAttribute('aria-label','Закрыть');
    backdrop.addEventListener('click', close);
    document.body.appendChild(backdrop);
  }
  btn.addEventListener('click', function(e){
    e.stopPropagation();
    if(w.classList.contains('open')) close(); else open();
  });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') close(); });
})();
</script>
<?php endif; ?>

<script src="/themes/<?= e($theme) ?>/fx.js" defer></script>
<script src="/themes/<?= e($theme) ?>/ui.js" defer></script>
<script src="/themes/<?= e($theme) ?>/lightbox.js" defer></script>
</body></html>
