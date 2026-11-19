(() => {
  const P = window.PCMS, types = P.types, STYLE = P.style, csrf = P.csrf, galleries = P.galleries;
  const root = document.getElementById('blocks-editor');
  const input = document.getElementById('blocks-input');
  const form = input.closest('form');
  let dirty = false;

  const h = (tag, attrs = {}, ...kids) => {
    const el = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs)) {
      if (k === 'class') el.className = v;
      else if (k.startsWith('on')) el.addEventListener(k.slice(2), v);
      else if (v !== false && v != null) el.setAttribute(k, v === true ? '' : v);
    }
    kids.flat().forEach(c => c != null && el.append(c.nodeType ? c : document.createTextNode(c)));
    return el;
  };
  const clone = o => JSON.parse(JSON.stringify(o));
  const defOf = f => f.t === 'range' ? f.def : f.t === 'select' ? Object.keys(f.o)[0] : f.t === 'repeater' ? [] : (f.t === 'gallery' || f.t === 'category') ? 0 : '';

  function normalize(b) {
    const t = types[b.type];
    if (Array.isArray(b.data)) b.data = {};
    [...t.fields, ...t.design].forEach(f => { if (b.data[f.k] == null) b.data[f.k] = defOf(f); });
    if (!t.nostyle) {
      const base = {}; STYLE.forEach(f => base[f.k] = defOf(f));
      b.data._s = Object.assign(base, t.sdef || {}, b.data._s || {});
    }
    return b;
  }
  let blocks = (P.blocks || []).filter(b => types[b.type]).map(b => normalize({ type: b.type, data: b.data || {}, open: false }));

  const sync = () => { input.value = JSON.stringify(blocks.map(({ type, data }) => ({ type, data }))); };
  form.addEventListener('submit', () => { sync(); dirty = false; });
  window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  document.addEventListener('keydown', e => { if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') { e.preventDefault(); form.requestSubmit(); } });
  const touch = () => { dirty = true; sync(); schedulePreview(); };

  /* ---------- fields ---------- */
  function field(f, data, onChange, defs) {
    const val = data[f.k] ?? defOf(f);
    const set = v => { data[f.k] = v; onChange(); };
    const def = defs && defs[f.k] !== undefined ? defs[f.k] : f.def;
    if (f.t === 'range') {
      const out = h('output', {}, val + (f.unit || ''));
      const rng = h('input', { type: 'range', min: f.min, max: f.max, step: f.step, value: val, oninput: e => { const n = +e.target.value; out.textContent = n + (f.unit || ''); set(n); } });
      const reset = h('button', { type: 'button', class: 'rst', title: 'Вернуть по умолчанию (' + def + (f.unit || '') + ')', 'aria-label': 'Сбросить', onclick: () => { rng.value = def; out.textContent = def + (f.unit || ''); set(def); } }, '↺');
      return h('div', { class: 'rng' }, h('span', { class: 'rng-l' }, f.l, ' ', out), h('div', { class: 'rng-c' }, rng, reset));
    }
    if (f.t === 'textarea') return h('label', {}, f.l, h('textarea', { rows: f.k === 'code' ? 8 : 4, class: f.k === 'code' ? 'mono' : '', oninput: e => set(e.target.value) }, val));
    if (f.t === 'select') return h('label', {}, f.l, h('select', { onchange: e => set(e.target.value) }, Object.entries(f.o).map(([k, l]) => h('option', { value: k, selected: String(k) === String(val) }, l))));
    if (f.t === 'gallery') return h('label', {}, f.l, h('select', { onchange: e => set(+e.target.value) }, h('option', { value: 0 }, '— выберите галерею —'), galleries.map(g => h('option', { value: g.id, selected: +val === +g.id }, g.title))));
    if (f.t === 'category') return h('label', {}, f.l, h('select', { onchange: e => set(+e.target.value) }, h('option', { value: 0 }, 'Все рубрики'), (P.categories || []).map(c => h('option', { value: c.id, selected: +val === +c.id }, c.title))));
    if (f.t === 'image') return imageField(f, val, set);
    if (f.t === 'repeater') {
      const rows = data[f.k] = Array.isArray(data[f.k]) ? data[f.k] : [];
      const wrap = h('div', { class: 'rep' });
      const draw = () => wrap.replaceChildren(h('span', { class: 'lbl' }, f.l),
        ...rows.map((row, ri) => h('div', { class: 'rep-row' }, f.sub.map(sf => field(sf, row, onChange)),
          h('button', { type: 'button', class: 'btn small danger', onclick: () => { rows.splice(ri, 1); onChange(); draw(); } }, 'Удалить'))),
        h('button', { type: 'button', class: 'btn small', onclick: () => { rows.push({}); onChange(); draw(); } }, '+ ' + (f.add || 'Добавить')));
      draw(); return wrap;
    }
    return h('label', {}, f.l, h('input', { value: val, oninput: e => set(e.target.value) }));
  }

  function fieldList(fields, data, onChange, defs) {
    const out = []; let g = null;
    fields.forEach(f => {
      if (f.g && f.g !== g) { g = f.g; out.push(h('h4', { class: 'grp' }, g)); }
      out.push(field(f, data, onChange, defs));
    });
    return out;
  }

  function imageField(f, val, set) {
    const prev = h('div', { class: 'img-prev' }); const status = h('small', { class: 'muted' });
    const draw = () => prev.replaceChildren(val ? h('img', { src: val, alt: '' }) : h('span', { class: 'muted' }, 'Фото не выбрано'));
    const file = h('input', { type: 'file', accept: 'image/jpeg,image/png,image/webp', hidden: true, onchange: async () => {
      const fl = file.files[0]; if (!fl) return; status.textContent = 'Загрузка…';
      const fd = new FormData(); fd.append('photo', fl);
      try {
        const res = await fetch('/admin/?r=media_upload', { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: fd }); const j = await res.json();
        if (!res.ok) throw new Error(j.error || 'Ошибка'); val = j.url; set(val); draw(); status.textContent = '';
      } catch (e) { status.textContent = e.message; }
      file.value = '';
    } });
    draw();
    return h('div', { class: 'img-field' }, h('span', { class: 'lbl' }, f.l), prev,
      h('div', { class: 'row' }, h('button', { type: 'button', class: 'btn small', onclick: () => file.click() }, 'Загрузить фото'),
        h('button', { type: 'button', class: 'btn small', onclick: () => { val = ''; set(''); draw(); } }, 'Убрать'), file), status);
  }

  /* ---------- blocks list ---------- */
  const summary = b => String(b.data.title || b.data.text || b.data.caption || b.data.subtitle || '').slice(0, 70);
  const move = (i, dir) => { const j = i + dir; if (j < 0 || j >= blocks.length) return; [blocks[i], blocks[j]] = [blocks[j], blocks[i]]; touch(); render(); };

  function card(b, i) {
    const t = types[b.type];
    const sum = h('span', { class: 'sum muted' }, summary(b));
    const change = () => { sum.textContent = summary(b); touch(); };
    const parts = [h('div', { class: 'stack' }, fieldList(t.fields, b.data, change))];
    if (t.design.length || !t.nostyle) {
      const inner = fieldList(t.design, b.data, change);
      if (!t.nostyle) inner.push(...fieldList(STYLE, b.data._s, change, t.sdef));
      parts.push(h('details', { class: 'design', open: b.dOpen }, h('summary', {}, 'Оформление и анимация'), h('div', { class: 'stack' }, inner)));
      parts[1].addEventListener('toggle', e => { b.dOpen = e.target.open; });
    }
    const body = h('div', { class: 'blk-body stack', hidden: !b.open }, parts);
    const toggle = h('button', { type: 'button', class: 'blk-title', 'aria-expanded': String(b.open), onclick: () => { b.open = !b.open; body.hidden = !b.open; toggle.setAttribute('aria-expanded', String(b.open)); } }, h('strong', {}, t.label), sum);
    return h('div', { class: 'card blk' }, h('div', { class: 'blk-head' }, toggle,
      h('button', { type: 'button', class: 'ic', 'aria-label': 'Выше', title: 'Выше', disabled: i === 0, onclick: () => move(i, -1) }, '↑'),
      h('button', { type: 'button', class: 'ic', 'aria-label': 'Ниже', title: 'Ниже', disabled: i === blocks.length - 1, onclick: () => move(i, 1) }, '↓'),
      h('button', { type: 'button', class: 'ic', 'aria-label': 'Дублировать', title: 'Дублировать', onclick: () => { blocks.splice(i + 1, 0, { type: b.type, data: clone(b.data), open: true }); touch(); render(); } }, '⧉'),
      h('button', { type: 'button', class: 'ic danger', 'aria-label': 'Удалить блок', title: 'Удалить', onclick: () => { if (confirm('Удалить блок «' + t.label + '»?')) { blocks.splice(i, 1); touch(); render(); } } }, '✕')), body);
  }

  function picker() {
    return h('div', { class: 'card' }, h('h2', {}, 'Добавить блок'), h('div', { class: 'picker' },
      Object.entries(types).map(([k, t]) => h('button', { type: 'button', class: 'pick-btn', onclick: () => {
        blocks.push(normalize({ type: k, data: clone(Array.isArray(t.default) ? {} : (t.default || {})), open: true })); touch(); render();
        root.querySelectorAll('.blk')[blocks.length - 1]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } }, h('strong', {}, t.label), h('small', {}, t.hint)))));
  }

  function toolbar() {
    const animField = STYLE.find(f => f.k === 'anim');
    const sel = h('select', { 'aria-label': 'Анимация для всех блоков' }, Object.entries(animField.o).map(([k, l]) => h('option', { value: k, selected: k === 'up' }, l)));
    return h('div', { class: 'card tb' },
      h('button', { type: 'button', class: 'btn primary small', onclick: () => togglePreview() }, 'Предпросмотр'),
      h('button', { type: 'button', class: 'btn small', onclick: () => { const all = blocks.some(b => !b.open); blocks.forEach(b => b.open = all); render(); } }, 'Развернуть / свернуть все'),
      h('span', { class: 'grow' }),
      h('label', { class: 'inl' }, 'Анимация для всех блоков', sel),
      h('button', { type: 'button', class: 'btn small', onclick: () => { blocks.forEach(b => { if (b.data._s && b.type !== 'hero') b.data._s.anim = sel.value; }); touch(); render(); } }, 'Применить'));
  }

  function render() {
    root.replaceChildren(toolbar(),
      blocks.length ? h('div', { class: 'stack' }, blocks.map(card)) : h('p', { class: 'empty' }, 'Страница пока пустая. Добавьте первый блок ниже, например «Обложку».'),
      picker());
    sync();
  }

  /* ---------- live preview (shows unsaved changes, never touches the published page) ---------- */
  let pv = null, timer = null, deviceW = 1440, scrollKeep = 0;
  function buildPreview() {
    const frame = h('iframe', { title: 'Предпросмотр страницы' });
    const stage = h('div', { class: 'pv-stage' }, frame);
    const dev = (label, w) => h('button', { type: 'button', class: 'btn small', onclick: () => { deviceW = w; layout(); } }, label);
    const bar = h('div', { class: 'pv-bar' }, dev('Компьютер', 1440), dev('Планшет', 820), dev('Телефон', 390), h('span', { class: 'grow' }),
      h('button', { type: 'button', class: 'btn small', onclick: () => refresh() }, '↻ Обновить'), h('button', { type: 'button', class: 'btn small', onclick: () => togglePreview(false) }, 'Закрыть'));
    const panel = h('aside', { class: 'pv', 'aria-label': 'Предпросмотр' }, bar, stage);
    document.body.append(panel);
    frame.addEventListener('load', () => { try { frame.contentWindow.scrollTo(0, scrollKeep); } catch (e) {} });
    function layout() {
      const sw = stage.clientWidth, sh = stage.clientHeight, s = Math.min(1, sw / deviceW);
      frame.style.width = deviceW + 'px'; frame.style.height = (sh / s) + 'px'; frame.style.transform = 'scale(' + s + ')';
      frame.style.marginLeft = Math.max(0, (sw - deviceW * s) / 2) + 'px';
    }
    window.addEventListener('resize', () => pv && layout());
    return { panel, frame, layout };
  }
  async function refresh() {
    if (!pv) return;
    try { scrollKeep = pv.frame.contentWindow.scrollY || 0; } catch (e) {}
    const fd = new FormData(); fd.append('id', P.pageId); fd.append('blocks', input.value); fd.append('title', form.querySelector('input[name=title]').value);
    try { await fetch('/admin/?r=' + (P.previewEndpoint || 'page_preview'), { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: fd }); } catch (e) { return; }
    pv.frame.src = (P.previewUrl || ('/?preview=1&pvpage=' + P.pageId)) + '&_=' + Date.now();
  }
  function schedulePreview() { if (!pv || !document.body.classList.contains('pv-open')) return; clearTimeout(timer); timer = setTimeout(refresh, 700); }
  function togglePreview(force) {
    const open = force ?? !document.body.classList.contains('pv-open');
    document.body.classList.toggle('pv-open', open);
    if (open) { pv = pv || buildPreview(); sync(); pv.layout(); refresh(); }
  }
  form.querySelector('input[name=title]').addEventListener('input', () => { dirty = true; schedulePreview(); });

  render();
})();
