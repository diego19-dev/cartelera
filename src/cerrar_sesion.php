<?php
/**
 * Cierre de sesión del panel administrativo (/tv/logout.php).
 *
 * Invalida la sesión actual en Session::destroy() vía Auth::logout(),
 * verifica token CSRF si la petición es POST y redirige al formulario
 * de inicio de sesión (/tv/login.php).
 */
require_once __DIR__ . '/bootstrap.php';
require_once BASE_PATH . '/src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? '';
    if (!Security::verifyCsrf(is_string($token) ? $token : null)) {
        http_response_code(403);
        echo 'CSRF inválido';
        exit;
    }
}

Auth::logout();
redirect(APP_BASE . '/login.php');
