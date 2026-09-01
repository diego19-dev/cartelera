<?php

class CenterController extends BaseController {

    public function index(): void {
        $this->requireAuth();
        $items = CenterContent::all();
        $items = $this->filterByArea($items, 'sector');

        $sector = $_GET['sector'] ?? '';
        $active = $_GET['active'] ?? '';
        $search = trim($_GET['search'] ?? '');

        $items = array_values(array_filter($items, function ($item) use ($sector, $active, $search) {
            if ($search !== '' && stripos($item['title'], $search) === false) return false;
            if ($sector !== '' && $item['sector'] !== $sector) return false;
            if ($active === '1' && !$item['is_active']) return false;
            if ($active === '0' && $item['is_active']) return false;
            return true;
        }));

        $this->ok(['items' => $items]);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $item = CenterContent::find($id);
        if (!$item) $this->fail('No encontrado', 404);
        if (!Auth::canEditArea($item['sector'] ?? null) && !Session::hasRole(['admin', 'editor'])) {
            if (Session::hasRole('program_head') && Session::userProgramArea() !== $item['sector']) {
                $this->fail('Acceso denegado', 403);
            }
        }
        $this->ok(['item' => $item]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->requireCsrf();
        $data = $this->collectData();
        if (empty($data['sector'])) $this->fail('Sector requerido', 422);
        if (!Auth::canEditArea($data['sector'])) $this->fail('Sin permisos', 403);
        $this->handleUpload($data);
        $data['created_by'] = Session::userId();
        $id = CenterContent::create($data);
        Logger::log(Session::userId(), 'create', 'center_content', $id);
        $this->ok(['item' => CenterContent::find($id)], 201);
    }

    public function update(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $existing = CenterContent::find($id);
        if (!$existing) $this->fail('No encontrado', 404);
        if (!Auth::canEditArea($existing['sector'])) $this->fail('Sin permisos', 403);
        $data = $this->collectData();
        if (!empty($data['sector']) && !Auth::canEditArea($data['sector'])) {
            $this->fail('Sin permisos', 403);
        }
        $this->handleUpload($data);
        CenterContent::update($id, $data);
        Logger::log(Session::userId(), 'update', 'center_content', $id);
        $this->ok(['item' => CenterContent::find($id)]);
    }

    public function destroy(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $existing = CenterContent::find($id);
        if (!$existing) $this->fail('No encontrado', 404);
        if (!Auth::canEditArea($existing['sector'])) $this->fail('Sin permisos', 403);
        CenterContent::delete($id);
        Logger::log(Session::userId(), 'delete', 'center_content', $id);
        $this->ok(['message' => 'Eliminado']);
    }

    public function toggle(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $existing = CenterContent::find($id);
        if (!$existing) $this->fail('No encontrado', 404);
        if (!Auth::canEditArea($existing['sector'])) $this->fail('Sin permisos', 403);
        CenterContent::toggleActive($id);
        Logger::log(Session::userId(), 'toggle', 'center_content', $id);
        $this->ok(['item' => CenterContent::find($id)]);
    }

    private function collectData(): array {
        $body = $this->jsonInput();
        $data = Security::sanitizeArray(array_merge($_POST, $body));
        $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        return $data;
    }

    private function handleUpload(array &$data): void {
        if (!empty($_FILES['media_file']['name'])) {
            $upload = MediaController::upload($_FILES['media_file'], 'center');
            $data['media_path'] = $upload['filename'];
            $data['media_type'] = $upload['type'];
        }
    }
}
