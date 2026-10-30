<?php
/**
 * Front controller REST en /tv/api/ (rewrite → src/api.php).
 *
 * CORS + OPTIONS 204, luego ApiDispatcher::dispatch(). El rewrite de
 * .htaccess manda /api/* aquí.
 */
require_once __DIR__ . '/bootstrap.php';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

(new ApiDispatcher())->dispatch();
