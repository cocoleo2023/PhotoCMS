// Scroll reveal, text effects, counters, parallax. Without JS (or with "reduce motion") the page is simply shown as is.
(() => {
  window.__fx = 1;
  const root = document.documentElement;
  if (root.classList.contains('no-fx') || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const hasIO = 'IntersectionObserver' in window;

  const els = [...document.querySelectorAll('[data-anim]')];
  if (!hasIO) els.forEach(e => e.classList.add('in'));
  else {
    const io = new IntersectionObserver(list => list.forEach(en => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } }),
      { threshold: 0, rootMargin: '0px 0px -8% 0px' });
    els.forEach(e => io.observe(e));
  }

  // text effects: split headings into words or letters, then reveal when the block scrolls into view
  const split = (el, mode) => {
    const text = el.textContent.trim().replace(/\s+/g, ' '); el.setAttribute('aria-label', text); el.textContent = '';
    const words = text.split(' '); let ci = 0;
    words.forEach((w, wi) => {
      const wd = document.createElement('span'); wd.setAttribute('aria-hidden', 'true');
      if (mode === 'words') { wd.className = 'tfx-w'; wd.style.setProperty('--i', wi); wd.textContent = w; }
      else { wd.className = 'tfx-wd'; [...w].forEach(ch => { const c = document.createElement('span'); c.className = 'tfx-c'; c.style.setProperty('--i', ci++); c.textContent = ch; wd.append(c); }); ci++; }
      el.append(wd); if (wi < words.length - 1) el.append(' ');
    });
  };
  const tfx = [...document.querySelectorAll('[data-tfx]')];
  tfx.forEach(b => {
    const m = b.dataset.tfx; if (!['words', 'letters', 'typewriter'].includes(m)) return;
    b.querySelectorAll('h1,h2,h3').forEach(h => { if (!h.children.length && h.textContent.trim()) split(h, m === 'words' ? 'words' : 'chars'); });
  });

  // counters count up from zero when they appear
  const counters = [...document.querySelectorAll('[data-count]')];
  const fmt = (v, d) => d ? v.toFixed(d) : String(Math.round(v));
  const run = el => {
    const to = parseFloat(el.dataset.count), dec = +el.dataset.dec || 0, suf = el.dataset.suf || '', t0 = performance.now(), dur = 1700;
    const step = t => { const p = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - p, 3); el.textContent = fmt(to * e, dec) + suf; if (p < 1) requestAnimationFrame(step); };
    requestAnimationFrame(step);
  };
  counters.forEach(el => { el.textContent = fmt(0, +el.dataset.dec || 0) + (el.dataset.suf || ''); });

  if (hasIO) {
    const io2 = new IntersectionObserver(list => list.forEach(en => {
      if (!en.isIntersecting) return; io2.unobserve(en.target);
      if (en.target.dataset.count !== undefined) run(en.target); else en.target.classList.add('tfx-in');
    }), { threshold: 0.2, rootMargin: '0px 0px -6% 0px' });
    tfx.forEach(b => io2.observe(b)); counters.forEach(c => io2.observe(c));
  } else { tfx.forEach(b => b.classList.add('tfx-in')); counters.forEach(c => { c.textContent = c.dataset.count + (c.dataset.suf || ''); }); }

  // parallax
  const items = [...document.querySelectorAll('[data-parallax]')]
    .map(el => ({ el, img: el.querySelector('img'), k: parseFloat(el.dataset.parallax) || 0 })).filter(o => o.img && o.k > 0);
  if (!items.length) return;
  let ticking = false;
  const update = () => {
    ticking = false; const vh = innerHeight;
    for (const o of items) {
      const r = o.el.getBoundingClientRect();
      if (r.bottom < -120 || r.top > vh + 120) continue;
      const p = Math.max(-1, Math.min(1, ((r.top + r.height / 2) - vh / 2) / (vh / 2 + r.height / 2)));
      o.img.style.setProperty('--py', (-p * o.k * 0.1 * r.height).toFixed(1) + 'px');
    }
  };
  const req = () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } };
  addEventListener('scroll', req, { passive: true }); addEventListener('resize', req); addEventListener('load', req);
  update();
})();
