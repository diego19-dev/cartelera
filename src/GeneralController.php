<?php
/**
 * CRUD REST de información general (/tv/api/general).
 *
 * GET …/indicators lista indicadores externos; el resto es CRUD de
 * general_content. Roles de contenido (admin/editor).
 */
class GeneralController extends BaseController {

    public function index(): void {
        $this->requireAuth();
        $this->requireContentRole();
        $cat = $_GET['category'] ?? $_GET['cat'] ?? '';
        $items = GeneralContent::all();
        if ($cat !== '') {
            $items = array_values(array_filter($items, fn($i) => $i['category'] === $cat));
        }
        $search = trim($_GET['search'] ?? '');
        if ($search !== '') {
            $items = array_values(array_filter($items, fn($i) => stripos($i['title'], $search) !== false));
        }
        $this->ok(['items' => $items]);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $this->requireContentRole();
        $item = GeneralContent::find($id);
        if (!$item) $this->fail('No encontrado', 404);
        $this->ok(['item' => $item]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->requireCsrf();
        $this->requireContentRole();
        $data = $this->collectData();
        if (empty($data['category'])) {
            $this->fail('Categoría requerida', 422);
        }
        $this->handleUpload($data);
        $data['created_by'] = Session::userId();
        $id = GeneralContent::create($data);
        Logger::log(Session::userId(), 'create', 'general_content', $id);
        $this->ok(['item' => GeneralContent::find($id)], 201);
    }

    public function update(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $this->requireContentRole();
        if (!GeneralContent::find($id)) $this->fail('No encontrado', 404);
        $data = $this->collectData();
        $this->handleUpload($data);
        GeneralContent::update($id, $data);
        Logger::log(Session::userId(), 'update', 'general_content', $id);
        $this->ok(['item' => GeneralContent::find($id)]);
    }

    public function destroy(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $this->requireContentRole();
        if (!GeneralContent::find($id)) $this->fail('No encontrado', 404);
        GeneralContent::delete($id);
        Logger::log(Session::userId(), 'delete', 'general_content', $id);
        $this->ok(['message' => 'Eliminado']);
    }

    public function toggle(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $this->requireContentRole();
        if (!GeneralContent::find($id)) $this->fail('No encontrado', 404);
        GeneralContent::toggleActive($id);
        Logger::log(Session::userId(), 'toggle', 'general_content', $id);
        $this->ok(['item' => GeneralContent::find($id)]);
    }

    /** GET/POST indicadores externos (filas is_external de general_content). */
    public function indicators(): void {
        $this->requireAuth();
        $this->requireContentRole();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $rows = Database::getInstance()->fetchAll(
                "SELECT id, category, title, description, external_data, source_url
                 FROM general_content
                 WHERE is_external = TRUE
                 ORDER BY category, title"
            );
            foreach ($rows as &$r) {
                if (!empty($r['external_data'])) {
                    $r['external_data'] = json_decode($r['external_data'], true);
                }
            }
            $this->ok(['indicators' => $rows]);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
            $body = $this->jsonInput();
            $indicators = $body['indicators'] ?? [];
            $updated = 0;
            foreach ($indicators as $data) {
                $category = $data['category'] ?? null;
                $title = $data['title'] ?? null;
                $payload = $data['data'] ?? $data['external_data'] ?? [];
                $source = $data['source_url'] ?? $data['source'] ?? null;
                if (is_array($payload)) {
                    $payload = array_filter($payload, fn($v) => $v !== '' && $v !== null);
                }
                if ($category && $title && !empty($payload)) {
                    GeneralContent::upsertExternal($category, $title, $payload, $source);
                    $updated++;
                }
            }
            $this->ok(['updated' => $updated]);
        }
    }

    /**
     * docs/QA-CRUD-CHECKLIST.md: General CRUD es solo admin.
     * Ver AUDITORIA_SEGURIDAD_CSET.md hallazgo C6.
     */
    private function requireContentRole(): void {
        if (!Session::hasRole('admin')) {
            $this->fail('Sin permisos', 403);
        }
    }

    private function collectData(): array {
        $body = $this->jsonInput();
        $data = Security::sanitizeArray(array_merge($_POST, $body));
        $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $data['is_external'] = filter_var($data['is_external'] ?? false, FILTER_VALIDATE_BOOLEAN);
        return $data;
    }

    private function handleUpload(array &$data): void {
        $fileKey = !empty($_FILES['image_file']['name']) ? 'image_file' : 'media_file';
        if (!empty($_FILES[$fileKey]['name'])) {
            $upload = MediaController::upload($_FILES[$fileKey], 'general');
            $data['image_path'] = $upload['filename'];
        }
    }
}
