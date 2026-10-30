<?php
/**
 * GET /tv/api/branding — subset público de DisplayConfig::getAllForTv().
 * Lo consume el player para logo/colores sin el feed completo.
 */
class BrandingController extends BaseController {

    public function index(): void {
        $config = DisplayConfig::getAllForTv();
        $this->ok(['branding' => $config]);
    }
}
