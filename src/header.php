<?php
/**
 * Layout HTML del panel: <head>, barra, flash, abre <main>.
 *
 * Variables: $pageTitle, $currentUser. Cierra con footer.php.
 */
/** @var string $pageTitle */
/** @var array|null $currentUser */
$pageTitle = $pageTitle ?? 'Panel';
$currentUser = $currentUser ?? (Session::isAuthenticated() ? Auth::userPayload() : null);
$flash = flash_get();
$branding = cset_branding();
$logoUrl = (string) ($branding['logo_url'] ?? '');
if ($logoUrl === '') {
    $logoUrl = cset_url('img/logo-sena.png');
}
$userName = Session::userName() ?: (string) ($currentUser['full_name'] ?? $currentUser['username'] ?? '');
$userRole = Session::userRole() ?: (string) ($currentUser['role'] ?? '');
$csrf = Security::csrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e($csrf) ?>">
  <title><?= e($pageTitle) ?> — SMART CSET</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(cset_url('css/app.css')) ?>?v=9">
  <script>
    (function () {
      try {
        var t = localStorage.getItem('theme');
        var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
      } catch (e) {}
    })();
  </script>
  <style>
<?php
    $p = $branding['primary_color'] ?? '';
    $s = $branding['secondary_color'] ?? '';
    $a = $branding['accent_color'] ?? '';
    $bg = $branding['bg_color'] ?? '';
    $tx = $branding['text_color'] ?? '';
    $dp = $branding['dark_primary_color'] ?? $p;
    $ds = $branding['dark_secondary_color'] ?? $s;
    $da = $branding['dark_accent_color'] ?? $a;
    $dbg = $branding['dark_bg_color'] ?? $bg;
    $dt = $branding['dark_text_color'] ?? $tx;
    $font = $branding['font_family'] ?? '';
    if ($p) echo ':root{--brand-primary-light:' . e($p) . '}';
    if ($s) echo ':root{--brand-secondary-light:' . e($s) . '}';
    if ($a) echo ':root{--brand-accent-light:' . e($a) . '}';
    if ($bg) echo ':root{--brand-bg-light:' . e($bg) . '}';
    if ($tx) echo ':root{--brand-text-light:' . e($tx) . '}';
    if ($dp) echo ':root{--brand-primary-dark:' . e($dp) . '}';
    if ($ds) echo ':root{--brand-secondary-dark:' . e($ds) . '}';
    if ($da) echo ':root{--brand-accent-dark:' . e($da) . '}';
    if ($dbg) echo ':root{--brand-bg-dark:' . e($dbg) . '}';
    if ($dt) echo ':root{--brand-text-dark:' . e($dt) . '}';
    if ($font) echo ':root{--brand-font:' . e($font) . ';--c-font:' . e($font) . '}';
?>
  </style>
</head>
<body>
<?php if ($currentUser): ?>
<div class="grid-bg flex h-screen overflow-hidden">
  <button type="button" id="nav-backdrop" class="fixed inset-0 z-40 bg-black/50 lg:hidden hidden" aria-label="Cerrar menú"></button>
  <aside id="admin-nav" class="fixed inset-y-0 left-0 z-50 flex h-full w-64 flex-col bg-brand-primary text-white transition-transform duration-200 ease-out -translate-x-full lg:static lg:z-auto lg:translate-x-0 lg:shrink-0">
    <div class="flex shrink-0 items-start justify-between gap-3 border-b border-white/10 p-5">
      <div class="min-w-0">
        <h2 class="text-lg font-bold">SMART CSET</h2>
        <p class="mt-1 text-xs text-white/70"><?= e($userName) ?></p>
        <span class="mt-2 inline-block rounded-full bg-white/15 px-2 py-0.5 text-xs"><?= e($userRole) ?></span>
      </div>
      <div class="flex shrink-0 items-start gap-2">
        <?php if ($logoUrl): ?>
          <div class="flex size-14 items-center justify-center">
            <img src="<?= e($logoUrl) ?>" alt="" class="max-h-full max-w-full object-contain">
          </div>
        <?php endif; ?>
        <button type="button" id="nav-close" class="rounded-lg p-1.5 text-white/80 hover:bg-white/10 lg:hidden" aria-label="Cerrar menú">
          <?= cset_icon('x', 20) ?>
        </button>
      </div>
    </div>
    <?php require __DIR__ . '/nav.php'; ?>
  </aside>

  <div class="flex min-h-0 min-w-0 flex-1 flex-col">
    <header class="flex shrink-0 items-center justify-between gap-3 border-b border-surface-border bg-surface-card px-4 py-3 lg:px-6">
      <div class="flex min-w-0 items-center gap-3">
        <button type="button" id="nav-open" class="rounded-lg border border-surface-border p-2 text-surface-text hover:bg-surface-bg lg:hidden" aria-label="Abrir menú" aria-controls="admin-nav">
          <?= cset_icon('menu', 20) ?>
        </button>
        <div class="min-w-0">
          <div class="text-xs text-surface-muted">Panel</div>
          <h1 class="truncate text-lg font-semibold"><?= e($pageTitle) ?></h1>
        </div>
      </div>
      <div class="flex shrink-0 items-center gap-2">
        <button class="btn btn-outline" type="button" id="theme-toggle">
          <span class="icon-sun hidden"><?= cset_icon('sun', 16) ?></span>
          <span class="icon-moon"><?= cset_icon('moon', 16) ?></span>
          <span class="hidden sm:inline">Tema</span>
        </button>
        <div class="hidden text-sm lg:block"><?= e($userName) ?></div>
        <form method="post" action="<?= e(cset_url('logout.php')) ?>">
          <?= Security::csrfField() ?>
          <button class="btn btn-danger" type="submit">
            <?= cset_icon('log-out', 16) ?>
            <span class="hidden sm:inline">Salir</span>
          </button>
        </form>
      </div>
    </header>
    <main class="min-h-0 flex-1 overflow-y-auto p-4 lg:p-6">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" data-flash-alert role="status"><?= e($flash['message']) ?></div>
        <?php endif; ?>
<?php else: ?>
<main class="grid-bg flex min-h-screen flex-col items-center justify-center px-4 py-6">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" data-flash-alert role="status"><?= e($flash['message']) ?></div>
        <?php endif; ?>
<?php endif; ?>
