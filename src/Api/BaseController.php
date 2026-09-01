<?php

abstract class BaseController {

    protected function ok($data = [], int $status = 200): void {
        Security::jsonResponse(array_merge(['ok' => true], is_array($data) ? $data : ['data' => $data]), $status);
    }

    protected function fail(string $error, int $status = 400): void {
        Security::jsonResponse(['ok' => false, 'error' => $error], $status);
    }

    protected function requireAuth(): void {
        Auth::requireAuthApi();
    }

    protected function requireAdmin(): void {
        Auth::requireRoleApi('admin');
    }

    protected function requireCsrf(): void {
        Security::verifyCsrfRequest();
    }

    /**
     * Si el body multipart supera post_max_size, PHP vacía $_POST y $_FILES.
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

    protected function mediaUrl(?string $path, string $category): ?string {
        if (empty($path)) return null;
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        // Ruta relativa a la raíz (sin esquema/host): el mismo JSON lo consumen
        // tanto la cartelera legacy (HTTP, en /) como /cset/tv/ (HTTPS) — un
        // esquema fijo rompe uno de los dos. Ver MediaController::publicUrl()
        // y bug del logo, 2026-07-27.
        return APP_BASE . '/assets/uploads/' . $category . '/' . ltrim($path, '/');
    }
}
