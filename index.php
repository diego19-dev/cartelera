<?php
/**
 * Cartelera pública GET /tv/ — inyecta window.__CSET_BOOT__ (mismo JSON que
 * /tv/api/tv) y embebe src/cartelera.html (player ES5, Chromium 53).
 * Apache Alias /tv → raíz del repo.
 */
require_once __DIR__ . '/src/bootstrap.php';

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$payload = null;
try {
    // Reutilizar TvController sin enviar headers JSON todavía
    ob_start();
    $controller = new TvController();
    // Capturar salida JSON del método index si imprime; si no, construir manualmente
    if (method_exists($controller, 'feed')) {
        $payload = $controller->feed();
    }
    $buffered = ob_get_clean();
    if ($payload === null && $buffered) {
        $decoded = json_decode($buffered, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }
} catch (Throwable $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    $payload = null;
}

if ($payload === null) {
    // Fallback: pedir feed vía include de la respuesta tipada
    try {
        $config = DisplayConfig::getAllForTv();
        $payload = [
            'server_ts' => time(),
            'server_iso' => date('c'),
            'version' => '2.0.0',
            'config' => $config,
            'scenarios' => [
                'welcome' => [DisplayConfig::getWelcome()],
                'academic' => AcademicContent::all(true),
                'center' => CenterContent::all(true),
                'general' => GeneralContent::all(true),
                'gallery' => GalleryContent::all(true),
            ],
        ];
    } catch (Throwable $e) {
        $payload = ['error' => 'No se pudo cargar la cartelera', 'server_ts' => time()];
    }
}

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
if ($json === false) {
    $json = '{}';
}

$signageFile = __DIR__ . '/src/cartelera.html';
$html = is_file($signageFile) ? file_get_contents($signageFile) : '';
if ($html === false || $html === '') {
    http_response_code(500);
    echo 'Cartelera no disponible';
    exit;
}

$bootstrap = '<script>window.__CSET_BOOT__=' . $json . ';</script>';
if (stripos($html, '</head>') !== false) {
    $html = preg_replace('/<\/head>/i', $bootstrap . '</head>', $html, 1);
} else {
    $html = $bootstrap . $html;
}

header('Content-Type: text/html; charset=UTF-8');
echo $html;
