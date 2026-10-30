<?php
/**
 * GET /tv/api/meta — catálogos públicos: PROGRAM_AREAS, CONTENT_CATEGORIES, roles.
 */
class MetaController extends BaseController {

    public function index(): void {
        $this->ok([
            'program_areas' => PROGRAM_AREAS,
            'content_categories' => CONTENT_CATEGORIES,
            'roles' => cset_assignable_roles(),
            'app_name' => APP_NAME,
            'app_base' => APP_BASE,
        ]);
    }
}
