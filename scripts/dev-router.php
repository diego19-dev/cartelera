<?php
/**
 * Router para el servidor embebido de PHP (php -S).
 * Imita el Alias Apache /tv → public/ y las reglas de public/.htaccess.
 *
 * Uso: bash scripts/dev.sh
 */
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$query = $_SERVER['QUERY_STRING'] ?? '';
$public = dirname(__DIR__) . '/public';

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

if (preg_match('#^/tv/assets/uploads/.*\.(php|phtml|php3|php4|php5|phar)$#i', $uri) === 1) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Forbidden';
    return true;
}

if (preg_match('#^/tv/api(?:/(.*))?$#', $uri, $m) === 1) {
    $_SERVER['PATH_INFO'] = '/' . ($m[1] ?? '');
    require $public . '/api/index.php';
    return true;
}

if ($uri === '/tv/') {
    require $public . '/index.php';
    return true;
}

if (!str_starts_with($uri, '/tv/')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "404 — usa http://127.0.0.1:8080/tv/\n";
    return true;
}

$relative = substr($uri, 3);
$diskPath = $public . $relative;

if (is_dir($diskPath)) {
    $indexPhp = rtrim($diskPath, '/') . '/index.php';
    if (is_file($indexPhp)) {
        require $indexPhp;
        return true;
    }
}

return false;
