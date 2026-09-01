<?php
/**
 * Instalador Web para CSET
 * Uso (mantenimiento, no público): php install_web.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Si ya hay configuración válida, mostrar resumen
require_once __DIR__ . '/src/Core/bootstrap.php';

$step = $_GET['step'] ?? '1';
$error = '';
$success = '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CSET - Instalador Web</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            color: #1e293b;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(90deg, #1e3a8a, #3b82f6);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 8px 0 0; opacity: 0.9; }
        .content { padding: 30px; }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #e2e8f0;
            z-index: 0;
        }
        .step {
            background: #e2e8f0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 1;
        }
        .step.active { background: #3b82f6; color: white; }
        .step.done { background: #10b981; color: white; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #334155;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover { background: #2563eb; }
        .btn-success { background: #10b981; }
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
        .alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #10b981; }
        .alert-info { background: #eff6ff; color: #1e40af; border-left: 4px solid #3b82f6; }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table th, .info-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .info-table th { background: #f8fafc; font-weight: 600; }
        pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎓 CSET - Cartelera Digital</h1>
        <p>Instalador Web</p>
    </div>

    <div class="content">

        <!-- Indicador de pasos -->
        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">1</div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">2</div>
            <div class="step <?= $step >= 3 ? ($step > 3 ? 'done' : 'active') : '' ?>">3</div>
            <div class="step <?= $step >= 4 ? 'active' : '' ?>">4</div>
        </div>

        <?php if ($step == '1'): ?>
            <h2>Paso 1: Verificación del entorno</h2>

            <?php
            $checks = [];
            $checks[] = ['PHP >= 8.0', version_compare(PHP_VERSION, '8.0.0', '>='), 'Versión: ' . PHP_VERSION];
            $checks[] = ['pdo_pgsql', extension_loaded('pdo_pgsql'), ''];
            $checks[] = ['pgsql', extension_loaded('pgsql'), ''];
            $checks[] = ['mbstring', extension_loaded('mbstring'), ''];
            $checks[] = ['openssl', extension_loaded('openssl'), ''];

            // Conexión
            try {
                $db = Database::getInstance();
                $checks[] = ['Conexión a BD', true, DB_HOST . ':' . DB_PORT . ' / ' . DB_NAME];
            } catch (Exception $e) {
                $checks[] = ['Conexión a BD', false, $e->getMessage()];
            }

            // Tablas
            try {
                $tables = ['users', 'academic_content', 'center_content', 'general_content',
                           'gallery_content', 'media_files', 'display_config'];
                $existing = 0;
                foreach ($tables as $t) {
                    try { $db->fetchColumn("SELECT 1 FROM $t LIMIT 1"); $existing++; } catch (Exception $e) {}
                }
                $checks[] = ['Tablas IES-TV', $existing === count($tables),
                    "$existing de " . count($tables) . ' tablas presentes'];
            } catch (Exception $e) {
                $checks[] = ['Tablas IES-TV', false, 'No se pudo verificar'];
            }
            ?>

            <table class="info-table">
                <tr><th>Componente</th><th>Estado</th><th>Detalle</th></tr>
                <?php foreach ($checks as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c[0]) ?></td>
                    <td><?= $c[1] ? '<span style="color:#10b981;font-weight:bold;">✓ OK</span>' : '<span style="color:#ef4444;font-weight:bold;">✗ FAIL</span>' ?></td>
                    <td><?= htmlspecialchars($c[2]) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <?php
            $allOk = !in_array(false, array_column($checks, 1));
            if ($allOk): ?>
                <div class="alert alert-success">
                    ✓ Entorno verificado correctamente. Puede continuar al paso 2.
                </div>
                <a href="?step=2" class="btn btn-success">Continuar →</a>
            <?php else: ?>
                <div class="alert alert-error">
                    ✗ Hay problemas. Revise la configuración de la base de datos en <code>src/Core/Config.php</code>.
                </div>
            <?php endif; ?>

        <?php elseif ($step == '2'): ?>
            <h2>Paso 2: Configurar administrador</h2>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $adminUser = trim($_POST['admin_user'] ?? 'admin');
                $newPass = $_POST['new_password'] ?? '';
                $confirmPass = $_POST['confirm_password'] ?? '';

                if (strlen($newPass) < 10) {
                    $error = 'La contraseña debe tener mínimo 10 caracteres';
                } elseif (!preg_match('/[A-Z]/', $newPass) || !preg_match('/[a-z]/', $newPass) ||
                          !preg_match('/\d/', $newPass) || !preg_match('/[^A-Za-z0-9]/', $newPass)) {
                    $error = 'La contraseña debe tener mayúsculas, minúsculas, números y símbolos';
                } elseif ($newPass !== $confirmPass) {
                    $error = 'Las contraseñas no coinciden';
                } else {
                    try {
                        $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                        $db = Database::getInstance();
                        $stmt = $db->pdo()->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE username = ?");
                        $result = $stmt->execute([$hash, $adminUser]);

                        if ($result) {
                            Security::redirect('?step=3&success=1');
                        } else {
                            $error = 'No se pudo actualizar la contraseña';
                        }
                    } catch (Exception $e) {
                        $error = 'Error: ' . $e->getMessage();
                    }
                }
            }
            ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Usuario administrador</label>
                    <input type="text" name="admin_user" value="admin" required>
                </div>
                <div class="form-group">
                    <label>Nueva contraseña</label>
                    <input type="password" name="new_password" required minlength="10">
                    <small style="color:#64748b;">Mínimo 10 caracteres con mayúsculas, minúsculas, números y símbolos.</small>
                </div>
                <div class="form-group">
                    <label>Confirmar contraseña</label>
                    <input type="password" name="confirm_password" required minlength="10">
                </div>
                <button type="submit" class="btn btn-success">Actualizar contraseña</button>
            </form>

        <?php elseif ($step == '3'): ?>
            <h2>Paso 3: Configuración inicial</h2>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $configs = $_POST['config'] ?? [];
                try {
                    $db = Database::getInstance();
                    foreach ($configs as $key => $value) {
                        $exists = $db->fetch('SELECT id FROM display_config WHERE config_key = ?', [$key]);
                        if ($exists) {
                            $db->update('display_config',
                                ['config_value' => trim($value), 'updated_at' => date('c')],
                                'id = :id', ['id' => $exists['id']]);
                        } else {
                            $db->insert('display_config', [
                                'config_key' => $key, 'config_value' => trim($value),
                                'updated_at' => date('c'),
                            ]);
                        }
                    }
                    Security::redirect('?step=4&success=1');
                } catch (Exception $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
            }

            // Cargar configuración actual
            try {
                $currentConfig = DisplayConfig::all();
                $configMap = [];
                foreach ($currentConfig as $c) {
                    $configMap[$c['config_key']] = $c['config_value'];
                }
            } catch (Exception $e) {
                $configMap = [];
            }
            ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Título del sitio (aparece en la TV)</label>
                    <input type="text" name="config[tv_branding_title]"
                           value="<?= htmlspecialchars($configMap['tv_branding_title'] ?? 'CSET - Cartelera Digital') ?>">
                </div>
                <div class="form-group">
                    <label>Subtítulo</label>
                    <input type="text" name="config[tv_branding_subtitle]"
                           value="<?= htmlspecialchars($configMap['tv_branding_subtitle'] ?? 'Formación para el trabajo') ?>">
                </div>
                <button type="submit" class="btn btn-success">Guardar configuración</button>
            </form>

        <?php elseif ($step == '4'): ?>
            <h2>✓ ¡Instalación completada!</h2>

            <div class="alert alert-success">
                El sistema CSET está listo. Puede acceder al panel de administración.
            </div>

            <h3 style="margin-top:30px;">Accesos directos</h3>
            <table class="info-table">
                <tr>
                    <th>Recurso</th><th>URL</th>
                </tr>
                <tr>
                    <td>📺 TV Display</td>
                    <td><a href="public/index.php" target="_blank">public/index.php</a></td>
                </tr>
                <tr>
                    <td>🔐 Login Admin</td>
                    <td><a href="public/login.php" target="_blank">public/login.php</a></td>
                </tr>
                <tr>
                    <td>📊 Dashboard</td>
                    <td><a href="public/admin/dashboard.php" target="_blank">public/admin/dashboard.php</a></td>
                </tr>
                <tr>
                    <td>🔌 API JSON</td>
                    <td><a href="public/api.php" target="_blank">public/api.php</a></td>
                </tr>
            </table>

            <h3 style="margin-top:30px;">Próximos pasos</h3>
            <ol>
                <li>Crea contenido desde <strong>🎓 Información Académica</strong></li>
                <li>Agrega contenido de cada sector en <strong>🏛️ Nuestro Centro</strong></li>
                <li>Configura noticias en <strong>📰 Información General</strong></li>
                <li>Cambia las contraseñas de los usuarios <code>jp_*</code></li>
                <li>Elimina este archivo (<code>install_web.php</code>) por seguridad</li>
            </ol>

            <div class="alert alert-info" style="margin-top:20px;">
                <strong>Importante:</strong> Por seguridad, elimina o renombra <code>install_web.php</code>
                una vez completada la instalación.
            </div>

            <a href="public/login.php" class="btn">Ir al panel de administración →</a>

        <?php endif; ?>

    </div>
</div>
</body>
</html>