<?php
/**
 * Listado de contenido académico. POST action=delete + CSRF.
 * program_head solo ve/borra lo que creó (cset_academic_list_for_user).
 */
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/src/helpers.php';
require_once BASE_PATH . '/src/auth_guard.php';
require_once BASE_PATH . '/src/crud.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_post_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $item = AcademicContent::find($id);
        if (!$item || !cset_can_manage_academic_item($item)) {
            flash_set('error', 'Sin permisos para eliminar este contenido.');
        } else {
            try {
                AcademicContent::delete($id);
                Logger::log(Session::userId(), 'delete', 'academic_content', $id);
                flash_set('success', 'Registro eliminado.');
            } catch (Throwable $e) {
                flash_set('error', 'No se pudo eliminar el registro.');
            }
        }
    }
    redirect(APP_BASE . '/admin/academic/index.php');
}

$items = cset_academic_list_for_user();
$pageTitle = 'Contenido Académico';
require BASE_PATH . '/src/header.php';
?>
<div class="toolbar">
    <div class="muted"><?= count($items) ?> registro(s)</div>
    <a class="btn" href="<?= e(APP_BASE) ?>/admin/academic/form.php">Nuevo</a>
</div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Orden</th><th>Media</th><th>Estado</th><th></th></tr></thead>
<tbody>
<?php if (!$items): ?><tr><td colspan="4" class="muted">Sin registros.</td></tr><?php endif; ?>
<?php foreach ($items as $row): ?>
<tr>
<td><?= e((string) ($row['display_order'] ?? '')) ?></td>
<td class="media-cell">
  <div class="media-cell-inner">
    <?php render_media_preview($row['media_path'] ?? null, 'academic', $row['media_type'] ?? null, ['class' => 'thumb thumb-sm', 'show_empty' => true]); ?>
    <span class="media-name"><?= e($row['media_path'] ?? '—') ?></span>
  </div>
</td>
  <td><?php render_tv_status_badge($row); ?></td>
  <td class="actions">
    <a class="btn btn-ghost btn-sm" href="<?= e(APP_BASE) ?>/admin/academic/form.php?id=<?= (int)$row['id'] ?>">Editar</a>
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
