const API_BASE = '/tv/api';

function getCsrf() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') || '' : '';
}

class ApiError extends Error {
  constructor(message, status) {
    super(message);
    this.status = status;
  }
}

async function api(path, options = {}) {
  const headers = Object.assign({}, options.headers || {});
  let body = options.body;
  if (options.json !== undefined) {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(options.json);
  }
  const method = (options.method || 'GET').toUpperCase();
  const csrf = getCsrf();
  if (method !== 'GET' && method !== 'HEAD' && csrf) {
    headers['X-CSRF-Token'] = csrf;
  }
  const res = await fetch(API_BASE + path, {
    method,
    headers,
    body,
    credentials: 'include'
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || data.ok === false) {
    if (res.status === 401 && path !== '/auth/login') {
      window.location.assign('/tv/login.php');
    }
    throw new ApiError(data.error || 'Error de API', res.status);
  }
  return data;
}

async function apiForm(path, form, method = 'POST') {
  const csrf = getCsrf();
  if (csrf) form.append('_csrf', csrf);
  const headers = {};
  if (csrf) headers['X-CSRF-Token'] = csrf;
  const res = await fetch(API_BASE + path, {
    method,
    body: form,
    headers,
    credentials: 'include'
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || data.ok === false) {
    if (res.status === 401) {
      window.location.assign('/tv/login.php');
    }
    throw new ApiError(data.error || 'Error de API', res.status);
  }
  return data;
}

function broadcastConfigUpdate() {
  try {
    const ch = new BroadcastChannel('cset-config-update');
    ch.postMessage({ type: 'config-update', at: Date.now() });
    ch.close();
  } catch (e) {}
}
