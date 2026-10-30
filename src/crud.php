<?php
/**
 * Utilidades compartidas del CRUD PHP: CSRF POST, uploads, vigencia, duración.
 *
 * Lo incluyen las páginas de public/admin/*. require_post_csrf() termina en 403
 * si el token falla. apply_media_upload() usa MediaController.
 */

/** Verifica POST `_csrf` o 403 + exit. */
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

/**
 * Mueve $_FILES[$field] a UPLOAD_PATH/{category}/. Null si no hay archivo.
 *
 * @throws RuntimeException
 */
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

/** Clamp de duration_seconds del POST a MIN…MAX_SLIDE_DURATION_SECONDS. */
function duration_seconds_from_post(int $default = 15): int {
    return VideoDuration::clampSlideSeconds((int) ($_POST['duration_seconds'] ?? $default));
}

/**
 * Si el body multipart supera post_max_size, PHP vacía $_POST y $_FILES.
 */
function assert_multipart_intact(): void {
    $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
    if (stripos($contentType, 'multipart/form-data') === false) {
        return;
    }
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength <= 0) {
        return;
    }
    if (!empty($_POST) || !empty($_FILES)) {
        return;
    }
    $maxPost = ini_get('post_max_size') ?: '?';
    throw new RuntimeException(
        "La subida supera el límite del servidor (post_max_size={$maxPost}). Reduzca el tamaño del archivo."
    );
}

/**
 * Asigna media_path/media_type desde $_FILES; en video fija duration_seconds.
 *
 * @param array<string, mixed> $data
 */
function apply_media_upload(array &$data, string $field, string $category, string $pathKey = 'media_path'): void {
    $file = $_FILES[$field] ?? null;
    if (!is_array($file)) {
        return;
    }
    $name = trim((string) ($file['name'] ?? ''));
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($name === '' && $error === UPLOAD_ERR_NO_FILE) {
        return;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(MediaController::uploadErrorMessage($error));
    }
    $upload = MediaController::upload($file, $category);
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

/** URL pública APP_BASE/assets/uploads/{category}/{filename}, o null. */
function cset_upload_url(string $category, ?string $filename): ?string {
    $filename = $filename !== null ? trim($filename) : '';
    if ($filename === '') {
        return null;
    }
    return MediaController::publicUrl($category, $filename);
}

/**
 * Clasifica un archivo de upload por media_type o extensión.
 *
 * @return 'image'|'video'|'pdf'|'other'
 */
function cset_media_kind(?string $filename, ?string $mediaType = null): string {
    $type = strtolower(trim((string) $mediaType));
    if ($type === 'image' || $type === 'video') {
        return $type;
    }
    if ($type === 'pdf' || $type === 'doc') {
        return 'pdf';
    }
    $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
    if ($ext !== '' && in_array($ext, ALLOWED_IMAGE_TYPES, true)) {
        return 'image';
    }
    if ($ext !== '' && in_array($ext, ALLOWED_VIDEO_TYPES, true)) {
        return 'video';
    }
    if ($ext !== '' && in_array($ext, ALLOWED_DOC_TYPES, true)) {
        return 'pdf';
    }
    return 'other';
}

/**
 * Miniatura / enlace de un archivo en uploads/{category}/.
 *
 * @param array{class?: string, empty?: string, show_empty?: bool} $opts
 */
function render_media_preview(?string $filename, string $category, ?string $mediaType = null, array $opts = []): void {
    $filename = $filename !== null ? trim($filename) : '';
    $class = trim((string) ($opts['class'] ?? 'thumb'));
    $showEmpty = !empty($opts['show_empty']);
    $emptyLabel = (string) ($opts['empty'] ?? '—');

    if ($filename === '') {
        if ($showEmpty) {
            echo '<span class="muted">' . e($emptyLabel) . '</span>';
        }
        return;
    }

    $url = cset_upload_url($category, $filename);
    if ($url === null) {
        if ($showEmpty) {
            echo '<span class="muted">' . e($emptyLabel) . '</span>';
        }
        return;
    }

    $kind = cset_media_kind($filename, $mediaType);
    $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';

    if ($kind === 'image') {
        echo '<img' . $classAttr . ' src="' . e($url) . '" alt="' . e($filename) . '" loading="lazy">';
        return;
    }
    if ($kind === 'video') {
        echo '<video' . $classAttr . ' src="' . e($url) . '" muted preload="metadata" playsinline></video>';
        return;
    }
    if ($kind === 'pdf') {
        echo '<a class="media-doc-link" href="' . e($url) . '" target="_blank" rel="noopener">PDF</a>';
        return;
    }
    echo '<a class="media-doc-link" href="' . e($url) . '" target="_blank" rel="noopener">' . e($filename) . '</a>';
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

/** Badge Activo / Inactivo / Vencido (activo pero fuera del feed de TV). */
function render_tv_status_badge(array $row): void {
    $active = !empty($row['is_active']);
    $expired = ContentValidity::isExpired(isset($row['show_until']) ? (string) $row['show_until'] : null);
    if (!$active) {
        echo '<span class="badge badge-off">Inactivo</span>';
        return;
    }
    if ($expired) {
        echo '<span class="badge badge-warn">Vencido</span>';
        return;
    }
    echo '<span class="badge badge-ok">Activo</span>';
}

/** Radios de vigencia (validity_mode) para ContentValidity::applyFromPost(). */
function render_validity_fieldset(?string $currentUntil): void {
    $hasCurrent = $currentUntil !== null && trim($currentUntil) !== '';
    $expired = ContentValidity::isExpired($currentUntil);
    $posted = (string) ($_POST['validity_mode'] ?? '');
    $mode = $posted !== '' ? $posted : ($hasCurrent && !$expired ? 'keep' : 'none');
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
    <?php if ($expired): ?>
    <div class="alert alert-error">La vigencia venció<?= $label !== '' ? ' el ' . e($label) . ' (hora Bogotá)' : '' ?>. Este contenido no se muestra en la TV hasta que quite el límite o defina un tiempo nuevo.</div>
    <?php endif; ?>
    <label class="checkbox"><input type="radio" name="validity_mode" value="none" <?= $mode === 'none' ? 'checked' : '' ?>> Sin límite de tiempo</label>
    <?php if ($hasCurrent && !$expired): ?>
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
