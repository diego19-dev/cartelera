<?php
/**
 * Alta/edición/borrado de usuario (User). Solo admin + CSRF.
 * Roles: PANEL_ASSIGNABLE_ROLES. No borra el último admin ni la propia cuenta.
 */
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/src/helpers.php';
require_once BASE_PATH . '/src/auth_guard.php';
require_once BASE_PATH . '/src/crud.php';

Auth::requireRole(['admin']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$item = $id ? User::find($id) : null;
$error = '';
$actorId = (int) Session::userId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_post_csrf();
    try {
        if ($id <= 0 || !$item) {
            throw new RuntimeException('Usuario no encontrado.');
        }
        $typed = trim((string) ($_POST['confirm_username'] ?? ''));
        if (!hash_equals((string) $item['username'], $typed)) {
            throw new RuntimeException('Escriba el usuario de ingreso exactamente para confirmar la eliminación.');
        }
        User::delete($id, $actorId);
        Logger::log($actorId, 'delete_user', 'users', $id, [
            'username' => $item['username'],
        ]);
        flash_set('success', 'Usuario eliminado.');
        redirect(APP_BASE . '/admin/users/index.php');
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        Logger::error('delete_user failed', ['id' => $id, 'error' => $e->getMessage()]);
        $error = 'No se pudo eliminar el usuario.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'delete') {
    require_post_csrf();
    try {
        $validated = cset_validate_user_fields(
            trim((string) ($_POST['role'] ?? 'program_head')),
            trim((string) ($_POST['program_area'] ?? '')) ?: null
        );
        $data = [
            'username' => trim((string) ($_POST['username'] ?? '')),
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'role' => $validated['role'],
            'program_area' => $validated['program_area'],
            'is_active' => bool_from_post('is_active'),
        ];
        if ($data['username'] === '' || $data['full_name'] === '') {
            throw new RuntimeException('Usuario y nombre son obligatorios.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            throw new RuntimeException('El usuario solo admite letras, números y guion bajo.');
        }
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        $needPassword = $id <= 0;
        if ($needPassword && $password === '') {
            throw new RuntimeException('La contraseña es obligatoria para usuarios nuevos.');
        }
        if ($password !== '') {
            if ($password !== $confirm) {
                throw new RuntimeException('Las contraseñas no coinciden.');
            }
            if (!Security::isStrongPassword($password)) {
                throw new RuntimeException('La contraseña no cumple los requisitos: mínimo 10 caracteres, mayúscula, minúscula, número y carácter especial.');
            }
        }
        if ($id > 0) {
            User::update($id, $data);
            if ($password !== '') {
                User::changePassword($id, $password);
            }
            Logger::log(Session::userId(), 'update', 'users', $id);
            flash_set('success', 'Usuario actualizado.');
        } else {
            $data['password'] = $password;
            $id = User::create($data);
            Logger::log(Session::userId(), 'create', 'users', $id);
            flash_set('success', 'Usuario creado.');
        }
        redirect(APP_BASE . '/admin/users/index.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $item = array_merge($item ?? [], $_POST);
    }
}

$pageTitle = $id ? 'Editar usuario' : 'Nuevo usuario';
require BASE_PATH . '/src/header.php';
$v = $item ?? [];
$deleteBlockReason = $id > 0
    ? User::deletionBlockReason($item, $actorId, User::countAdmins())
    : null;
$canDelete = $deleteBlockReason === null && $id > 0;
$confirmUsername = (string) ($item['username'] ?? '');
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="card"><form method="post" id="user-form" autocomplete="off" novalidate>
<?= Security::csrfField() ?>
<input type="hidden" name="id" value="<?= (int) $id ?>">
<div class="form-group"><label>Usuario de ingreso</label>
<input name="username" required pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guion bajo" value="<?= e($v['username'] ?? '') ?>">
<p class="hint">Nombre con el que inicia sesión (no es el sector ni el rol).</p></div>
<div class="form-group"><label>Nombre completo</label><input name="full_name" required value="<?= e($v['full_name'] ?? '') ?>"></div>
<div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($v['email'] ?? '') ?>"></div>
<div class="form-group"><label>Rol</label>
<select name="role" id="user-role">
<?php foreach (cset_user_role_options($v['role'] ?? null) as $k => $label): ?>
<option value="<?= e($k) ?>" <?= (($v['role'] ?? 'program_head') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
</select></div>
<div class="form-group" id="program-area-group"><label>Área de programa</label>
<select name="program_area" id="program-area">
<option value="">—</option>
<?php foreach (PROGRAM_AREAS as $k => $label): ?>
<option value="<?= e($k) ?>" <?= (($v['program_area'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
</select>
<p class="hint" id="program-area-hint">Obligatorio para jefes de programa.</p></div>
<div class="form-group"><label>Contraseña <?= $id ? '(dejar vacío para no cambiar)' : '' ?></label>
<input type="password" id="password" name="password" <?= $id ? '' : 'required' ?> autocomplete="new-password">
<ul class="req-list" id="password-requirements" aria-live="polite">
  <li data-req="length">Mínimo 10 caracteres</li>
  <li data-req="case">Al menos una letra mayúscula y una minúscula</li>
  <li data-req="digit">Al menos un número</li>
  <li data-req="special">Al menos un carácter especial</li>
</ul></div>
<div class="form-group"><label for="password_confirm">Confirmar contraseña</label>
<input type="password" id="password_confirm" name="password_confirm" <?= $id ? '' : 'required' ?> autocomplete="new-password">
<p class="hint password-match-hint hidden" id="password-match-hint" aria-live="polite"></p></div>
<label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= !isset($v['is_active']) || !empty($v['is_active']) ? 'checked' : '' ?>> Activo</label>
<div class="actions mt-2">
<button class="btn" type="submit">Guardar</button>
<a class="btn btn-ghost" href="<?= e(APP_BASE) ?>/admin/users/index.php">Cancelar</a>
</div>
</form></div>
<?php if ($id > 0): ?>
<div class="card danger-zone" id="eliminar-usuario">
<h2>Eliminar usuario</h2>
<?php if ($deleteBlockReason): ?>
<p class="hint"><?= e($deleteBlockReason) ?></p>
<?php else: ?>
<p class="hint">Esta acción es permanente. El contenido que haya publicado se conserva sin autor y sus sesiones se cierran. No se puede deshacer.</p>
<form method="post" id="delete-user-form" autocomplete="off">
<?= Security::csrfField() ?>
<input type="hidden" name="id" value="<?= (int) $id ?>">
<input type="hidden" name="action" value="delete">
<div class="form-group">
<label for="confirm-username">Escriba <strong><?= e($confirmUsername) ?></strong> para confirmar</label>
<input id="confirm-username" name="confirm_username" required autocomplete="off" spellcheck="false">
</div>
<button class="btn btn-danger" id="delete-user-btn" type="submit" disabled>Eliminar cuenta</button>
</form>
<?php endif; ?>
</div>
<?php endif; ?>
<script src="<?= e(cset_url('js/password-form.js')) ?>?v=1"></script>
<script>
(function () {
  var role = document.getElementById('user-role');
  var areaGroup = document.getElementById('program-area-group');
  var area = document.getElementById('program-area');
  function syncArea() {
    var isHead = role && role.value === 'program_head';
    if (areaGroup) areaGroup.style.display = isHead ? '' : 'none';
    if (area) area.required = isHead;
  }
  role && role.addEventListener('change', syncArea);
  syncArea();

  var confirmInput = document.getElementById('confirm-username');
  var deleteBtn = document.getElementById('delete-user-btn');
  var deleteForm = document.getElementById('delete-user-form');
  var expectedUsername = <?= json_encode($canDelete ? $confirmUsername : '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var deleteConfirmed = false;
  function syncDelete() {
    if (!confirmInput || !deleteBtn) return;
    deleteBtn.disabled = confirmInput.value !== expectedUsername;
  }
  confirmInput && confirmInput.addEventListener('input', syncDelete);
  syncDelete();

  deleteForm && deleteForm.addEventListener('submit', function (e) {
    if (deleteConfirmed) return;
    e.preventDefault();
    if (!confirmInput || confirmInput.value !== expectedUsername) return;
    var ask = typeof window.csetConfirm === 'function'
      ? window.csetConfirm('Eliminar usuario', '¿Eliminar este usuario de forma permanente? Esta acción no se puede deshacer.')
      : Promise.resolve(window.confirm('¿Eliminar este usuario de forma permanente?'));
    ask.then(function (ok) {
      if (!ok) return;
      deleteConfirmed = true;
      deleteForm.submit();
    });
  });
})();
</script>
<?php require BASE_PATH . '/src/footer.php'; ?>
