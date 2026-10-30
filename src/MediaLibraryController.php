<?php
/**
 * Biblioteca de archivos (/tv/api/media). Solo admin + CSRF.
 *
 * Listado de media_files, upload (MediaController) y delete.
 */
class MediaLibraryController extends BaseController {

    public function index(): void {
        $this->requireAdmin();
        $limit = min(500, max(1, (int) ($_GET['limit'] ?? 200)));
        $items = MediaFile::all($limit);
        $category = $_GET['category'] ?? '';
        $q = trim($_GET['q'] ?? '');

        $items = array_values(array_filter($items, function ($m) use ($category, $q) {
            if ($category !== '' && $m['category'] !== $category) return false;
            if ($q !== '' && stripos($m['original_name'] . $m['filename'], $q) === false) return false;
            return true;
        }));

        foreach ($items as &$m) {
            $m['url'] = $this->mediaUrl($m['filename'], $m['category']);
        }
        $this->ok(['items' => $items]);
    }

    /** POST multipart campo file + category. */
    public function store(): void {
        $this->requireAdmin();
        $this->requireCsrf();
        $category = $_POST['category'] ?? 'general';
        if (!in_array($category, ['academic', 'center', 'general', 'gallery'], true)) {
            $category = 'general';
        }
        if (empty($_FILES['file']['name'])) {
            $this->fail('Seleccione un archivo', 422);
        }
        try {
            $r = MediaController::upload($_FILES['file'], $category);
            Logger::log(Session::userId(), 'upload', 'media_files', $r['id'], [
                'filename' => $r['filename'], 'category' => $category,
            ]);
            $this->ok(['item' => array_merge($r, ['url' => $r['url']])], 201);
        } catch (Exception $e) {
            $this->fail($e->getMessage(), 400);
        }
    }

    public function destroy(int $id): void {
        $this->requireAdmin();
        $this->requireCsrf();
        try {
            MediaController::delete($id);
            Logger::log(Session::userId(), 'delete_media', 'media_files', $id);
            $this->ok(['message' => 'Eliminado']);
        } catch (Exception $e) {
            $this->fail($e->getMessage(), 403);
        }
    }
}
