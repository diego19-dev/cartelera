<?php
/**
 * CRUD REST de galería (/tv/api/gallery). Solo imágenes.
 *
 * ApiDispatcher responde 403 si gallery está en PANEL_DISABLED_MODULES.
 */
class GalleryController extends BaseController {

    public function index(): void {
        $this->requireAuth();
        $this->requireContentRole();
        $items = GalleryContent::all();

        $search = trim($_GET['search'] ?? '');
        $active = $_GET['active'] ?? '';

        $items = array_values(array_filter($items, function ($item) use ($search, $active) {
            if ($search !== '') {
                $hay = (string) ($item['image_path'] ?? '');
                if (stripos($hay, $search) === false) return false;
            }
            if ($active === '1' && !$item['is_active']) return false;
            if ($active === '0' && $item['is_active']) return false;
            return true;
        }));

        $this->ok(['items' => $items]);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $this->requireContentRole();
        $item = GalleryContent::find($id);
        if (!$item) $this->fail('No encontrado', 404);
        $this->ok(['item' => $item]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->requireCsrf();
        $this->requireContentRole();
        $data = $this->collectData();
        $this->handleUpload($data, true);
        $data['created_by'] = Session::userId();
        $id = GalleryContent::create($data);
        Logger::log(Session::userId(), 'create', 'gallery_content', $id, [
            'image_path' => $data['image_path'] ?? null,
        ]);
        $this->ok(['item' => GalleryContent::find($id)], 201);
    }

    public function update(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $this->requireContentRole();
        $existing = GalleryContent::find($id);
        if (!$existing) $this->fail('No encontrado', 404);
        $data = $this->collectData();
        $this->handleUpload($data, false);
        if (empty($data['image_path']) && empty($existing['image_path'])) {
            $this->fail('Imagen requerida', 422);
        }
        GalleryContent::update($id, $data);
        Logger::log(Session::userId(), 'update', 'gallery_content', $id);
        $this->ok(['item' => GalleryContent::find($id)]);
    }

    public function destroy(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $this->requireContentRole();
        $existing = GalleryContent::find($id);
        if (!$existing) $this->fail('No encontrado', 404);
        GalleryContent::delete($id);
        Logger::log(Session::userId(), 'delete', 'gallery_content', $id);
        $this->ok(['message' => 'Eliminado']);
    }

    public function toggle(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $this->requireContentRole();
        $existing = GalleryContent::find($id);
        if (!$existing) $this->fail('No encontrado', 404);
        GalleryContent::toggleActive($id);
        Logger::log(Session::userId(), 'toggle', 'gallery_content', $id);
        $this->ok(['item' => GalleryContent::find($id)]);
    }

    private function requireContentRole(): void {
        if (!Session::hasRole(['admin', 'program_head', 'editor'])) {
            $this->fail('Sin permisos', 403);
        }
    }

    private function collectData(): array {
        $body = $this->jsonInput();
        $data = Security::sanitizeArray(array_merge($_POST, $body));
        $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        // Solo campos editables del escenario
        $allowed = ['duration_seconds', 'is_active', 'show_from', 'show_until', 'image_path'];
        return array_intersect_key($data, array_flip($allowed));
    }

    private function handleUpload(array &$data, bool $required): void {
        $fileKey = !empty($_FILES['image_file']['name']) ? 'image_file' : 'media_file';
        if (empty($_FILES[$fileKey]['name'])) {
            if ($required) {
                $this->fail('Imagen requerida', 422);
            }
            return;
        }
        try {
            $upload = MediaController::upload($_FILES[$fileKey], 'gallery');
        } catch (Exception $e) {
            $this->fail($e->getMessage(), 422);
            return;
        }
        if (($upload['type'] ?? '') !== 'image') {
            $this->fail('Solo se permiten imágenes', 422);
        }
        $data['image_path'] = $upload['filename'];
    }
}
