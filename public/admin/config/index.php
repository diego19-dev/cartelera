<?php
require_once __DIR__ . '/../../../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';
require_once BASE_PATH . '/src/Views/auth_guard.php';
require_once BASE_PATH . '/src/Views/crud.php';

Auth::requireRole(['admin']);
$error = '';

$scenarioKeys = ['academic' => 'Académico', 'center' => 'Centro', 'general' => 'General', 'gallery' => 'Galería'];
$lightColors = [
    'brand_primary_color' => ['Color principal', 'Barra y botones destacados', '#39a900'],
    'brand_secondary_color' => ['Color secundario', 'Degradado de la barra', '#3366cc'],
    'brand_accent_color' => ['Color de acento', 'Reloj, títulos y progreso', '#787877'],
    'brand_bg_color' => ['Fondo de pantalla', 'Fondo general de la cartelera', '#ffffff'],
    'brand_text_color' => ['Color del texto', 'Texto principal sobre el fondo', '#000000'],
];
$darkColors = [
    'brand_dark_primary_color' => ['Color principal', 'Barra y botones destacados', '#39a900'],
    'brand_dark_secondary_color' => ['Color secundario', 'Degradado de la barra', '#3366cc'],
    'brand_dark_accent_color' => ['Color de acento', 'Reloj, títulos y progreso', '#787877'],
    'brand_dark_bg_color' => ['Fondo de pantalla', 'Fondo general de la cartelera', '#000000'],
    'brand_dark_text_color' => ['Color del texto', 'Texto principal sobre el fondo', '#ffffff'],
];
$tvColors = [
    'tv_color_header' => ['Barra superior', 'Encabezado con logo y reloj', '#39a900'],
    'tv_color_badge' => ['Etiqueta de escenario', 'Badge de categoría', '#39a900'],
    'tv_color_icon' => ['Iconos de detalle', 'Programa y área en las diapositivas', '#39a900'],
    'tv_color_live' => ['Indicador EN VIVO', 'Badge de estado en el pie', '#39a900'],
    'tv_color_progress' => ['Barra de progreso', 'Segmentos del carrusel', '#39a900'],
];

