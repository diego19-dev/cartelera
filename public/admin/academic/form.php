<?php
require_once __DIR__ . '/../../../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';
require_once BASE_PATH . '/src/Views/auth_guard.php';
require_once BASE_PATH . '/src/Views/crud.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$item = $id ? AcademicContent::find($id) : null;
$error = '';
$missingProgramArea = Session::hasRole('program_head')
    && !Session::hasRole('admin')
    && cset_academic_program_area($item) === null;

if ($id > 0 && $item && !cset_can_manage_academic_item($item)) {
    http_response_code(403);
    echo 'Acceso denegado. Solo puede editar contenido que usted subió.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    try {
        assert_multipart_intact();
        $programArea = cset_academic_program_area($item);
        if (Session::hasRole('program_head') && !Session::hasRole('admin') && $programArea === null) {
            throw new RuntimeException(
                'Su usuario no tiene un área de programa asignada. Contacte al administrador.'
            );
        }

        $data = [
            'title' => trim((string) ($_POST['title'] ?? $item['title'] ?? '')),
            'description' => $item['description'] ?? null,
            'program_type' => (string) ($item['program_type'] ?? 'announcement'),
            'program_area' => $programArea,
            'is_active' => bool_from_post('is_active'),
            'display_order' => (int) ($_POST['display_order'] ?? 0),
            'duration_seconds' => duration_seconds_from_post(15),
        ];
        if (!Auth::canEditArea($data['program_area'])) {
            throw new RuntimeException('Sin permisos para este sector.');
        }
        $userId = Session::userId();
        if (!$userId) {
            throw new RuntimeException('Sesión inválida. Vuelva a iniciar sesión.');
        }
        apply_media_upload($data, 'media_file', 'academic', 'media_path');
        if ($id <= 0 && empty($data['media_path'])) {
            throw new RuntimeException('Seleccione un archivo de imagen o video.');
        }
        ContentValidity::applyFromPost($data, is_array($item) ? ($item['show_until'] ?? null) : null);
        if ($id > 0) {
            $existing = AcademicContent::find($id);
            if (!$existing || !cset_can_manage_academic_item($existing)) {
                throw new RuntimeException('Sin permisos para editar este contenido.');
            }
            AcademicContent::update($id, $data);
            Logger::log($userId, 'update', 'academic_content', $id);
            flash_set('success', 'Actualizado.');
        } else {
            $data['created_by'] = $userId;
            $id = AcademicContent::create($data);
            Logger::log($userId, 'create', 'academic_content', $id);
            flash_set('success', 'Creado.');
        }
        redirect(APP_BASE . '/admin/academic/index.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $item = array_merge($item ?? [], $_POST);
    }
}

$pageTitle = $id ? 'Editar Contenido Académico' : 'Nuevo Contenido Académico';
require BASE_PATH . '/src/Views/header.php';
$v = $item ?? [];
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($missingProgramArea): ?>
<div class="alert alert-error">Su usuario no tiene un área de programa asignada. No podrá subir contenido hasta que un administrador se la asigne.</div>
<?php endif; ?>
<div class="card"><form method="post" enctype="multipart/form-data">
<?= Security::csrfField() ?>
<input type="hidden" name="id" value="<?= (int) $id ?>">
<div class="form-group"><label>Orden</label><input type="number" name="display_order" value="<?= e((string) ($v['display_order'] ?? 0)) ?>"></div>
<?php render_duration_field($v); ?>
<?php render_validity_fieldset($v['show_until'] ?? null); ?>
<div class="form-group">
  <span class="form-label" id="media_file_label">Media</span>
  <div class="file-picker">
    <input id="media_file" type="file" name="media_file" accept="image/*,video/mp4,video/webm,.jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.webm,.pdf" <?= $missingProgramArea ? 'disabled' : '' ?>>
    <button class="btn btn-ghost btn-sm" type="button" id="media-pick-btn" <?= $missingProgramArea ? 'disabled' : '' ?>>Seleccionar archivo</button>
    <span class="file-picker-name" id="media-file-name">Ningún archivo seleccionado</span>
  </div>
  <p class="hint" id="media-file-hint">
    <?php if (!empty($v['media_path'])): ?>
      Archivo actual: <?= e((string) $v['media_path']) ?>. Suba otro para reemplazarlo.
    <?php else: ?>
      Imagen o video obligatorio al crear. Máx. 10 MB (imagen) / 500 MB (video).
    <?php endif; ?>
  </p>
</div>
<label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= !isset($v['is_active']) || !empty($v['is_active']) ? 'checked' : '' ?>> Activo</label>
<div class="actions mt-2">
  <button class="btn" type="submit" <?= $missingProgramArea ? 'disabled' : '' ?>>Guardar</button>
  <a class="btn btn-ghost" href="<?= e(APP_BASE) ?>/admin/academic/index.php">Cancelar</a>
</div>
</form></div>
<script>
(function () {
  var input = document.getElementById('media_file');
  var pickBtn = document.getElementById('media-pick-btn');
  var nameEl = document.getElementById('media-file-name');
  var hint = document.getElementById('media-file-hint');
  if (!input) return;
  function syncName() {
    var file = input.files && input.files[0];
    if (!nameEl) return;
    if (!file) {
      nameEl.textContent = 'Ningún archivo seleccionado';
      return;
    }
    var mb = (file.size / (1024 * 1024)).toFixed(1);
    nameEl.textContent = file.name + ' (' + mb + ' MB)';
    if (hint) {
      hint.textContent = 'Seleccionado: ' + file.name + ' (' + mb + ' MB)';
      hint.classList.toggle('text-red-600', file.size > 500 * 1024 * 1024);
      if (file.size > 500 * 1024 * 1024) {
        hint.textContent += ' — demasiado grande (máx. 500 MB).';
      }
    }
  }
  pickBtn && pickBtn.addEventListener('click', function () {
    if (input.disabled) return;
    input.click();
  });
  input.addEventListener('change', syncName);
})();
</script>
<?php require BASE_PATH . '/src/Views/footer.php'; ?>
