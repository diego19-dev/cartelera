<?php
/**
 * Listado de información general. Solo admin. POST delete + CSRF.
 * Modelo: GeneralContent.
 */
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/src/helpers.php';
require_once BASE_PATH . '/src/auth_guard.php';
require_once BASE_PATH . '/src/crud.php';

Auth::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_post_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        GeneralContent::delete($id);
        Logger::log(Session::userId(), 'delete', 'general_content', $id);
        flash_set('success', 'Registro eliminado.');
    }
    redirect(APP_BASE . '/admin/general/index.php');
}

$items = GeneralContent::all();
$pageTitle = 'Contenido General';
require BASE_PATH . '/src/header.php';
?>
<div class="toolbar">
    <div class="muted"><?= count($items) ?> registro(s)</div>
    <a class="btn" href="<?= e(APP_BASE) ?>/admin/general/form.php">Nuevo</a>
</div>
<div class="card">
<div class="table-wrap"><table>
<thead><tr><th>Título</th><th>Categoría</th><th>Estado</th><th>Orden</th><th></th></tr></thead>
<tbody>
<?php if (!$items): ?><tr><td colspan="5" class="muted">Sin registros.</td></tr><?php endif; ?>
<?php foreach ($items as $row): ?>
<tr>
  <td><?= e($row['title'] ?? '') ?></td>
  <td><?= e($row['category'] ?? '') ?></td>
  <td><span class="badge <?= !empty($row['is_active']) ? 'badge-ok' : 'badge-off' ?>"><?= !empty($row['is_active']) ? 'Activo' : 'Inactivo' ?></span></td>
  <td><?= e((string)($row['sort_order'] ?? $row['display_order'] ?? '')) ?></td>
  <td class="actions">
    <a class="btn btn-ghost btn-sm" href="<?= e(APP_BASE) ?>/admin/general/form.php?id=<?= (int)$row['id'] ?>">Editar</a>
    <form method="post" onsubmit="return confirm('¿Eliminar?');">
      <?= Security::csrfField() ?>
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php require BASE_PATH . '/src/footer.php'; ?>
