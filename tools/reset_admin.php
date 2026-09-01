<?php
/**
 * RESETEA LA CONTRASEÑA DEL ADMIN
 * Uso (mantenimiento, no público): php tools/reset_admin.php
 *
 * SOLO PARA USO EN DESARROLLO
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../src/Core/bootstrap.php';

$message = '';
$newHash = '';

// Generar nueva contraseña automáticamente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($newPass) < 6) {
        $message = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($newPass !== $confirm) {
        $message = 'Las contraseñas no coinciden';
    } else {
        try {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            $db = Database::getInstance();
            $stmt = $db->pdo()->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE username = 'admin'");
            $result = $stmt->execute([$hash]);

            if ($result) {
                $message = '✓ Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
                $newHash = $hash;
            } else {
                $message = '✗ No se pudo actualizar la contraseña';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CSET - Resetear contraseña Admin</title>
    <style>
        body { font-family: sans-serif; background: linear-gradient(135deg, #1e3a8a, #3b82f6); margin: 0; padding: 20px; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: white; padding: 40px; border-radius: 12px; max-width: 500px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #1e3a8a; margin: 0 0 8px; }
        p.sub { color: #64748b; margin: 0 0 24px; font-size: 14px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; color: #334155; font-size: 13px; }
        input[type=password], input[type=text] { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; margin-bottom: 16px; }
        button { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
        button:hover { background: #2563eb; }
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; }
        .alert-ok { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .warning { background: #fef3c7; color: #92400e; padding: 12px; border-radius: 6px; font-size: 12px; margin-top: 20px; }
        .hash-display { background: #1e293b; color: #e2e8f0; padding: 10px; border-radius: 6px; font-size: 11px; word-break: break-all; margin-top: 10px; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔑 CSET - Resetear Admin</h1>
    <p class="sub">Re-establece la contraseña del usuario <code>admin</code></p>

    <?php if ($message): ?>
        <div class="alert <?= strpos($message, '✓') !== false ? 'alert-ok' : 'alert-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($newHash): ?>
        <div class="alert alert-ok">
            ✓ Ahora ve a <a href="public/login.php">public/login.php</a> e inicia sesión con tu nueva contraseña.
        </div>
        <div class="hash-display">Hash generado: <?= htmlspecialchars($newHash) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Nueva contraseña para admin</label>
        <input type="password" name="new_password" required minlength="6" autofocus>

        <label>Confirmar contraseña</label>
        <input type="password" name="confirm_password" required minlength="6">

        <button type="submit">Actualizar contraseña</button>
    </form>

    <div class="warning">
        ⚠️ <strong>IMPORTANTE:</strong> Este script está en <code>tools/</code> (no público). No lo expongas vía HTTP.
    </div>
</div>
</body>
</html>