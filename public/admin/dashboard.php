<?php
require_once __DIR__ . '/../../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';
require_once BASE_PATH . '/src/Views/auth_guard.php';

$pageTitle = 'Dashboard';
$stats = [];
$activity = [];
try {
    $stats = Database::getInstance()->fetch('SELECT * FROM v_dashboard_stats') ?: [];
} catch (Throwable $e) {
    $stats = [];
}
try {
    $activity = Logger::getRecent(15);
} catch (Throwable $e) {
    $activity = [];
}
$metrics = [
    ['key' => 'active_academic', 'label' => 'Contenido Académico'],
    ['key' => 'active_center', 'label' => 'Contenido Centro'],
    ['key' => 'active_general', 'label' => 'Contenido General'],
    ['key' => 'active_gallery', 'label' => 'Galería'],
    ['key' => 'total_media', 'label' => 'Archivos Multimedia'],
    ['key' => 'active_users', 'label' => 'Usuarios Activos'],
    ['key' => 'activity_24h', 'label' => 'Acciones (24h)'],
];
require BASE_PATH . '/src/Views/header.php';
?>
<section class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-primary to-brand-secondary p-5 text-white shadow-lg sm:p-6">
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <p class="text-xs uppercase tracking-widest text-white/70">Panel principal</p>
      <h2 class="mt-1 text-2xl font-bold">Bienvenido al panel CSET-TV</h2>
      <p class="mt-2 text-sm text-white/80">Gestione la cartelera digital y el contenido de las Smart TVs.</p>
    </div>
    <span class="rounded-full bg-white/15 px-3 py-1 text-xs"><?= (int) ($stats['active_users'] ?? 0) ?> usuarios activos</span>
  </div>
</section>

<div class="card mb-6">
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
    <?php foreach ($metrics as $i => $m): ?>
      <div class="text-center sm:text-left <?= $i < count($metrics) - 1 ? 'lg:border-r lg:border-surface-border lg:pr-4' : '' ?>">
        <div class="text-3xl font-bold"><?= (int) ($stats[$m['key']] ?? 0) ?></div>
        <div class="mt-1 text-sm text-surface-muted"><?= e($m['label']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
  <div class="card">
    <h3 class="mb-4 font-semibold">Acciones rápidas</h3>
    <div class="space-y-2">
      <a class="flex items-center justify-between rounded-xl border border-surface-border px-4 py-3 hover:bg-surface-bg" href="<?= e(cset_url('admin/academic/form.php')) ?>">+ Nuevo contenido académico →</a>
      <a class="flex items-center justify-between rounded-xl border border-surface-border px-4 py-3 hover:bg-surface-bg" href="<?= e(cset_url('admin/center/form.php')) ?>">+ Nuevo contenido centro →</a>
      <a class="flex items-center justify-between rounded-xl border border-surface-border px-4 py-3 hover:bg-surface-bg" href="<?= e(cset_url('admin/general/form.php')) ?>">+ Nueva noticia / evento →</a>
    </div>
  </div>
  <div class="card min-w-0">
    <h3 class="mb-4 font-semibold">Actividad reciente</h3>
    <?php if (!$activity): ?>
      <p class="text-sm text-surface-muted">Sin actividad registrada.</p>
    <?php else: ?>
      <div class="max-h-72 space-y-2 overflow-auto">
        <?php foreach ($activity as $log): ?>
          <div class="min-w-0 rounded-xl border border-surface-border px-3 py-2 text-sm">
            <div class="break-words font-medium"><?= e((string) ($log['action'] ?? '')) ?> · <?= e((string) ($log['table_name'] ?? '')) ?></div>
            <div class="break-words text-xs text-surface-muted">
              <?= e((string) ($log['username'] ?? '')) ?> — <?= e(cset_format_activity_at($log['created_at'] ?? '')) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card mt-6">
  <div class="mb-4 flex items-center justify-between gap-3">
    <h3 class="font-semibold">Vista previa TV</h3>
    <a class="btn btn-primary" href="<?= e(cset_url('')) ?>" target="_blank" rel="noopener">Abrir TV</a>
  </div>
  <div class="relative aspect-video w-full overflow-hidden rounded-xl border border-surface-border bg-black" data-tv-preview>
    <div class="origin-top-left" data-tv-scale style="width:1920px;height:1080px">
      <iframe title="TV Preview" src="<?= e(cset_url('')) ?>" width="1920" height="1080" class="block border-0"></iframe>
    </div>
  </div>
</div>
<script>
  (function () {
    var box = document.querySelector('[data-tv-preview]');
    var inner = document.querySelector('[data-tv-scale]');
    if (!box || !inner || !window.ResizeObserver) return;
    function update() {
      var w = box.clientWidth;
      inner.style.transform = 'scale(' + (w > 0 ? w / 1920 : 0.35) + ')';
    }
    update();
    new ResizeObserver(update).observe(box);
  })();
</script>
<?php require BASE_PATH . '/src/Views/footer.php'; ?>
