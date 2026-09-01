<?php
require_once __DIR__ . '/../../../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';
require_once BASE_PATH . '/src/Views/auth_guard.php';
require_once BASE_PATH . '/src/Views/crud.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_post_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        MediaFile::delete($id);
        Logger::log(Session::userId(), 'delete', 'media_files', $id);
        flash_set('success', 'Archivo eliminado.');
    }
    redirect(APP_BASE . '/admin/media/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    require_post_csrf();
    try {
        $category = trim((string) ($_POST['category'] ?? 'media')) ?: 'media';
        $filename = save_upload('media_file', $category);
        if (!$filename) {
            throw new RuntimeException('Seleccione un archivo.');
        }
        $id = MediaFile::register([
            'filename' => $filename,
            'original_name' => $_FILES['media_file']['name'] ?? $filename,
            'mime_type' => $_FILES['media_file']['type'] ?? null,
            'file_size' => (int) ($_FILES['media_file']['size'] ?? 0),
            'file_path' => UPLOAD_PATH . '/' . $category . '/' . $filename,
            'category' => $category,
        ], (int) Session::userId());
        Logger::log(Session::userId(), 'upload', 'media_files', $id);
        flash_set('success', 'Archivo subido.');
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
    }
    redirect(APP_BASE . '/admin/media/index.php');
}

$items = MediaFile::all(200);
$pageTitle = 'Biblioteca Media';
require BASE_PATH . '/src/Views/header.php';
?>
<div class="toolbar">
    <div class="muted"><?= count($items) ?> archivo(s)</div>
</div>
<div class="card">
    <h2>Subir archivo</h2>
    <form method="post" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="upload">
        <div class="form-group"><label>Categoría</label>
            <select name="category">
                <?php foreach (['media','general','center','academic','gallery','branding','welcome'] as $cat): ?>
                    <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Archivo</label><input type="file" name="media_file" required></div>
        <button class="btn" type="submit">Subir</button>
    </form>
</div>
<div class="card">
<div class="table-wrap"><table>
<thead><tr><th>Archivo</th><th>Categoría</th><th>Tamaño</th><th></th></tr></thead>
<tbody>
<?php if (!$items): ?><tr><td colspan="4" class="muted">Sin archivos.</td></tr><?php endif; ?>
<?php foreach ($items as $row): ?>
<tr>
  <td><?= e($row['original_name'] ?? $row['filename'] ?? '') ?></td>
  <td><?= e($row['category'] ?? '') ?></td>
  <td><?= e((string) ($row['file_size'] ?? $row['size_bytes'] ?? '')) ?></td>
  <td class="actions">
    <form method="post" onsubmit="return confirm('¿Eliminar?');">
      <?= Security::csrfField() ?>
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
      <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php require BASE_PATH . '/src/Views/footer.php'; ?>
