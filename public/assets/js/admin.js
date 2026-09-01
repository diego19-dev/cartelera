(function () {
  const nav = document.getElementById('admin-nav');
  const backdrop = document.getElementById('nav-backdrop');
  const openBtn = document.getElementById('nav-open');
  const closeBtn = document.getElementById('nav-close');

  function setNav(open) {
    if (!nav) return;
    nav.classList.toggle('-translate-x-full', !open);
    nav.classList.toggle('translate-x-0', open);
    if (backdrop) backdrop.classList.toggle('hidden', !open);
  }

  openBtn && openBtn.addEventListener('click', () => setNav(true));
  closeBtn && closeBtn.addEventListener('click', () => setNav(false));
  backdrop && backdrop.addEventListener('click', () => setNav(false));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setNav(false);
  });

  const root = document.documentElement;
  const themeBtn = document.getElementById('theme-toggle');
  function syncThemeIcons() {
    const dark = root.classList.contains('dark');
    const sun = themeBtn && themeBtn.querySelector('.icon-sun');
    const moon = themeBtn && themeBtn.querySelector('.icon-moon');
    if (sun) sun.classList.toggle('hidden', !dark);
    if (moon) moon.classList.toggle('hidden', dark);
  }
  syncThemeIcons();
  themeBtn && themeBtn.addEventListener('click', () => {
    const next = !root.classList.contains('dark');
    root.classList.toggle('dark', next);
    try { localStorage.setItem('theme', next ? 'dark' : 'light'); } catch (e) {}
    syncThemeIcons();
  });

  const host = document.getElementById('toast-host');
  window.toast = function (message, type) {
    if (!host) return;
    const el = document.createElement('div');
    el.className = 'card shadow-lg text-sm ' + (type === 'error' ? 'border-red-500 text-red-700' : '');
    el.textContent = message;
    host.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  };

  let confirmResolver = null;
  const dialog = document.getElementById('confirm-dialog');
  const titleEl = document.getElementById('confirm-title');
  const msgEl = document.getElementById('confirm-message');
  const okBtn = document.getElementById('confirm-ok');
  const cancelBtn = document.getElementById('confirm-cancel');

  window.csetConfirm = function (title, message) {
    return new Promise((resolve) => {
      confirmResolver = resolve;
      if (titleEl) titleEl.textContent = title || 'Confirmar';
      if (msgEl) msgEl.textContent = message || '¿Está seguro?';
      if (dialog) {
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
      }
    });
  };

  function closeConfirm(ok) {
    if (dialog) {
      dialog.classList.add('hidden');
      dialog.classList.remove('flex');
    }
    if (confirmResolver) confirmResolver(ok);
    confirmResolver = null;
  }
  okBtn && okBtn.addEventListener('click', () => closeConfirm(true));
  cancelBtn && cancelBtn.addEventListener('click', () => closeConfirm(false));

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-toggle-url]');
    if (!btn) return;
    e.preventDefault();
    try {
      await api(btn.getAttribute('data-toggle-url'), { method: 'PATCH', json: {} });
      const on = btn.getAttribute('aria-checked') === 'true';
      btn.setAttribute('aria-checked', on ? 'false' : 'true');
      btn.classList.toggle('bg-brand-primary', !on);
      btn.classList.toggle('bg-surface-border', on);
      const knob = btn.querySelector('span');
      if (knob) knob.classList.toggle('translate-x-5', !on);
      toast('Estado actualizado');
    } catch (err) {
      toast(err.message || 'Error', 'error');
    }
  });

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-delete-url]');
    if (!btn) return;
    e.preventDefault();
    const ok = await csetConfirm(
      btn.getAttribute('data-delete-title') || 'Eliminar',
      btn.getAttribute('data-delete-message') || '¿Eliminar este registro?'
    );
    if (!ok) return;
    try {
      await api(btn.getAttribute('data-delete-url'), { method: 'DELETE', json: {} });
      toast('Eliminado');
      const row = btn.closest('tr');
      if (row) row.remove();
      else window.location.reload();
    } catch (err) {
      toast(err.message || 'Error', 'error');
    }
  });

  window.csetToggleHtml = function (checked, url) {
    const on = !!checked;
    return (
      '<button type="button" role="switch" aria-checked="' + (on ? 'true' : 'false') +
      '" data-toggle-url="' + url +
      '" class="relative h-6 w-11 rounded-full transition ' +
      (on ? 'bg-brand-primary' : 'bg-surface-border') +
      '"><span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white transition ' +
      (on ? 'translate-x-5' : '') + '"></span></button>'
    );
  };

  window.bindAjaxForm = function (form, options) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submit = form.querySelector('[type="submit"]');
      if (options && options.before && options.before() === false) return;
      if (submit) submit.disabled = true;
      try {
        const fd = new FormData(form);
        const method = options.method || 'POST';
        if (options.json) {
          const obj = {};
          fd.forEach((v, k) => { if (k !== '_csrf') obj[k] = v; });
          if (options.transform) options.transform(obj, fd);
          await api(options.path, { method, json: obj });
        } else {
          if (options.method && options.method !== 'POST') {
            await apiForm(options.path, fd, options.method);
          } else {
            await apiForm(options.path, fd, method);
          }
        }
        if (typeof broadcastConfigUpdate === 'function') broadcastConfigUpdate();
        toast(options.success || 'Guardado');
        if (options.redirect) window.location.assign(options.redirect);
      } catch (err) {
        toast(err.message || 'Error', 'error');
      } finally {
        if (submit) submit.disabled = false;
      }
    });
  };

  window.initValidityField = function (root) {
    if (!root) return;
    const hidden = root.querySelector('input[name="show_until"]');
    const amount = root.querySelector('[data-validity-amount]');
    const unit = root.querySelector('[data-validity-unit]');
    const extra = root.querySelector('[data-validity-new]');
    const preview = root.querySelector('[data-validity-preview]');
    const initial = hidden ? hidden.value : '';

    function addDuration(from, n, u) {
      const d = new Date(from.getTime());
      n = Math.max(0, Math.floor(n));
      if (u === 'hours') d.setHours(d.getHours() + n);
      else if (u === 'days') d.setDate(d.getDate() + n);
      else d.setMonth(d.getMonth() + n);
      return d;
    }

    function fmt(d) {
      return new Intl.DateTimeFormat('es-CO', {
        timeZone: 'America/Bogota',
        dateStyle: 'medium',
        timeStyle: 'short'
      }).format(d);
    }

    function mode() {
      const sel = root.querySelector('input[name="validity-mode"]:checked');
      return sel ? sel.value : 'none';
    }

    function sync() {
      const m = mode();
      if (extra) extra.classList.toggle('hidden', m !== 'new');
      if (m === 'none') {
        hidden.value = '';
        if (preview) preview.textContent = '';
        return;
      }
      if (m === 'keep') {
        hidden.value = initial;
        if (preview && initial) preview.textContent = 'Se mostrará hasta el ' + fmt(new Date(initial)) + ' (hora Bogotá).';
        return;
      }
      const until = addDuration(new Date(), Number(amount && amount.value) || 0, (unit && unit.value) || 'days');
      const delta = until.getTime() - Date.now();
      const max = addDuration(new Date(), 6, 'months');
      if (delta < 60 * 60 * 1000) {
        hidden.value = '';
        if (preview) preview.textContent = 'La vigencia mínima es 1 hora';
        return;
      }
      if (until.getTime() > max.getTime()) {
        hidden.value = '';
        if (preview) preview.textContent = 'La vigencia máxima es 6 meses';
        return;
      }
      hidden.value = until.toISOString();
      if (preview) preview.textContent = 'Se mostrará hasta el ' + fmt(until) + ' (hora Bogotá).';
    }

    root.querySelectorAll('input[name="validity-mode"]').forEach((el) => el.addEventListener('change', sync));
    amount && amount.addEventListener('input', sync);
    unit && unit.addEventListener('change', sync);
    sync();
  };
})();
