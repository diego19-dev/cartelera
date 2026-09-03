<?php
require_once __DIR__ . '/../../../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';
require_once BASE_PATH . '/src/Views/auth_guard.php';
require_once BASE_PATH . '/src/Views/crud.php';

cset_require_panel_module('center');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$item = $id ? CenterContent::find($id) : null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    try {
        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'sector' => trim((string) ($_POST['sector'] ?? '')),
            'is_active' => bool_from_post('is_active'),
            'display_order' => (int) ($_POST['display_order'] ?? 0),
            'duration_seconds' => duration_seconds_from_post(15),
        ];
        if ($data['title'] === '') {
            throw new RuntimeException('El título es obligatorio.');
        }
        if ($data['sector'] === '') {
            throw new RuntimeException('El sector es obligatorio.');
        }
        apply_media_upload($data, 'media_file', 'center', 'media_path');
        ContentValidity::applyFromPost($data, $item['show_until'] ?? null);
        if ($id > 0) {
            CenterContent::update($id, $data);
            Logger::log(Session::userId(), 'update', 'center_content', $id);
            flash_set('success', 'Actualizado.');
        } else {
            $data['created_by'] = Session::userId();
            $id = CenterContent::create($data);
            Logger::log(Session::userId(), 'create', 'center_content', $id);
            flash_set('success', 'Creado.');
        }
        redirect(APP_BASE . '/admin/center/index.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $item = array_merge($item ?? [], $_POST);
    }
}

$pageTitle = $id ? 'Editar Contenido Centro' : 'Nuevo Contenido Centro';
require BASE_PATH . '/src/Views/header.php';
$v = $item ?? [];
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="card"><form method="post" enctype="multipart/form-data">
<?= Security::csrfField() ?>
<input type="hidden" name="id" value="<?= (int) $id ?>">
<div class="form-group"><label>Título</label><input type="text" name="title" required value="<?= e((string) ($v['title'] ?? '')) ?>"></div>
<div class="form-group"><label>Descripción</label><textarea name="description"><?= e($v['description'] ?? '') ?></textarea></div>
<div class="form-group"><label>Sector</label>
<select name="sector">
<option value="">—</option>
<?php foreach (PROGRAM_AREAS as $k => $label): ?>
<option value="<?= e($k) ?>" <?= (($v['sector'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
</select></div>
<div class="form-group"><label>Orden</label><input type="number" name="display_order" value="<?= e((string) ($v['display_order'] ?? 0)) ?>"></div>
<?php render_duration_field($v); ?>
<?php render_validity_fieldset($v['show_until'] ?? null); ?>
<div class="form-group"><label>Media</label><input type="file" name="media_file"></div>
<label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= !isset($v['is_active']) || !empty($v['is_active']) ? 'checked' : '' ?>> Activo</label>
<div class="actions mt-2">
  <button class="btn" type="submit">Guardar</button>
  <a class="btn btn-ghost" href="<?= e(APP_BASE) ?>/admin/center/index.php">Cancelar</a>
</div>
</form></div>
<?php require BASE_PATH . '/src/Views/footer.php'; ?>
