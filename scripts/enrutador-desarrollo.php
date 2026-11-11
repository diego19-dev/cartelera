<?php
/**
 * Enrutador del servidor embebido PHP (php -S).
 * Imita Alias /tv → raíz y las reglas de .htaccess.
 * Uso: bash scripts/desarrollo.sh
 */
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$query = $_SERVER['QUERY_STRING'] ?? '';
$root = dirname(__DIR__);

$redirect = static function (string $target, int $code = 301) use ($query): bool {
    if ($query !== '') {
        $target .= (str_contains($target, '?') ? '&' : '?') . $query;
    }
    header('Location: ' . $target, true, $code);
    return true;
};

if (preg_match('#^/cset(/.*)?$#', $uri, $m) === 1) {
    $rest = $m[1] ?? '/';
    if ($rest === '') {
        $rest = '/';
    }
    return $redirect('/tv' . $rest);
}

if ($uri === '/tv') {
    return $redirect('/tv/');
}
if ($uri === '/tv/login' || $uri === '/tv/login/') {
    return $redirect('/tv/login.php');
}
if ($uri === '/tv/admin/dashboard' || $uri === '/tv/admin/dashboard/') {
    return $redirect('/tv/admin/dashboard.php');
}
if (preg_match('#^/tv/tv-modern(/.*)?$#', $uri) === 1) {
    return $redirect('/tv/');
}

if (preg_match('#^/tv/assets/css/(.*)$#', $uri, $m) === 1) {
    return $redirect('/tv/css/' . $m[1]);
}
if (preg_match('#^/tv/assets/js/(.*)$#', $uri, $m) === 1) {
    return $redirect('/tv/js/' . $m[1]);
}
if (preg_match('#^/tv/assets/uploads/(.*)$#', $uri, $m) === 1) {
    return $redirect('/tv/img/uploads/' . $m[1]);
}

if (preg_match('#^/tv/(src|lib|logs|cambios|scripts|docs)/#', $uri) === 1) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Forbidden';
    return true;
}

if (preg_match('#^/tv/img/uploads/.*\.(php|phtml|php3|php4|php5|phar)$#i', $uri) === 1) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Forbidden';
    return true;
}

$map = [
    '/tv/login.php' => '/src/iniciar_sesion.php',
    '/tv/logout.php' => '/src/cerrar_sesion.php',
    '/tv/admin/dashboard.php' => '/src/panel_inicio.php',
    '/tv/admin/academic/' => '/src/panel_academico.php',
    '/tv/admin/academic/index.php' => '/src/panel_academico.php',
    '/tv/admin/academic/form.php' => '/src/panel_academico_formulario.php',
    '/tv/admin/center/' => '/src/panel_centro.php',
    '/tv/admin/center/index.php' => '/src/panel_centro.php',
    '/tv/admin/center/form.php' => '/src/panel_centro_formulario.php',
    '/tv/admin/gallery/' => '/src/panel_galeria.php',
    '/tv/admin/gallery/index.php' => '/src/panel_galeria.php',
    '/tv/admin/gallery/form.php' => '/src/panel_galeria_formulario.php',
    '/tv/admin/general/' => '/src/panel_general.php',
    '/tv/admin/general/index.php' => '/src/panel_general.php',
    '/tv/admin/general/form.php' => '/src/panel_general_formulario.php',
    '/tv/admin/users/' => '/src/panel_usuarios.php',
    '/tv/admin/users/index.php' => '/src/panel_usuarios.php',
    '/tv/admin/users/form.php' => '/src/panel_usuarios_formulario.php',
    '/tv/admin/config/' => '/src/panel_configuracion.php',
    '/tv/admin/config/index.php' => '/src/panel_configuracion.php',
    '/tv/admin/welcome/' => '/src/panel_bienvenida.php',
    '/tv/admin/welcome/index.php' => '/src/panel_bienvenida.php',
    '/tv/admin/media/' => '/src/panel_medios.php',
    '/tv/admin/media/index.php' => '/src/panel_medios.php',
];

if (isset($map[$uri])) {
    require $root . $map[$uri];
    return true;
}

if (preg_match('#^/tv/api(?:/(.*))?$#', $uri, $m) === 1) {
    $_SERVER['PATH_INFO'] = '/' . ($m[1] ?? '');
    require $root . '/src/api.php';
    return true;
}

if ($uri === '/tv/') {
    require $root . '/index.php';
    return true;
}

if (!str_starts_with($uri, '/tv/')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "404 — usa http://127.0.0.1:8080/tv/\n";
    return true;
}

$relative = substr($uri, 3);
$diskPath = $root . $relative;

if (is_file($diskPath) && is_readable($diskPath)) {
    $mime = mime_content_type($diskPath) ?: 'application/octet-stream';
    $lower = strtolower($diskPath);
    if (str_ends_with($lower, '.css')) {
        $mime = 'text/css; charset=UTF-8';
    } elseif (str_ends_with($lower, '.js')) {
        $mime = 'application/javascript; charset=UTF-8';
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($diskPath));
    readfile($diskPath);
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo "404\n";
return true;
