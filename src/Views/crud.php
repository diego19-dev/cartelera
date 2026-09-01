<?php
/**
 * Utilidades compartidas para CRUD del panel.
 */

function require_post_csrf(): void {
    $token = $_POST['_csrf'] ?? '';
    if (!Security::verifyCsrf(is_string($token) ? $token : null)) {
        http_response_code(403);
        echo 'Token CSRF inválido';
        exit;
    }
}

function bool_from_post(string $key): bool {
    return isset($_POST[$key]) && (string) $_POST[$key] !== '0' && (string) $_POST[$key] !== '';
}

function save_upload(string $field, string $category): ?string {
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error al subir el archivo.');
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $allowed = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_VIDEO_TYPES, ALLOWED_AUDIO_TYPES, ALLOWED_DOC_TYPES);
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Tipo de archivo no permitido.');
    }
    $dir = UPLOAD_PATH . '/' . $category;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('No se pudo crear el directorio de uploads.');
    }
    $name = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
        throw new RuntimeException('No se pudo guardar el archivo.');
    }
    return $name;
}

function format_bogota(?string $iso): string {
    if ($iso === null || trim($iso) === '') {
        return '';
    }
    try {
        $dt = (new DateTimeImmutable($iso))->setTimezone(new DateTimeZone('America/Bogota'));
        return $dt->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return '';
    }
}

function duration_seconds_from_post(int $default = 15): int {
    return VideoDuration::clampSlideSeconds((int) ($_POST['duration_seconds'] ?? $default));
}

function apply_media_upload(array &$data, string $field, string $category, string $pathKey = 'media_path'): void {
    if (empty($_FILES[$field]['name'])) {
        return;
    }
    $upload = MediaController::upload($_FILES[$field], $category);
    $data[$pathKey] = $upload['filename'];
    if ($pathKey === 'media_path' && !empty($upload['type'])) {
        $type = (string) $upload['type'];
        if (in_array($type, ['image', 'video', 'text', 'pdf'], true)) {
            $data['media_type'] = $type;
        }
    }
    if (($upload['type'] ?? '') === 'video' && isset($upload['duration_seconds'])) {
        $data['duration_seconds'] = (int) $upload['duration_seconds'];
    }
}

function render_duration_field(array $v, int $default = 15): void {
    $val = (int) ($v['duration_seconds'] ?? $default);
    if ($val < 1) {
        $val = $default;
    }
    $min = MIN_SLIDE_DURATION_SECONDS;
    $max = MAX_SLIDE_DURATION_SECONDS;
    echo '<div class="form-group"><label for="duration_seconds">Duración en TV (segundos)</label>';
    echo '<input id="duration_seconds" type="number" name="duration_seconds" min="' . (int) $min . '" max="' . (int) $max . '" value="' . e((string) $val) . '">';
    echo '<p class="hint">Entre ' . (int) $min . ' y ' . (int) $max . ' s. En video se toma la duración del archivo.</p></div>';
}

function render_color_field(string $name, string $label, string $value, string $help = ''): void {
    $hex = preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : '#39a900';
    echo '<div class="form-group"><label for="' . e($name) . '">' . e($label) . '</label>';
    if ($help !== '') {
        echo '<p class="hint">' . e($help) . '</p>';
    }
    echo '<div class="color-row">';
    echo '<input type="color" value="' . e($hex) . '" oninput="this.nextElementSibling.value=this.value">';
    echo '<input id="' . e($name) . '" name="' . e($name) . '" value="' . e($value) . '" maxlength="7" class="mono">';
    echo '</div></div>';
}

function render_validity_fieldset(?string $currentUntil): void {
    $hasCurrent = $currentUntil !== null && trim($currentUntil) !== '';
    $posted = (string) ($_POST['validity_mode'] ?? '');
    $mode = $posted !== '' ? $posted : ($hasCurrent ? 'keep' : 'none');
    $amount = (int) ($_POST['validity_amount'] ?? 1);
    if ($amount < 1) {
        $amount = 1;
    }
    $unit = (string) ($_POST['validity_unit'] ?? 'days');
    if (!in_array($unit, ['hours', 'days', 'months'], true)) {
        $unit = 'days';
    }
    $label = format_bogota($currentUntil);
    ?>
<fieldset class="validity">
    <legend>Vigencia en TV</legend>
    <p class="hint">Opcional. Mínimo 1 hora, máximo 6 meses. Sin límite = se muestra hasta que lo desactive.</p>
    <label class="checkbox"><input type="radio" name="validity_mode" value="none" <?= $mode === 'none' ? 'checked' : '' ?>> Sin límite de tiempo</label>
    <?php if ($hasCurrent): ?>
    <label class="checkbox"><input type="radio" name="validity_mode" value="keep" <?= $mode === 'keep' ? 'checked' : '' ?>> Mantener vigencia actual<?= $label !== '' ? ' (' . e($label) . ' hora Bogotá)' : '' ?></label>
    <?php endif; ?>
    <label class="checkbox"><input type="radio" name="validity_mode" value="new" <?= $mode === 'new' ? 'checked' : '' ?>> Definir tiempo de publicación</label>
    <div class="grid-2 mt-2">
        <div class="form-group"><label for="validity_amount">Cantidad</label>
            <input id="validity_amount" type="number" name="validity_amount" min="1" value="<?= e((string) $amount) ?>">
        </div>
        <div class="form-group"><label for="validity_unit">Unidad</label>
            <select id="validity_unit" name="validity_unit">
                <option value="hours" <?= $unit === 'hours' ? 'selected' : '' ?>>Horas</option>
                <option value="days" <?= $unit === 'days' ? 'selected' : '' ?>>Días</option>
                <option value="months" <?= $unit === 'months' ? 'selected' : '' ?>>Meses</option>
            </select>
        </div>
    </div>
</fieldset>
    <?php
}
