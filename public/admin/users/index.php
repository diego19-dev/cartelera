<?php
require_once __DIR__ . '/../../../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';
require_once BASE_PATH . '/src/Views/auth_guard.php';
require_once BASE_PATH . '/src/Views/crud.php';

Auth::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    require_post_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0 && $id !== (int) Session::userId()) {
        $existing = User::find($id);
        if ($existing) {
            User::toggleActive($id);
            Logger::log(Session::userId(), 'toggle_user', 'users', $id);
            $active = filter_var($existing['is_active'], FILTER_VALIDATE_BOOLEAN);
            flash_set('success', $active ? 'Usuario desactivado.' : 'Usuario activado.');
        }
    } else {
        flash_set('error', 'No puede desactivar su propia cuenta.');
    }
    redirect(APP_BASE . '/admin/users/index.php');
}

$items = User::all();
$pageTitle = 'Usuarios';
require BASE_PATH . '/src/Views/header.php';
?>
<div class="toolbar">
    <div class="muted"><?= count($items) ?> usuario(s)</div>
    <a class="btn" href="<?= e(APP_BASE) ?>/admin/users/form.php">Nuevo</a>
</div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
<tbody>
<?php foreach ($items as $row): ?>
<tr>
  <td><?= e($row['username'] ?? '') ?></td>
  <td><?= e($row['full_name'] ?? '') ?></td>
  <td><?= e(role_label($row['role'] ?? '')) ?></td>
  <td><span class="badge <?= !empty($row['is_active']) ? 'badge-ok' : 'badge-off' ?>"><?= !empty($row['is_active']) ? 'Activo' : 'Inactivo' ?></span></td>
  <td class="actions">
    <a class="btn btn-ghost btn-sm" href="<?= e(APP_BASE) ?>/admin/users/form.php?id=<?= (int)$row['id'] ?>">Editar</a>
    <?php if ((int)$row['id'] !== (int)Session::userId()): ?>
    <form method="post">
      <?= Security::csrfField() ?>
      <input type="hidden" name="action" value="toggle">
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <button class="btn btn-sm <?= !empty($row['is_active']) ? 'btn-danger' : 'btn-ghost' ?>" type="submit"
        onclick="return confirm('<?= !empty($row['is_active']) ? '¿Desactivar este usuario?' : '¿Activar este usuario?' ?>');">
        <?= !empty($row['is_active']) ? 'Desactivar' : 'Activar' ?>
      </button>
    </form>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php require BASE_PATH . '/src/Views/footer.php'; ?>
