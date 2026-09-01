<?php
require_once __DIR__ . '/../src/Core/bootstrap.php';
require_once BASE_PATH . '/src/Views/helpers.php';

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
