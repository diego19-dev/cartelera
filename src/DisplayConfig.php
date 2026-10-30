<?php
/**
 * Clave/valor de la cartelera (tabla display_config): duraciones, branding, bienvenida.
 *
 * Lo usan admin/config, admin/welcome, TvController y BrandingController.
 * getAllForTv() es el objeto `config` del JSON /api/tv. getWelcome() arma el
 * slide institucional (claves welcome_*).
 */
class DisplayConfig {

    private static array $cache = [];

    /**
     * Valor de una clave, con caché de request.
     *
     * @return string|null
     */
    public static function get(string $key, ?string $default = null): ?string {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        $row = Database::getInstance()->fetch(
            'SELECT config_value FROM display_config WHERE config_key = ?', [$key]
        );
        $value = $row['config_value'] ?? $default;
        self::$cache[$key] = $value;
        return $value;
    }

    /**
     * Upsert de una clave. Invalida/actualiza la caché de request.
     *
     * @param int|null $userId updated_by
     */
    public static function set(string $key, string $value, ?int $userId = null): bool {
        $exists = Database::getInstance()->fetch(
            'SELECT id FROM display_config WHERE config_key = ?', [$key]
        );
        if ($exists) {
            Database::getInstance()->update('display_config',
                ['config_value' => $value, 'updated_at' => date('c'), 'updated_by' => $userId],
                'id = :id', ['id' => $exists['id']]);
        } else {
            Database::getInstance()->insert('display_config', [
                'config_key'   => $key,
                'config_value' => $value,
                'updated_at'   => date('c'),
                'updated_by'   => $userId,
            ]);
        }
        self::$cache[$key] = $value;
        return true;
    }

    /**
     * Todas las filas indexadas por config_key (panel de configuración).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array {
        $rows = Database::getInstance()->fetchAll('SELECT * FROM display_config ORDER BY config_key');
        $result = [];
        foreach ($rows as $row) {
            $result[$row['config_key']] = $row;
        }
        return $result;
    }

    /**
     * Config tipada para el player: rotación, poll, scenario_order, colores, logo.
     *
     * @return array<string, mixed>
     */
    public static function getAllForTv(): array {
        return [
            'rotation_seconds'    => (int) self::get('tv_rotation_seconds', '20'),
            'api_poll_seconds'    => (int) self::get('api_poll_seconds', '30'),
            'scenario_order'      => explode(',', self::get('tv_scenario_order', 'academic,center,general')),
            'academic_duration'   => (int) self::get('tv_academic_duration', '15'),
            'center_duration'     => (int) self::get('tv_center_duration', '15'),
            'general_duration'    => (int) self::get('tv_general_duration', '10'),
            'gallery_duration'    => (int) self::get('tv_gallery_duration', '15'),
            'show_clock'          => (bool) self::get('tv_show_clock', '1'),
            'show_weather'        => (bool) self::get('tv_show_weather', '1'),
            'tv_enabled'          => self::get('tv_enabled', '1') === '0' ? '0' : '1',
            'tv_fullscreen'       => self::get('tv_fullscreen', '1') === '0' ? '0' : '1',
            'tv_off_message'      => self::get('tv_off_message', 'Cartelera apagada') ?? 'Cartelera apagada',
            'branding_title'      => self::get('tv_branding_title', 'IES - Cartelera Digital'),
            'branding_subtitle'   => self::get('tv_branding_subtitle', 'Formación para el trabajo'),
            'site_name'           => self::get('site_name', 'IES'),

            // Branding configurable por admin
            'logo_path'           => self::get('brand_logo_path', ''),
            'logo_url'            => self::logoPublicUrl(),
            'primary_color'       => self::get('brand_primary_color', '#0b3d91'),
            'secondary_color'     => self::get('brand_secondary_color', '#1e5ba8'),
            'accent_color'        => self::get('brand_accent_color', '#e6b800'),
            'bg_color'            => self::get('brand_bg_color', '#f1f5f9'),
            'text_color'          => self::get('brand_text_color', '#1e293b'),
            'font_family'         => self::get('brand_font_family', "'Segoe UI', Roboto, sans-serif"),
            'logo_size'           => self::get('brand_logo_size', 'medium'),

            // Modo Oscuro
            'dark_primary_color'  => self::get('brand_dark_primary_color', '#0b3d91'),
            'dark_secondary_color'=> self::get('brand_dark_secondary_color', '#1e5ba8'),
            'dark_accent_color'   => self::get('brand_dark_accent_color', '#e6b800'),
            'dark_bg_color'       => self::get('brand_dark_bg_color', '#0a0e27'),
            'dark_text_color'     => self::get('brand_dark_text_color', '#ffffff'),

            // Colores específicos de cartelera (vacío = fallback a primary en el cliente)
            'tv_color_header'     => self::get('tv_color_header', '') ?? '',
            'tv_color_badge'      => self::get('tv_color_badge', '') ?? '',
            'tv_color_icon'       => self::get('tv_color_icon', '') ?? '',
            'tv_color_live'       => self::get('tv_color_live', '') ?? '',
            'tv_color_progress'   => self::get('tv_color_progress', '') ?? '',
        ];
    }

