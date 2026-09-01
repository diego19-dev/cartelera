<?php

class MetaController extends BaseController {

    public function index(): void {
        $this->ok([
            'program_areas' => PROGRAM_AREAS,
            'content_categories' => CONTENT_CATEGORIES,
            'roles' => [
                'admin' => 'Administrador',
                'program_head' => 'Jefe de Programa',
                'editor' => 'Editor',
            ],
            'app_name' => APP_NAME,
            'app_base' => APP_BASE,
        ]);
    }
}
