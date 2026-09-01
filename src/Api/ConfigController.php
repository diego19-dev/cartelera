<?php

class ConfigController extends BaseController {

    public function index(): void {
        $this->requireAdmin();
        $this->ok(['config' => DisplayConfig::all()]);
    }

    public function update(): void {
        $this->requireAdmin();
        $this->requireCsrf();
        $body = $this->jsonInput();
        $configs = $body['config'] ?? $body;
        if (!is_array($configs)) {
            $this->fail('Configuración inválida', 422);
        }
        foreach ($configs as $key => $value) {
            if (!is_string($key)) continue;
            if (is_array($value)) continue;
            DisplayConfig::set($key, trim((string) $value), Session::userId());
        }
        Logger::log(Session::userId(), 'update_config', 'display_config', null, ['keys' => array_keys($configs)]);
        $this->ok(['config' => DisplayConfig::all()]);
    }

    public function uploadLogo(): void {
        $this->requireAdmin();
        $this->requireCsrf();
        if (empty($_FILES['logo']['name'])) {
            $this->fail('No se envió archivo', 422);
        }
        try {
            $upload = MediaController::upload($_FILES['logo'], 'branding');
            $oldLogo = DisplayConfig::get('brand_logo_path');
            if ($oldLogo && file_exists(UPLOAD_PATH . '/branding/' . $oldLogo)) {
                @unlink(UPLOAD_PATH . '/branding/' . $oldLogo);
            }
            DisplayConfig::set('brand_logo_path', $upload['filename'], Session::userId());
            DisplayConfig::set('brand_logo_url', $upload['url'], Session::userId());
            if (!empty($_POST['brand_logo_size'])) {
                DisplayConfig::set('brand_logo_size', $_POST['brand_logo_size'], Session::userId());
            }
            Logger::log(Session::userId(), 'upload_logo', 'display_config', null);
            $this->ok(['logo_url' => $upload['url']]);
        } catch (Exception $e) {
            $this->fail($e->getMessage(), 400);
        }
    }

    public function deleteLogo(): void {
        $this->requireAdmin();
        $this->requireCsrf();
        $logoPath = DisplayConfig::get('brand_logo_path');
        if ($logoPath) {
            $full = UPLOAD_PATH . '/branding/' . $logoPath;
            if (file_exists($full)) @unlink($full);
            DisplayConfig::set('brand_logo_path', '');
            DisplayConfig::set('brand_logo_url', '');
        }
        $this->ok(['message' => 'Logo eliminado']);
    }
}
