<?php

class AcademicController extends BaseController {

    public function index(): void {
        $this->requireAuth();
        $items = AcademicContent::all();
        $items = $this->filterByArea($items, 'program_area');

        $search = trim($_GET['search'] ?? '');
        $area = $_GET['area'] ?? '';
        $type = $_GET['type'] ?? '';
        $active = $_GET['active'] ?? '';

        $items = array_values(array_filter($items, function ($item) use ($search, $area, $type, $active) {
            if ($search !== '' && stripos($item['title'], $search) === false) return false;
            if ($area !== '' && ($item['program_area'] ?? '') !== $area) return false;
            if ($type !== '' && ($item['program_type'] ?? '') !== $type) return false;
            if ($active === '1' && !$item['is_active']) return false;
            if ($active === '0' && $item['is_active']) return false;
            return true;
        }));

        $this->ok(['items' => $items]);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $item = AcademicContent::find($id);
        if (!$item) $this->fail('No encontrado', 404);
        if (!$this->canViewItem($item)) $this->fail('Acceso denegado', 403);
        $this->ok(['item' => $item]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->requireCsrf();
        $this->assertMultipartIntact();
        $data = $this->collectData();
        if (!Auth::canEditArea($data['program_area'] ?? null)) {
            $this->fail('Sin permisos para este sector', 403);
        }
        $this->applyMediaAndDuration($data, null);
        $data['created_by'] = Session::userId();
        try {
            $id = AcademicContent::create($data);
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage(), 422);
        }
        Logger::log(Session::userId(), 'create', 'academic_content', $id, ['title' => $data['title']]);
        $this->ok(['item' => AcademicContent::find($id)], 201);
    }

    public function update(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $this->assertMultipartIntact();
        $existing = AcademicContent::find($id);
        if (!$existing) $this->fail('No encontrado', 404);
        if (!Auth::canEditArea($existing['program_area'] ?? null)) {
            $this->fail('Sin permisos', 403);
        }
        $data = $this->collectData();
        if (!empty($data['program_area']) && !Auth::canEditArea($data['program_area'])) {
            $this->fail('Sin permisos para el sector', 403);
        }
        $this->applyMediaAndDuration($data, $existing);
        try {
            AcademicContent::update($id, $data);
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage(), 422);
        }
        Logger::log(Session::userId(), 'update', 'academic_content', $id);
        $this->ok(['item' => AcademicContent::find($id)]);
    }

    public function destroy(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $existing = AcademicContent::find($id);
        if (!$existing) $this->fail('No encontrado', 404);
        if (!Auth::canEditArea($existing['program_area'] ?? null)) {
            $this->fail('Sin permisos', 403);
        }
        AcademicContent::delete($id);
        Logger::log(Session::userId(), 'delete', 'academic_content', $id);
        $this->ok(['message' => 'Eliminado']);
    }

    public function toggle(int $id): void {
        $this->requireAuth();
        $this->requireCsrf();
        $existing = AcademicContent::find($id);
        if (!$existing) $this->fail('No encontrado', 404);
        if (!Auth::canEditArea($existing['program_area'] ?? null)) {
            $this->fail('Sin permisos', 403);
        }
        AcademicContent::toggleActive($id);
        Logger::log(Session::userId(), 'toggle', 'academic_content', $id);
        $this->ok(['item' => AcademicContent::find($id)]);
    }

    private function canViewItem(array $item): bool {
        if (Session::hasRole(['admin', 'editor'])) return true;
        if (Session::hasRole('program_head')) {
            $area = Session::userProgramArea();
            return ($item['program_area'] ?? null) === $area || empty($item['program_area']);
        }
        return true;
    }

    private function collectData(): array {
        $body = $this->jsonInput();
        $source = array_merge($_POST, $body);
        $data = Security::sanitizeArray($source);
        $data['is_active'] = filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        return $data;
    }

    /**
     * - video nuevo: duración forzada a la del archivo
     * - video existente sin re-subir: no tocar duration_seconds
     * - imagen/texto: clamp 5…MAX_SLIDE_DURATION_SECONDS
     */
    private function applyMediaAndDuration(array &$data, ?array $existing): void {
        if (!empty($_FILES['media_file']['name'])) {
            $upload = MediaController::upload($_FILES['media_file'], 'academic');
            $data['media_path'] = $upload['filename'];
            $data['media_type'] = $upload['type'];
            if (($upload['type'] ?? '') === 'video') {
                $data['duration_seconds'] = (int) ($upload['duration_seconds']
                    ?? VideoDuration::clampSlideSeconds((int) ($data['duration_seconds'] ?? 15)));
                return;
            }
        }

        $mediaType = $data['media_type'] ?? ($existing['media_type'] ?? 'text');
        if ($mediaType === 'video') {
            unset($data['duration_seconds']);
            return;
        }

        if (isset($data['duration_seconds'])) {
            $data['duration_seconds'] = VideoDuration::clampSlideSeconds((int) $data['duration_seconds']);
        }
    }
}