    /** URL pública del logo; se deriva de brand_logo_path para no quedar pegada a un APP_BASE viejo. */
    public static function logoPublicUrl(): string {
        $path = self::get('brand_logo_path', '') ?? '';
        if ($path !== '') {
            return APP_BASE . '/img/uploads/branding/' . ltrim($path, '/');
        }
        $url = self::get('brand_logo_url', '') ?? '';
        if ($url !== '' && str_starts_with($url, '/cset/')) {
            return APP_BASE . substr($url, strlen('/cset'));
        }
        return $url;
    }

    /** Defaults institucionales del slide de bienvenida. */
    public static function welcomeDefaults(): array {
        return [
            'saludo'        => 'El conocimiento es de todos',
            'titulo'        => "¡Te damos la\nbienvenida!",
            'centro'        => 'Centro de Servicios Empresariales y Turísticos',
            'regional'      => 'Regional Santander',
            'lema'          => '¿Y si <b>este fuera tu momento</b>?',
            'hashtag'       => '#SigueTuMomentoSENA',
            'pie'           => 'Prepárate · Fórmate · <b>Abre nuevas oportunidades</b>',
            'duration'      => 12,
            'bg_color'       => '#eaf4e1',
            'accent_color'   => '#39a900',
            'text_color'     => '#4d4d4d',
            'saludo_color'   => '#39a900',
            'titulo_color'   => '#39a900',
            'centro_color'   => '#4d4d4d',
            'regional_color' => '#6b6b6b',
            'lema_color'     => '#4d4d4d',
            'hashtag_color'  => '#39a900',
            'pie_color'      => '#6b6b6b',
            'deco_color'     => '#a6ce39',
            'bg_path'        => '',
            'bg_url'         => '',
        ];
    }

    /**
     * Textos, colores y fondo del slide de bienvenida (claves welcome_*).
     *
     * @return array<string, mixed>
     */
    public static function getWelcome(): array {
        $d = self::welcomeDefaults();
        $duration = (int) self::get('welcome_duration', (string) $d['duration']);
        if ($duration < 3) {
            $duration = 3;
        }
        if ($duration > 120) {
            $duration = 120;
        }
        $accent = self::get('welcome_accent_color', $d['accent_color']) ?? $d['accent_color'];
        $text = self::get('welcome_text_color', $d['text_color']) ?? $d['text_color'];

        $pick = static function (?string $stored, string $fallback): string {
            if ($stored === null || $stored === '') {
                return $fallback;
            }
            return $stored;
        };

        return [
            'saludo'         => self::get('welcome_saludo', $d['saludo']) ?? $d['saludo'],
            'titulo'         => self::get('welcome_titulo', $d['titulo']) ?? $d['titulo'],
            'centro'         => self::get('welcome_centro', $d['centro']) ?? $d['centro'],
            'regional'       => self::get('welcome_regional', $d['regional']) ?? $d['regional'],
            'lema'           => self::get('welcome_lema', $d['lema']) ?? $d['lema'],
            'hashtag'        => self::get('welcome_hashtag', $d['hashtag']) ?? $d['hashtag'],
            'pie'            => self::get('welcome_pie', $d['pie']) ?? $d['pie'],
            'duration'       => $duration,
            'bg_color'       => self::get('welcome_bg_color', $d['bg_color']) ?? $d['bg_color'],
            'accent_color'   => $accent,
            'text_color'     => $text,
            'saludo_color'   => $pick(self::get('welcome_saludo_color'), $accent),
            'titulo_color'   => $pick(self::get('welcome_titulo_color'), $accent),
            'hashtag_color'  => $pick(self::get('welcome_hashtag_color'), $accent),
            'centro_color'   => $pick(self::get('welcome_centro_color'), $text),
            'regional_color' => $pick(self::get('welcome_regional_color'), $text),
            'lema_color'     => $pick(self::get('welcome_lema_color'), $text),
            'pie_color'      => $pick(self::get('welcome_pie_color'), $text),
            'deco_color'     => $pick(self::get('welcome_deco_color'), $d['deco_color']),
            'bg_path'        => self::get('welcome_bg_path', '') ?? '',
            'bg_url'         => self::get('welcome_bg_url', '') ?? '',
        ];
    }

    /**
     * Claves permitidas en PUT /api/welcome (sin path/url de fondo).
     *
     * @return array<int, string>
     */
    public static function welcomeEditableKeys(): array {
        return [
            'welcome_saludo',
            'welcome_titulo',
            'welcome_centro',
            'welcome_regional',
            'welcome_lema',
            'welcome_hashtag',
            'welcome_pie',
            'welcome_duration',
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
    }

    /**
     * Permite solo etiquetas <b></b>; el resto se escapa.
     */
    public static function sanitizeWelcomeHtml(string $text): string {
        $parts = preg_split('/(<\/?b\s*>)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return htmlspecialchars(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $out = '';
        $open = false;
        foreach ($parts as $part) {
            if (preg_match('/^<b\s*>$/i', $part)) {
                if (!$open) {
                    $out .= '<b>';
                    $open = true;
                }
                continue;
            }
            if (preg_match('/^<\/b\s*>$/i', $part)) {
                if ($open) {
                    $out .= '</b>';
                    $open = false;
                }
                continue;
            }
            $out .= htmlspecialchars($part, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if ($open) {
            $out .= '</b>';
        }
        return trim($out);
    }
}