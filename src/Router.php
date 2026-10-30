<?php
/**
 * Router HTTP mínimo (método + path con {params}).
 *
 * El panel y la API REST no lo usan: el panel son PHP sueltos y la API pasa
 * por ApiDispatcher. Queda por si se añaden rutas extra.
 */
class Router {
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * @param callable $handler fn(array $params): void
     * @param array<int, callable> $middleware Se ejecutan antes del handler
     */
    public function add(string $method, string $path, callable $handler, array $middleware = []): void {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $this->compile($path),
            'path'       => $path,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    private function compile(string $path): string {
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $this->basePath . $regex . '$#';
    }

    /** Encaja REQUEST_URI; 404 JSON si el path contiene /api/, si no HTML. */
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                foreach ($route['middleware'] as $mw) {
                    $mw();
                }
                call_user_func($route['handler'], $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        if (str_contains($uri, '/api/')) {
            Security::jsonResponse(['ok' => false, 'error' => 'Not Found'], 404);
        }
        echo '<h1>404 - Página no encontrada</h1>';
    }
}