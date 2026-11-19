(() => {
  const drop = document.getElementById('drop');
  const list = document.getElementById('photos');
  const input = document.getElementById('file');
  const bar = document.getElementById('progress');
  const csrf = drop.dataset.csrf, galleryId = list.dataset.gallery;
  const post = (url, data) => fetch(url, { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: data });
  const form = obj => { const f = new FormData(); for (const k in obj) f.append(k, obj[k]); return f; };

  async function uploadAll(files) {
    files = [...files].filter(f => /^image\/(jpeg|png|webp)$/.test(f.type));
    if (!files.length) return;
    bar.hidden = false; const fill = bar.firstElementChild, label = bar.lastElementChild;
    let done = 0, failed = [];
    for (const file of files) {
      label.textContent = `Загружено ${done} из ${files.length}`;
      const fd = new FormData(); fd.append('photo', file);
      try {
        const res = await post(drop.dataset.upload, fd); const j = await res.json();
        if (!res.ok) throw new Error(j.error || 'Ошибка');
        addPhoto(j);
      } catch (e) { failed.push(`${file.name}: ${e.message}`); }
      fill.style.width = (++done / files.length * 100) + '%';
    }
    label.textContent = failed.length ? `Не загружено: ${failed.join('; ')}` : `Готово: ${done} фото`;
    saveOrder();
  }
  function addPhoto(j) {
    const li = document.createElement('li'); li.draggable = true; li.dataset.id = j.id;
    li.innerHTML = '<img alt="" loading="lazy"><div class="ph-tools"><button type="button" data-act="cover" title="Сделать обложкой">★</button><button type="button" data-act="del" title="Удалить">✕</button></div><input class="alt" placeholder="Описание фото для SEO">';
    li.querySelector('img').src = j.thumb; list.appendChild(li);
    if (!list.querySelector('.is-cover')) li.classList.add('is-cover');
  }
  const saveOrder = () => post('/admin/?r=photos_order', form({ gallery_id: galleryId, ids: [...list.children].map(li => li.dataset.id).join(',') }));

  input.addEventListener('change', () => { uploadAll(input.files); input.value = ''; });
  ['dragenter', 'dragover'].forEach(t => drop.addEventListener(t, e => { if (e.dataTransfer.types.includes('Files')) { e.preventDefault(); drop.classList.add('over'); } }));
  ['dragleave', 'drop'].forEach(t => drop.addEventListener(t, () => drop.classList.remove('over')));
  drop.addEventListener('drop', e => { if (e.dataTransfer.files.length) { e.preventDefault(); uploadAll(e.dataTransfer.files); } });

  list.addEventListener('click', async e => {
    const btn = e.target.closest('button[data-act]'); if (!btn) return;
    const li = btn.closest('li'), id = li.dataset.id;
    if (btn.dataset.act === 'del') {
      if (!confirm('Удалить это фото?')) return;
      const res = await post('/admin/?r=photo_delete', form({ id })); if (res.ok) li.remove();
    } else {
      const res = await post('/admin/?r=photo_cover', form({ id }));
      if (res.ok) { list.querySelectorAll('.is-cover').forEach(x => x.classList.remove('is-cover')); li.classList.add('is-cover'); }
    }
  });
  list.addEventListener('change', e => {
    if (e.target.classList.contains('alt')) post('/admin/?r=photo_alt', form({ id: e.target.closest('li').dataset.id, alt: e.target.value }));
  });

  // reorder by drag & drop (internal items only)
  let dragged = null;
  list.addEventListener('dragstart', e => { dragged = e.target.closest('li'); if (dragged) { dragged.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', dragged.dataset.id); } });
  list.addEventListener('dragover', e => {
    if (!dragged) return; e.preventDefault();
    const over = e.target.closest('li'); if (!over || over === dragged) return;
    const r = over.getBoundingClientRect(), after = (e.clientX - r.left) > r.width / 2;
    list.insertBefore(dragged, after ? over.nextSibling : over);
  });
  list.addEventListener('dragend', () => { if (dragged) { dragged.classList.remove('dragging'); dragged = null; saveOrder(); } });
})();
