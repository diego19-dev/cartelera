<?php
/**
 * Bootstrap - Cargador central del sistema
 * Punto de entrada único para todos los requests
 */

// Manejador global de errores fatales para evitar pantallas en blanco
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    if (!class_exists('Logger', false)) {
        error_log("[CSET] $message in $file:$line");
        return;
    }
    Logger::error("PHP Error: $message in $file:$line");
});

set_exception_handler(function ($e) {
    $msg = $e->getMessage();
    $file = $e->getFile();
    $line = $e->getLine();
    error_log("[CSET Exception] $msg in $file:$line");

    if (class_exists('Logger', false)) {
        Logger::error("Uncaught: $msg in $file:$line");
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<h1>Error interno del servidor</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>';
    } else {
        echo <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title>
<style>body{font-family:sans-serif;background:#0a0e27;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center}
.box{background:#1e293b;padding:40px;border-radius:12px;max-width:500px}
h1{color:#fbbf24;font-size:48px;margin:0}p{color:#cbd5e1}</style></head>
<body><div class="box"><h1>500</h1><p>Error interno del servidor.<br>Contacte al administrador.</p></div></body></html>
HTML;
    }
    exit(1);
});

// Cargar variables de entorno (.env en la raíz del proyecto, no versionado) antes que Config.php
require_once __DIR__ . '/EnvLoader.php';
EnvLoader::load(dirname(__DIR__, 2) . '/.env');

// Configuración
require_once __DIR__ . '/Config.php';

// Núcleo (mismo directorio)
require_once __DIR__ . '/VideoDuration.php';
require_once __DIR__ . '/VideoTranscoder.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Auth.php';

// Autoload para Models, Controllers y Api
spl_autoload_register(function ($class) {
    $base = BASE_PATH . '/src/';
    $paths = [
        $base . 'Models/' . $class . '.php',
        $base . 'Controllers/' . $class . '.php',
        $base . 'Api/' . $class . '.php',
        $base . 'Core/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Sesión segura
Session::start();

require_once BASE_PATH . '/src/Views/helpers.php';

// Headers de seguridad (API y páginas PHP). Estáticos también vía public/.htaccess.
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'unsafe-inline'; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "font-src 'self' https://fonts.gstatic.com; " .
        "img-src 'self' data: blob:; " .
        "connect-src 'self'; " .
        "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'"
    );
    if (APP_ENV === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
