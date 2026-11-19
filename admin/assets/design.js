(() => {
  const D = window.DZ, form = document.getElementById('design-form'), frame = document.getElementById('dz-frame');
  const stage = frame.parentElement, target = document.getElementById('dz-target');
  let deviceW = 1440, timer = null, scrollKeep = 0, dirty = false;

  const layout = () => {
    const sw = stage.clientWidth, sh = stage.clientHeight, s = Math.min(1, sw / deviceW);
    frame.style.width = deviceW + 'px'; frame.style.height = (sh / s) + 'px'; frame.style.transform = 'scale(' + s + ')';
    frame.style.marginLeft = Math.max(0, (sw - deviceW * s) / 2) + 'px';
  };
  const load = () => { frame.src = target.value + '?preview=1&pvd=1&_=' + Date.now(); };
  const refresh = async () => {
    try { scrollKeep = frame.contentWindow.scrollY || 0; } catch (e) {}
    try { await fetch('/admin/?r=design_preview', { method: 'POST', headers: { 'X-CSRF-Token': D.csrf }, body: new FormData(form) }); } catch (e) { return; }
    load();
  };
  const schedule = () => { dirty = true; clearTimeout(timer); timer = setTimeout(refresh, 450); };
  frame.addEventListener('load', () => { try { frame.contentWindow.scrollTo(0, scrollKeep); } catch (e) {} });

  // palette swatches fill the colour pickers and switch the scheme
  const sel = form.elements.palette;
  const colorInputs = [...form.querySelectorAll('input[type=color]')];
  form.querySelectorAll('.sw').forEach(b => b.addEventListener('click', () => {
    const c = D.palettes[b.dataset.pal]; sel.value = b.dataset.pal;
    colorInputs.forEach(i => { const k = i.name.replace(/^c_/, ''); if (c[k]) i.value = c[k]; });
    mark(); schedule();
  }));
  const mark = () => form.querySelectorAll('.sw').forEach(b => b.classList.toggle('on', b.dataset.pal === sel.value));
  sel.addEventListener('change', () => {
    const c = D.palettes[sel.value]; if (c) colorInputs.forEach(i => { const k = i.name.replace(/^c_/, ''); if (c[k]) i.value = c[k]; }); mark();
  });
  colorInputs.forEach(i => i.addEventListener('input', () => { sel.value = 'custom'; mark(); }));
  mark();

  form.addEventListener('input', schedule); form.addEventListener('change', schedule);
  form.addEventListener('submit', () => { dirty = false; });
  window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  target.addEventListener('change', () => { scrollKeep = 0; load(); });
  document.getElementById('dz-refresh').addEventListener('click', refresh);
  document.querySelectorAll('.dz-pv [data-w]').forEach(b => b.addEventListener('click', () => { deviceW = +b.dataset.w; layout(); }));
  window.addEventListener('resize', layout);
  layout(); refresh();
})();
