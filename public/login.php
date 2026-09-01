<?php
require_once __DIR__ . '/../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';

if (Session::isAuthenticated()) {
    header('Location: ' . cset_url('admin/dashboard.php'));
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    if ($username === '' || $password === '') {
        $error = 'Usuario y contraseña requeridos';
    } elseif (LoginAttempt::isBlocked(Security::clientIp(), LOGIN_MAX_ATTEMPTS, LOGIN_WINDOW_MINUTES)) {
        $error = 'Demasiados intentos fallidos. Intente de nuevo en ' . LOGIN_WINDOW_MINUTES . ' minutos.';
    } else {
        $user = Auth::attempt($username, $password);
        if (!$user) {
            usleep(500000);
            $error = 'Credenciales inválidas';
        } else {
            if ($remember) {
                setcookie('cset_remember_user', $username, [
                    'expires' => time() + 60 * 60 * 24 * 30,
                    'path' => APP_BASE . '/',
                    'secure' => COOKIE_SECURE,
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]);
            } else {
                setcookie('cset_remember_user', '', [
                    'expires' => time() - 3600,
                    'path' => APP_BASE . '/',
                ]);
            }
            header('Location: ' . cset_url('admin/dashboard.php'));
            exit;
        }
    }
} else {
    $username = (string) ($_COOKIE['cset_remember_user'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión - SMART CSET</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(cset_url('assets/css/app.css')) ?>?v=1">
  <script>
    (function () {
      try {
        var t = localStorage.getItem('theme');
        var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
      } catch (e) {}
    })();
  </script>
</head>
<body>
<main class="grid-bg flex min-h-screen flex-col items-center justify-center px-4 py-6 md:px-8">
  <div class="mx-auto w-full max-w-4xl overflow-hidden rounded-2xl border border-surface-border bg-surface-card shadow-sm">
    <div class="grid lg:grid-cols-[2fr_3fr]">
      <div class="flex flex-col justify-center p-6 md:p-8 lg:border-r lg:border-surface-border">
        <div class="mb-6">
          <h1 class="mb-1.5 text-2xl font-bold text-surface-text md:text-3xl">Iniciar sesión</h1>
          <p class="text-sm leading-relaxed text-surface-muted">
            Acceda al panel de administración y gestione el contenido de las TVs.
          </p>
        </div>
        <?php if ($error !== ''): ?>
          <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/50 dark:text-red-200" role="alert">
            <?= e($error) ?>
          </div>
        <?php endif; ?>
        <form class="space-y-5" method="post" id="login-form" action="<?= e(cset_url('login.php')) ?>" autocomplete="on">
          <div>
            <label for="username" class="mb-1.5 inline-block text-sm font-medium text-surface-text">Usuario</label>
            <input id="username" name="username" type="text" value="<?= e($username) ?>" placeholder="admin" required autocomplete="username"
              class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2.5 text-sm text-surface-text outline-none transition focus:border-brand-primary">
          </div>
          <div>
            <label for="password" class="mb-1.5 inline-block text-sm font-medium text-surface-text">Contraseña</label>
            <input id="password" name="password" type="password" placeholder="••••••••" required autocomplete="current-password"
              class="w-full rounded-lg border border-surface-border bg-surface-card px-3 py-2.5 text-sm text-surface-text outline-none transition focus:border-brand-primary">
          </div>
          <label class="group flex cursor-pointer items-center">
            <input id="remember" name="remember" type="checkbox" value="1" class="sr-only" <?= $username !== '' ? 'checked' : '' ?>>
            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border border-surface-border bg-surface-card" aria-hidden="true">
              <svg class="size-3 text-white opacity-0" data-check viewBox="0 0 12 10" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 5l3 3 7-7" />
              </svg>
            </span>
            <span class="ml-3 text-sm text-surface-muted">Recordarme</span>
          </label>
          <button type="submit" class="w-full cursor-pointer rounded-lg border border-brand-primary bg-brand-primary px-3.5 py-2.5 text-sm font-semibold tracking-wide text-white transition hover:opacity-90">
            Ingresar
          </button>
          <p class="text-center text-xs text-surface-muted">Sistema de cartelería digital · SENA CSET</p>
        </form>
      </div>
      <div class="flex min-h-[220px] items-center justify-center border-t border-surface-border bg-surface-muted-20 p-8 md:p-10 lg:min-h-0 lg:border-t-0">
        <img src="<?= e(cset_url('logo-sena.png')) ?>" alt="Logo SENA" class="h-full w-full max-h-48 object-contain lg:max-h-full lg:max-w-[85%]">
      </div>
    </div>
  </div>
</main>
<script>
  (function () {
    var user = document.getElementById('username');
    var remember = document.getElementById('remember');
    var saved = null;
    try { saved = localStorage.getItem('cset_remember_user'); } catch (e) {}
    if (saved && user && !user.value) {
      user.value = saved;
      if (remember) remember.checked = true;
    }
    function syncCheck() {
      var on = remember && remember.checked;
      var box = remember && remember.parentNode.querySelector('span');
      var mark = remember && remember.parentNode.querySelector('[data-check]');
      if (box) {
        box.style.borderColor = on ? 'var(--brand-primary)' : '';
        box.style.background = on ? 'var(--brand-primary)' : '';
      }
      if (mark) mark.style.opacity = on ? '1' : '0';
    }
    remember && remember.addEventListener('change', syncCheck);
    syncCheck();
    var form = document.getElementById('login-form');
    form && form.addEventListener('submit', function () {
      try {
        if (remember && remember.checked && user) localStorage.setItem('cset_remember_user', user.value);
        else localStorage.removeItem('cset_remember_user');
      } catch (e) {}
    });
  })();
</script>
</body>
</html>
