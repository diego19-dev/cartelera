<nav class="min-h-0 flex-1 space-y-1 overflow-y-auto p-3" aria-label="Navegación principal">
    <a href="<?= e(cset_url('admin/dashboard.php')) ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:bg-white/10 <?= e(cset_nav_active('dashboard')) ?>">
        <?= cset_icon('home') ?> Inicio
    </a>
    <div class="px-3 pt-4 text-xs font-semibold uppercase tracking-wide text-white/50">Contenido</div>
    <?php if (cset_can_manage_config()): ?>
    <a href="<?= e(cset_url('admin/welcome/index.php')) ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:bg-white/10 <?= e(cset_nav_active('bienvenida')) ?>">
        <?= cset_icon('sparkles') ?> Bienvenida
    </a>
    <?php endif; ?>
    <a href="<?= e(cset_url('admin/academic/index.php')) ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:bg-white/10 <?= e(cset_nav_active('academic')) ?>">
        <?= cset_icon('graduation-cap') ?> Información Académica
    </a>
    <?php if (cset_can_manage_general()): ?>
    <a href="<?= e(cset_url('admin/general/index.php')) ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:bg-white/10 <?= e(cset_nav_active('general')) ?>">
        <?= cset_icon('newspaper') ?> Información General
    </a>
    <?php endif; ?>
    <?php if (cset_can_manage_media()): ?>
    <a href="<?= e(cset_url('admin/media/index.php')) ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:bg-white/10 <?= e(cset_nav_active('media')) ?>">
        <?= cset_icon('image') ?> Biblioteca de Medios
    </a>
    <?php endif; ?>
    <?php if (cset_can_manage_users()): ?>
    <div class="px-3 pt-4 text-xs font-semibold uppercase tracking-wide text-white/50">Administración</div>
    <a href="<?= e(cset_url('admin/users/index.php')) ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-white/10 <?= e(cset_nav_active('users')) ?>">
        <?= cset_icon('users') ?> Usuarios
    </a>
    <?php endif; ?>
    <?php if (cset_can_manage_config()): ?>
    <a href="<?= e(cset_url('admin/config/index.php')) ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-white/10 <?= e(cset_nav_active('config')) ?>">
        <?= cset_icon('settings') ?> Configuración
    </a>
    <?php endif; ?>
    <div class="px-3 pt-4 text-xs font-semibold uppercase tracking-wide text-white/50">Visualización</div>
    <a href="<?= e(cset_url('')) ?>" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm hover:bg-white/10">
        <?= cset_icon('tv') ?> Vista TV
    </a>
</nav>
