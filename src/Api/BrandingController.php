<?php

class BrandingController extends BaseController {

    public function index(): void {
        $config = DisplayConfig::getAllForTv();
        $this->ok(['branding' => $config]);
    }
}
