<?php
/**
 * Helpers JSON, auth y URLs de media para todos los controladores REST.
 *
 * ok()/fail() llaman Security::jsonResponse (exit). requireAuth/requireAdmin
 * son JSON 401/403. mediaUrl() arma /tv/assets/uploads/{categoría}/{archivo}.
 */
abstract class BaseController {

    /**
     * JSON { ok: true, ...$data }.
     *
     * @param array<string, mixed>|mixed $data
     * @return never
     */
    protected function ok($data = [], int $status = 200): void {
        Security::jsonResponse(array_merge(['ok' => true], is_array($data) ? $data : ['data' => $data]), $status);
    }

    /** JSON { ok: false, error } y termina. @return never */
    protected function fail(string $error, int $status = 400): void {
        Security::jsonResponse(['ok' => false, 'error' => $error], $status);
    }

    protected function requireAuth(): void {
        Auth::requireAuthApi();
    }

    protected function requireAdmin(): void {
        Auth::requireRoleApi('admin');
    }

    /** 403 si $module está en PANEL_DISABLED_MODULES. */
    protected function requirePanelModule(string $module): void {
        if (in_array($module, PANEL_DISABLED_MODULES, true)) {
            $this->fail('Módulo no disponible', 403);
        }
    }

    protected function requireCsrf(): void {
        Security::verifyCsrfRequest();
    }

    /**
     * 413 si el body multipart superó post_max_size (PHP deja $_POST/$_FILES vacíos).
     */
    protected function assertMultipartIntact(): void {
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
        $this->fail(
            "La subida supera el límite del servidor (post_max_size={$maxPost}). Usa un video más liviano o sube el límite PHP.",
            413
        );
    }

    protected function jsonInput(): array {
        return Security::jsonBody();
    }

    /**
     * Filtra items por área de programa para program_head.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    protected function filterByArea(array $items, string $areaField): array {
        if (Session::hasRole(['admin', 'editor'])) {
            return $items;
        }
        if (Session::hasRole('program_head')) {
            $area = Session::userProgramArea();
            return array_values(array_filter($items, fn($i) =>
                ($i[$areaField] ?? null) === $area || ($i[$areaField] ?? null) === null || ($i[$areaField] ?? '') === ''
            ));
        }
        return $items;
    }

    /**
     * URL pública del archivo bajo assets/uploads/{category}/, o null.
     * Paths http(s) se devuelven tal cual.
     */
    protected function mediaUrl(?string $path, string $category): ?string {
        if (empty($path)) return null;
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        // Ruta relativa a la raíz (sin esquema/host): el mismo JSON lo consumen
        // tanto la cartelera legacy (HTTP, en /) como /cset/tv/ (HTTPS) — un
        // esquema fijo rompe uno de los dos. Ver MediaController::publicUrl()
        // y bug del logo, 2026-07-27.
        return APP_BASE . '/img/uploads/' . $category . '/' . ltrim($path, '/');
    }
}
