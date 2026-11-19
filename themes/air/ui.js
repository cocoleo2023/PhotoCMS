// Sliders and click-to-load video. Works without JS as far as possible (sliders scroll natively).
(() => {
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.querySelectorAll('[data-slider]').forEach(sl => {
    const track = sl.querySelector('.sl-track'), items = [...track.children], n = items.length;
    if (!n) return;
    const fade = sl.classList.contains('sl-fade'); let i = 0, timer = null, paused = false, visible = true;
    const dotsBox = sl.querySelector('.sl-dots'), dots = [];
    if (dotsBox) items.forEach((_, k) => { const b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Слайд ' + (k + 1)); b.addEventListener('click', () => go(k, true)); dotsBox.append(b); dots.push(b); });
    const mark = () => { dots.forEach((b, k) => b.setAttribute('aria-current', String(k === i))); if (fade) items.forEach((it, k) => it.classList.toggle('on', k === i)); };
    const x0 = () => items[0].offsetLeft;
    const go = (k, user) => {
      i = (k + n) % n;
      if (!fade) track.scrollTo({ left: items[i].offsetLeft - x0(), behavior: reduce ? 'auto' : 'smooth' });
      mark(); if (user) restart();
    };
    const atEnd = () => track.scrollLeft + track.clientWidth >= track.scrollWidth - 3;
    const next = () => (!fade && atEnd()) ? go(0) : go(i + 1);
    const prev = () => go(i === 0 ? n - 1 : i - 1);
    sl.querySelector('.sl-next')?.addEventListener('click', () => { next(); restart(); });
    sl.querySelector('.sl-prev')?.addEventListener('click', () => { prev(); restart(); });
    if (!fade) {
      let raf = 0;
      track.addEventListener('scroll', () => { cancelAnimationFrame(raf); raf = requestAnimationFrame(() => {
        const x = track.scrollLeft; let best = 0, bd = Infinity;
        items.forEach((it, k) => { const d = Math.abs(it.offsetLeft - x0() - x); if (d < bd) { bd = d; best = k; } });
        if (best !== i) { i = best; mark(); }
      }); }, { passive: true });
    } else sl.classList.add('ready');
    sl.tabIndex = 0;
    sl.addEventListener('keydown', e => { if (e.key === 'ArrowRight') { next(); restart(); } if (e.key === 'ArrowLeft') { prev(); restart(); } });

    const auto = +sl.dataset.auto || 0;
    const restart = () => { clearInterval(timer); if (auto && !reduce && n > 1) timer = setInterval(() => { if (!paused && visible && !document.hidden) next(); }, auto); };
    ['mouseenter', 'focusin', 'touchstart'].forEach(ev => sl.addEventListener(ev, () => { paused = true; }, { passive: true }));
    ['mouseleave', 'focusout', 'touchend'].forEach(ev => sl.addEventListener(ev, () => { paused = false; }, { passive: true }));
    if ('IntersectionObserver' in window) new IntersectionObserver(es => { visible = es[0].isIntersecting; }).observe(sl);
    mark(); restart();
  });

  document.querySelectorAll('[data-video]').forEach(a => a.addEventListener('click', e => {
    e.preventDefault();
    const f = document.createElement('iframe'); f.src = a.dataset.video; f.title = 'Видео'; f.allowFullscreen = true;
    f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen'; a.replaceChildren(f);
  }));
})();
