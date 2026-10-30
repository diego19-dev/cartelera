<?php
/**
 * Alta/edición de slide general (categoría, icono, media). Solo admin + CSRF.
 */
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/src/helpers.php';
require_once BASE_PATH . '/src/auth_guard.php';
require_once BASE_PATH . '/src/crud.php';

Auth::requireRole(['admin']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$item = $id ? GeneralContent::find($id) : null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    try {
        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? 'news')),
            'icon' => trim((string) ($_POST['icon'] ?? '')),
            'source_url' => trim((string) ($_POST['source_url'] ?? '')),
            'is_active' => bool_from_post('is_active'),
            'is_external' => bool_from_post('is_external'),
            'duration_seconds' => duration_seconds_from_post(10),
        ];
        if ($data['title'] === '') {
            throw new RuntimeException('El título es obligatorio.');
        }
        apply_media_upload($data, 'media_file', 'general', 'image_path');
        if ($id > 0) {
            GeneralContent::update($id, $data);
            Logger::log(Session::userId(), 'update', 'general_content', $id);
            flash_set('success', 'Actualizado.');
        } else {
            $data['created_by'] = Session::userId();
            $id = GeneralContent::create($data);
            Logger::log(Session::userId(), 'create', 'general_content', $id);
            flash_set('success', 'Creado.');
        }
        redirect(APP_BASE . '/admin/general/index.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $item = array_merge($item ?? [], $_POST);
    }
}

$pageTitle = $id ? 'Editar general' : 'Nuevo general';
require BASE_PATH . '/src/header.php';
$v = $item ?? [];
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="card">
<form method="post" enctype="multipart/form-data">
<?= Security::csrfField() ?>
<input type="hidden" name="id" value="<?= (int) $id ?>">
<div class="form-group"><label>Título</label><input name="title" required value="<?= e($v['title'] ?? '') ?>"></div>
<div class="form-group"><label>Descripción</label><textarea name="description"><?= e($v['description'] ?? '') ?></textarea></div>
<div class="form-group"><label>Categoría</label>
<select name="category">
<?php foreach (CONTENT_CATEGORIES as $k => $label): ?>
<option value="<?= e($k) ?>" <?= (($v['category'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
</select></div>
<div class="form-group"><label>Icono</label>
<select name="icon">
<option value="">Ninguno</option>
<?php foreach (GENERAL_ICONS as $key): ?>
<option value="<?= e($key) ?>" <?= (($v['icon'] ?? '') === $key) ? 'selected' : '' ?>><?= e(GENERAL_ICON_LABELS[$key] ?? $key) ?></option>
<?php endforeach; ?>
</select></div>
<div class="form-group"><label>URL fuente</label><input name="source_url" value="<?= e($v['source_url'] ?? '') ?>"></div>
<?php if ($id > 0): ?>
<div class="form-group"><label>Orden</label>
  <input type="number" value="<?= e((string) ($v['display_order'] ?? '')) ?>" readonly disabled>
  <p class="hint">Asignado automáticamente (1…N). Se reordena al eliminar ítems.</p>
</div>
<?php endif; ?>
<?php render_duration_field($v, 10); ?>
<div class="form-group"><label>Media</label><input type="file" name="media_file"></div>
<label class="checkbox"><input type="checkbox" name="is_external" value="1" <?= !empty($v['is_external']) ? 'checked' : '' ?>> Contenido externo (API)</label>
<label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= !isset($v['is_active']) || !empty($v['is_active']) ? 'checked' : '' ?>> Activo</label>
<div class="actions mt-2">
  <button class="btn" type="submit">Guardar</button>
  <a class="btn btn-ghost" href="<?= e(APP_BASE) ?>/admin/general/index.php">Cancelar</a>
</div>
</form></div>
<?php require BASE_PATH . '/src/footer.php'; ?>
