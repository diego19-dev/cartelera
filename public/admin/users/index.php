<?php
require_once __DIR__ . '/../../../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';
require_once BASE_PATH . '/src/Views/auth_guard.php';
require_once BASE_PATH . '/src/Views/crud.php';

Auth::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_post_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0 && $id !== (int) Session::userId()) {
        Database::getInstance()->delete('users', 'id = :id', ['id' => $id]);
        Logger::log(Session::userId(), 'delete', 'users', $id);
        flash_set('success', 'Usuario eliminado.');
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
  <td><?= e($row['role'] ?? '') ?></td>
  <td><span class="badge <?= !empty($row['is_active']) ? 'badge-ok' : 'badge-off' ?>"><?= !empty($row['is_active']) ? 'Activo' : 'Inactivo' ?></span></td>
  <td class="actions">
    <a class="btn btn-ghost btn-sm" href="<?= e(APP_BASE) ?>/admin/users/form.php?id=<?= (int)$row['id'] ?>">Editar</a>
    <?php if ((int)$row['id'] !== (int)Session::userId()): ?>
    <form method="post" onsubmit="return confirm('¿Eliminar usuario?');">
      <?= Security::csrfField() ?>
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
    </form>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php require BASE_PATH . '/src/Views/footer.php'; ?>
