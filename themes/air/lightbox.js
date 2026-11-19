document.querySelectorAll('[data-lightbox]').forEach(box => {
  const links = [...box.querySelectorAll('a')]; let i = 0, el = null, opener = null;
  const show = n => { i = (n + links.length) % links.length; const img = el.querySelector('img'); img.src = links[i].href; img.alt = links[i].querySelector('img').alt; };
  const close = () => { if (!el) return; el.remove(); el = null; document.removeEventListener('keydown', key); document.body.style.overflow = ''; opener && opener.focus(); };
  const key = e => { if (e.key === 'Escape') close(); if (e.key === 'ArrowRight') show(i + 1); if (e.key === 'ArrowLeft') show(i - 1); };
  links.forEach((a, n) => a.addEventListener('click', e => {
    e.preventDefault(); opener = a; el = document.createElement('div'); el.className = 'lb'; el.setAttribute('role', 'dialog'); el.setAttribute('aria-modal', 'true'); el.setAttribute('aria-label', 'Просмотр фото');
    el.innerHTML = '<img alt=""><button class="x" aria-label="Закрыть">✕</button>' + (links.length > 1 ? '<button class="p" aria-label="Назад">‹</button><button class="n" aria-label="Вперёд">›</button>' : '');
    el.addEventListener('click', ev => { const c = ev.target.classList; if (ev.target === el || c.contains('x')) close(); if (c.contains('p')) show(i - 1); if (c.contains('n')) show(i + 1); });
    document.body.appendChild(el); document.body.style.overflow = 'hidden'; document.addEventListener('keydown', key); show(n); el.querySelector('.x').focus();
  }));
});
