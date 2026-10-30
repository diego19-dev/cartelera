<?php
/**
 * Slide de bienvenida institucional (/tv/api/welcome). Solo admin + CSRF.
 *
 * Textos/colores en display_config (welcome_*). POST/DELETE welcome/background
 * el fondo. HTML limitado a &lt;b&gt; en lema y pie.
 */
class WelcomeController extends BaseController {

    public function index(): void {
        $this->requireAdmin();
        $this->ok(['welcome' => $this->resolvedWelcome()]);
    }

    /** PUT/PATCH claves de DisplayConfig::welcomeEditableKeys(). */
    public function update(): void {
        $this->requireAdmin();
        $this->requireCsrf();
        $body = $this->jsonInput();
        $payload = $body['welcome'] ?? $body;
        if (!is_array($payload)) {
            $this->fail('Plantilla inválida', 422);
        }

        $allowed = DisplayConfig::welcomeEditableKeys();
        $htmlKeys = ['welcome_lema', 'welcome_pie'];
        $colorKeys = [
            'welcome_bg_color',
            'welcome_accent_color',
            'welcome_text_color',
            'welcome_saludo_color',
            'welcome_titulo_color',
            'welcome_centro_color',
            'welcome_regional_color',
            'welcome_lema_color',
            'welcome_hashtag_color',
            'welcome_pie_color',
            'welcome_deco_color',
        ];
        $saved = [];

        foreach ($payload as $key => $value) {
            if (!is_string($key) || is_array($value)) {
                continue;
            }
            if (!str_starts_with($key, 'welcome_')) {
                $key = 'welcome_' . $key;
            }
            if (!in_array($key, $allowed, true)) {
                continue;
            }

            $raw = trim((string) $value);

            if ($key === 'welcome_duration') {
                $n = (int) $raw;
                if ($n < 3) {
                    $n = 3;
                }
                if ($n > 120) {
                    $n = 120;
                }
                $raw = (string) $n;
            } elseif (in_array($key, $colorKeys, true)) {
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $raw)) {
                    $this->fail('Color inválido: ' . $key, 422);
                }
            } elseif (in_array($key, $htmlKeys, true)) {
                $raw = DisplayConfig::sanitizeWelcomeHtml($raw);
            } elseif ($key === 'welcome_titulo') {
                $raw = str_replace(["\r\n", "\r"], "\n", strip_tags($raw));
            } else {
                $raw = strip_tags($raw);
            }

            DisplayConfig::set($key, $raw, Session::userId());
            $saved[] = $key;
        }

        Logger::log(Session::userId(), 'update_welcome', 'display_config', null, ['keys' => $saved]);
        $this->ok(['welcome' => $this->resolvedWelcome()]);
    }

    public function uploadBackground(): void {
        $this->requireAdmin();
        $this->requireCsrf();
        if (empty($_FILES['background']['name'])) {
            $this->fail('No se envió archivo', 422);
        }
        try {
            $upload = MediaController::upload($_FILES['background'], 'welcome');
            $old = DisplayConfig::get('welcome_bg_path');
            if ($old && is_file(UPLOAD_PATH . '/welcome/' . $old) && $old !== $upload['filename']) {
                @unlink(UPLOAD_PATH . '/welcome/' . $old);
            }
            DisplayConfig::set('welcome_bg_path', $upload['filename'], Session::userId());
            DisplayConfig::set('welcome_bg_url', $upload['url'], Session::userId());
            Logger::log(Session::userId(), 'upload_welcome_bg', 'display_config', null);
            $welcome = $this->resolvedWelcome();
            $this->ok(['welcome' => $welcome, 'bg_url' => $welcome['bg_url']]);
        } catch (Exception $e) {
            $this->fail($e->getMessage(), 400);
        }
    }

    public function deleteBackground(): void {
        $this->requireAdmin();
        $this->requireCsrf();
        $path = DisplayConfig::get('welcome_bg_path');
        if ($path) {
            $full = UPLOAD_PATH . '/welcome/' . $path;
            if (is_file($full)) {
                @unlink($full);
            }
            DisplayConfig::set('welcome_bg_path', '', Session::userId());
            DisplayConfig::set('welcome_bg_url', '', Session::userId());
        }
        Logger::log(Session::userId(), 'delete_welcome_bg', 'display_config', null);
        $this->ok(['welcome' => $this->resolvedWelcome(), 'message' => 'Fondo eliminado']);
    }

    private function resolvedWelcome(): array {
        $welcome = DisplayConfig::getWelcome();
        $path = $welcome['bg_path'] ?? '';
        if ($path !== '' && $path !== null) {
            $welcome['bg_url'] = $this->mediaUrl($path, 'welcome') ?? '';
        } else {
            $welcome['bg_url'] = '';
        }
        return $welcome;
    }
}