function display_cfg(string $key, string $default = ''): string {
    return DisplayConfig::get($key, $default) ?? $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    try {
        if ($action === 'defaults_brand') {
            foreach (array_merge($lightColors, $darkColors) as $key => $meta) {
                DisplayConfig::set($key, $meta[2], Session::userId());
            }
            flash_set('success', 'Paleta predeterminada aplicada.');
            redirect(APP_BASE . '/admin/config/index.php');
        }
        if ($action === 'defaults_tv') {
            foreach ($tvColors as $key => $meta) {
                DisplayConfig::set($key, $meta[2], Session::userId());
            }
            flash_set('success', 'Colores TV restaurados.');
            redirect(APP_BASE . '/admin/config/index.php');
        }
        if ($action === 'delete_logo') {
            $old = DisplayConfig::get('brand_logo_path');
            if ($old && is_file(UPLOAD_PATH . '/branding/' . $old)) {
                @unlink(UPLOAD_PATH . '/branding/' . $old);
            }
            DisplayConfig::set('brand_logo_path', '', Session::userId());
            DisplayConfig::set('brand_logo_url', '', Session::userId());
            flash_set('success', 'Logo eliminado.');
            redirect(APP_BASE . '/admin/config/index.php');
        }

        $positions = [];
        foreach (array_keys($scenarioKeys) as $key) {
            $positions[$key] = (int) ($_POST['scenario_pos'][$key] ?? 99);
        }
        asort($positions, SORT_NUMERIC);
        DisplayConfig::set('tv_scenario_order', implode(',', array_keys($positions)), Session::userId());

        $keys = [
            'tv_enabled', 'tv_off_message',
            'tv_branding_title', 'tv_branding_subtitle',
            'api_poll_seconds', 'refresh_external_minutes',
        ];
        foreach (array_keys($lightColors + $darkColors + $tvColors) as $key) {
            $keys[] = $key;
        }
        foreach ($keys as $key) {
            if (!isset($_POST[$key])) {
                continue;
            }
            DisplayConfig::set($key, trim((string) $_POST[$key]), Session::userId());
        }
        if (!empty($_FILES['logo']['name'])) {
            $upload = MediaController::upload($_FILES['logo'], 'branding');
            $old = DisplayConfig::get('brand_logo_path');
            if ($old && is_file(UPLOAD_PATH . '/branding/' . $old) && $old !== $upload['filename']) {
                @unlink(UPLOAD_PATH . '/branding/' . $old);
            }
            DisplayConfig::set('brand_logo_path', $upload['filename'], Session::userId());
            DisplayConfig::set('brand_logo_url', $upload['url'] ?? (APP_BASE . '/assets/uploads/branding/' . $upload['filename']), Session::userId());
        }
        Logger::log(Session::userId(), 'update_config', 'display_config', null);
        flash_set('success', 'Configuración guardada.');
        redirect(APP_BASE . '/admin/config/index.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$orderRaw = display_cfg('tv_scenario_order', 'academic,center,general,gallery');
$order = array_values(array_filter(array_map('trim', explode(',', $orderRaw))));
foreach (array_keys($scenarioKeys) as $key) {
    if (!in_array($key, $order, true)) {
        $order[] = $key;
    }
}

$pageTitle = 'Configuración';
require BASE_PATH . '/src/Views/header.php';
$tvOn = display_cfg('tv_enabled', '1') !== '0';
$logoUrl = DisplayConfig::logoPublicUrl();
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<?= Security::csrfField() ?>

<div class="card">
<h2>TV</h2>
<p class="hint">Arrastre conceptual: asigne un número de orden a cada escenario (1 = primero).</p>
<?php foreach ($order as $i => $key): if (!isset($scenarioKeys[$key])) continue; ?>
<div class="form-group">
  <label><?= e($scenarioKeys[$key]) ?></label>
  <input type="number" name="scenario_pos[<?= e($key) ?>]" min="1" max="4" value="<?= (int) ($i + 1) ?>">
</div>
<?php endforeach; ?>
<p class="hint">La duración de cada diapositiva se define al crear o editar el contenido.</p>
</div>

<div class="card">
<h2>Colores de la cartelera</h2>
<p class="hint">Pintan la TV (barra, badges, progreso). Vacío = color principal del modo oscuro.</p>
<div class="grid-2">
<?php foreach ($tvColors as $key => $meta):
    render_color_field($key, $meta[0], display_cfg($key, $meta[2]), $meta[1]);
endforeach; ?>
</div>
<div class="actions">
  <button class="btn btn-ghost" type="submit" name="action" value="defaults_tv">Restaurar colores TV</button>
</div>
</div>

<div class="card danger-zone">
<h2>Apagar / encender cartelera</h2>
<div class="form-group"><label>Estado</label>
<select name="tv_enabled">
  <option value="1" <?= $tvOn ? 'selected' : '' ?>>Encendida</option>
  <option value="0" <?= !$tvOn ? 'selected' : '' ?>>Apagada</option>
</select></div>
<div class="form-group"><label>Mensaje cuando está apagada</label>
<input name="tv_off_message" maxlength="120" value="<?= e(display_cfg('tv_off_message', 'Cartelera apagada')) ?>"></div>
</div>

<div class="card">
<h2>Identidad</h2>
<div class="form-group"><label>Título TV</label>
<input name="tv_branding_title" value="<?= e(display_cfg('tv_branding_title', 'IES - Cartelera Digital')) ?>"></div>
<div class="form-group"><label>Subtítulo</label>
<input name="tv_branding_subtitle" value="<?= e(display_cfg('tv_branding_subtitle', 'Formación para el trabajo')) ?>"></div>
<div class="form-group"><label>Logo</label>
<input type="file" name="logo" accept="image/jpeg,image/png,image/svg+xml,.jpg,.jpeg,.png,.svg">
<p class="hint">JPG, PNG o SVG. Máximo 5 MB. Se muestra en la TV y en el panel.</p></div>
<?php if ($logoUrl !== ''): ?>
<div class="actions" style="align-items:center;margin-bottom:1rem">
  <img class="thumb" src="<?= e($logoUrl) ?>" alt="Logo">
  <button class="btn btn-danger btn-sm" type="submit" name="action" value="delete_logo">Quitar logo</button>
</div>
<?php endif; ?>
</div>

<div class="card">
<h2>Colores de marca — modo claro</h2>
<div class="grid-2">
<?php foreach ($lightColors as $key => $meta):
    render_color_field($key, $meta[0], display_cfg($key, $meta[2]), $meta[1]);
endforeach; ?>
</div>
</div>

<div class="card">
<h2>Colores de marca — modo oscuro</h2>
<p class="hint">Fallback de la TV si un color de cartelera está vacío.</p>
<div class="grid-2">
<?php foreach ($darkColors as $key => $meta):
    render_color_field($key, $meta[0], display_cfg($key, $meta[2]), $meta[1]);
endforeach; ?>
</div>
<div class="actions">
  <button class="btn btn-ghost" type="submit" name="action" value="defaults_brand">Colores predeterminados</button>
</div>
</div>

<div class="card">
<h2>Tiempo real</h2>
<div class="grid-2">
<div class="form-group"><label>Polling API (s)</label>
<p class="hint">Cada cuántos segundos la TV consulta si hay contenido nuevo.</p>
<input type="number" name="api_poll_seconds" min="5" max="300" value="<?= e(display_cfg('api_poll_seconds', '30')) ?>"></div>
<div class="form-group"><label>Refresh externos (min)</label>
<p class="hint">Cada cuántos minutos se actualizan feeds externos.</p>
<input type="number" name="refresh_external_minutes" min="1" max="1440" value="<?= e(display_cfg('refresh_external_minutes', '30')) ?>"></div>
</div>
</div>

<div class="actions">
  <button class="btn" type="submit" name="action" value="save">Guardar</button>
</div>
</form>
<?php require BASE_PATH . '/src/Views/footer.php'; ?>
