<?php
require_once __DIR__ . '/../../../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';
require_once BASE_PATH . '/src/Views/auth_guard.php';
require_once BASE_PATH . '/src/Views/crud.php';

Auth::requireRole(['admin', 'editor']);
$error = '';
$welcome = DisplayConfig::getWelcome();
$defaults = DisplayConfig::welcomeDefaults();

$colorFields = [
    'bg_color' => 'Fondo',
    'saludo_color' => 'Saludo',
    'titulo_color' => 'Título',
    'centro_color' => 'Centro',
    'regional_color' => 'Regional',
    'lema_color' => 'Lema',
    'accent_color' => 'Negrita (lema/pie)',
    'hashtag_color' => 'Hashtag',
    'pie_color' => 'Pie',
    'deco_color' => 'Decoración',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    try {
        if ($action === 'remove_bg') {
            $old = DisplayConfig::get('welcome_bg_path');
            if ($old && is_file(UPLOAD_PATH . '/welcome/' . $old)) {
                @unlink(UPLOAD_PATH . '/welcome/' . $old);
            }
            DisplayConfig::set('welcome_bg_path', '', Session::userId());
            DisplayConfig::set('welcome_bg_url', '', Session::userId());
            Logger::log(Session::userId(), 'delete_welcome_bg', 'display_config', null);
            flash_set('success', 'Fondo eliminado.');
            redirect(APP_BASE . '/admin/welcome/index.php');
        }

        $source = $_POST;
        if ($action === 'restore_texts') {
            foreach (['saludo', 'titulo', 'centro', 'regional', 'lema', 'hashtag', 'pie', 'duration'] as $k) {
                $source['welcome_' . $k] = (string) $defaults[$k];
            }
        }
        if ($action === 'restore_colors') {
            foreach (['bg_color', 'accent_color', 'text_color', 'saludo_color', 'titulo_color', 'centro_color', 'regional_color', 'lema_color', 'hashtag_color', 'pie_color', 'deco_color'] as $k) {
                $source['welcome_' . $k] = (string) $defaults[$k];
            }
        }

        $allowed = DisplayConfig::welcomeEditableKeys();
        foreach ($source as $key => $value) {
            if (!is_string($key) || is_array($value)) {
                continue;
            }
            if (!str_starts_with($key, 'welcome_')) {
                $key = 'welcome_' . $key;
            }
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $raw = trim((string) $value);
            if ($key === 'welcome_duration') {
                $n = max(3, min(120, (int) $raw));
                $raw = (string) $n;
            } elseif (in_array($key, ['welcome_lema', 'welcome_pie'], true)) {
                $raw = DisplayConfig::sanitizeWelcomeHtml($raw);
            } else {
                $raw = strip_tags($raw);
            }
            DisplayConfig::set($key, $raw, Session::userId());
        }
        if ($action !== 'restore_texts' && $action !== 'restore_colors' && !empty($_FILES['background']['name'])) {
            $filename = save_upload('background', 'welcome');
            if ($filename) {
                $old = DisplayConfig::get('welcome_bg_path');
                if ($old && is_file(UPLOAD_PATH . '/welcome/' . $old) && $old !== $filename) {
                    @unlink(UPLOAD_PATH . '/welcome/' . $old);
                }
                DisplayConfig::set('welcome_bg_path', $filename, Session::userId());
                DisplayConfig::set('welcome_bg_url', APP_BASE . '/assets/uploads/welcome/' . $filename, Session::userId());
            }
        }
        Logger::log(Session::userId(), 'update_welcome', 'display_config', null);
        $msg = match ($action) {
            'restore_texts' => 'Textos restaurados.',
            'restore_colors' => 'Colores restaurados.',
            default => 'Bienvenida actualizada.',
        };
        flash_set('success', $msg);
        redirect(APP_BASE . '/admin/welcome/index.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $welcome = array_merge($welcome, $_POST);
    }
}

$pageTitle = 'Bienvenida TV';
require BASE_PATH . '/src/Views/header.php';
$v = $welcome;
$bgUrl = (string) ($v['bg_url'] ?? '');
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="grid-2">
<div class="card">
<form method="post" enctype="multipart/form-data">
<?= Security::csrfField() ?>
<p class="hint">Plantilla del slide inicial de la cartelera.</p>
<div class="actions" style="margin-bottom:1rem">
  <button class="btn btn-ghost" type="submit" name="action" value="restore_texts">Restaurar textos</button>
  <button class="btn btn-ghost" type="submit" name="action" value="restore_colors">Restaurar colores</button>
</div>
<h2>Textos</h2>
<div class="form-group"><label>Saludo</label><input name="welcome_saludo" value="<?= e($v['saludo'] ?? $v['welcome_saludo'] ?? '') ?>"></div>
<div class="form-group"><label>Título</label><textarea name="welcome_titulo"><?= e($v['titulo'] ?? $v['welcome_titulo'] ?? '') ?></textarea></div>
<div class="form-group"><label>Centro</label><input name="welcome_centro" value="<?= e($v['centro'] ?? $v['welcome_centro'] ?? '') ?>"></div>
<div class="form-group"><label>Regional</label><input name="welcome_regional" value="<?= e($v['regional'] ?? $v['welcome_regional'] ?? '') ?>"></div>
<div class="form-group"><label>Lema</label><textarea name="welcome_lema"><?= e($v['lema'] ?? $v['welcome_lema'] ?? '') ?></textarea>
<p class="hint">Permite &lt;b&gt;negrita&lt;/b&gt;.</p></div>
<div class="form-group"><label>Hashtag</label><input name="welcome_hashtag" value="<?= e($v['hashtag'] ?? $v['welcome_hashtag'] ?? '') ?>"></div>
<div class="form-group"><label>Pie</label><textarea name="welcome_pie"><?= e($v['pie'] ?? $v['welcome_pie'] ?? '') ?></textarea>
<p class="hint">Permite &lt;b&gt;negrita&lt;/b&gt;.</p></div>
<div class="form-group"><label>Duración (seg)</label><input type="number" name="welcome_duration" min="3" max="120" value="<?= e((string) ($v['duration'] ?? $v['welcome_duration'] ?? 8)) ?>"></div>

<h2>Colores</h2>
<div class="grid-2">
<?php foreach ($colorFields as $key => $label):
    $val = (string) ($v[$key] ?? $v['welcome_' . $key] ?? $defaults[$key] ?? '#39a900');
    render_color_field('welcome_' . $key, $label, $val);
endforeach; ?>
</div>

<h2>Imagen de fondo</h2>
<?php if ($bgUrl !== ''): ?>
<div class="actions" style="margin-bottom:1rem;align-items:center">
  <img class="thumb" src="<?= e($bgUrl) ?>" alt="">
  <button class="btn btn-danger btn-sm" type="submit" name="action" value="remove_bg">Quitar fondo</button>
</div>
<?php endif; ?>
<div class="form-group"><label>Fondo (JPG/PNG/SVG)</label><input type="file" name="background" accept="image/*"></div>
<div class="actions mt-2">
  <button class="btn" type="submit" name="action" value="save">Guardar</button>
</div>
</form>
</div>
<div class="card">
    <h2>Vista previa</h2>
    <p class="hint">Cartelera en vivo. Los cambios se ven tras guardar (la TV consulta cada ~30 s).</p>
    <div class="tv-preview-wrap pointer-events-none" data-tv-preview>
      <div class="tv-preview-scale" data-tv-scale style="width:1920px;height:1080px">
        <iframe class="tv-preview-frame" src="<?= e(APP_BASE) ?>/?preview=1" title="Vista previa TV" width="1920" height="1080" tabindex="-1" loading="lazy"></iframe>
      </div>
    </div>
</div>
</div>
<?php require BASE_PATH . '/src/Views/footer.php'; ?>
