<?php
/**
 * Entrada del monorepo CSET.
 * La app se sirve solo en /tv/ (Alias Apache → public/).
 * Bookmarks /cset/* → /tv/* (301).
 */
$uri = $_SERVER['REQUEST_URI'] ?? '/cset/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/cset/';
$query = parse_url($uri, PHP_URL_QUERY);

$target = preg_replace('#^/cset(?=/|$)#', '/tv', $path) ?: '/tv/';
if ($target === '/tv') {
    $target = '/tv/';
}
if ($query) {
    $target .= '?' . $query;
}

header('Location: ' . $target, true, 301);
exit;
