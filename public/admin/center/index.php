<?php
require_once __DIR__ . '/../../../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';
require_once BASE_PATH . '/src/Views/auth_guard.php';
require_once BASE_PATH . '/src/Views/crud.php';

cset_require_panel_module('center');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_post_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        CenterContent::delete($id);
        Logger::log(Session::userId(), 'delete', 'center_content', $id);
        flash_set('success', 'Registro eliminado.');
    }
    redirect(APP_BASE . '/admin/center/index.php');
}

$items = CenterContent::all();
$pageTitle = 'Contenido Centro';
require BASE_PATH . '/src/Views/header.php';
?>
<div class="toolbar">
    <div class="muted"><?= count($items) ?> registro(s)</div>
    <a class="btn" href="<?= e(APP_BASE) ?>/admin/center/form.php">Nuevo</a>
</div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Título</th><th>Descripción</th><th>Sector</th><th>Orden</th><th>Estado</th><th></th></tr></thead>
<tbody>
<?php if (!$items): ?><tr><td colspan="6" class="muted">Sin registros.</td></tr><?php endif; ?>
<?php foreach ($items as $row): ?>
<tr>
<td><?= e($row['title'] ?? $row['display_order'] ?? '') ?></td>
<td><?= e($row['description'] ?? $row['display_order'] ?? '') ?></td>
<td><?= e($row['sector'] ?? $row['display_order'] ?? '') ?></td>
<td><?= e($row['sort_order'] ?? $row['display_order'] ?? '') ?></td>
  <td><span class="badge <?= !empty($row['is_active']) ? 'badge-ok' : 'badge-off' ?>"><?= !empty($row['is_active']) ? 'Activo' : 'Inactivo' ?></span></td>
  <td class="actions">
    <a class="btn btn-ghost btn-sm" href="<?= e(APP_BASE) ?>/admin/center/form.php?id=<?= (int)$row['id'] ?>">Editar</a>
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
<?php require BASE_PATH . '/src/Views/footer.php'; ?>
