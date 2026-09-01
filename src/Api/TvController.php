<?php

class TvController extends BaseController {

    public function index(): void {
        try {
            $config = DisplayConfig::getAllForTv();
            $response = [
                'server_ts'  => time(),
                'server_iso' => date('c'),
                'version'    => '2.0.0',
                'config'     => $config,
                'scenarios'  => [],
            ];

            // Slide de bienvenida institucional (siempre al inicio del ciclo).
            $welcome = DisplayConfig::getWelcome();
            $bgPath = $welcome['bg_path'] ?? '';
            $bgUrl = '';
            if ($bgPath !== '' && $bgPath !== null) {
                $bgUrl = $this->mediaUrl($bgPath, 'welcome') ?? '';
            }
            $response['scenarios']['welcome'] = [[
                'id'           => 0,
                'title'        => 'Bienvenida',
                'layout'       => 'bienvenida',
                'duration'     => (int) $welcome['duration'],
                'saludo'       => $welcome['saludo'],
                'titulo'       => $welcome['titulo'],
                'centro'       => $welcome['centro'],
                'regional'     => $welcome['regional'],
                'lema'         => $welcome['lema'],
                'hashtag'      => $welcome['hashtag'],
                'pie'          => $welcome['pie'],
                'bg_color'       => $welcome['bg_color'],
                'accent_color'   => $welcome['accent_color'],
                'text_color'     => $welcome['text_color'],
                'saludo_color'   => $welcome['saludo_color'],
                'titulo_color'   => $welcome['titulo_color'],
                'centro_color'   => $welcome['centro_color'],
                'regional_color' => $welcome['regional_color'],
                'lema_color'     => $welcome['lema_color'],
                'hashtag_color'  => $welcome['hashtag_color'],
                'pie_color'      => $welcome['pie_color'],
                'deco_color'     => $welcome['deco_color'],
                'bg_url'         => $bgUrl !== '' ? $bgUrl : null,
            ]];
            $order = $config['scenario_order'] ?? ['academic', 'center', 'general', 'gallery'];
            if (!is_array($order)) {
                $order = ['academic', 'center', 'general', 'gallery'];
            }
            $order = array_values(array_filter(array_map('strval', $order)));
            if (!in_array('welcome', $order, true)) {
                array_unshift($order, 'welcome');
            }
            $response['config']['scenario_order'] = $order;

            $academic = AcademicContent::getActiveForTv();
            $response['scenarios']['academic'] = array_map(function ($a) use ($config) {
                return [
                    'id'           => (int) $a['id'],
                    'title'        => $a['title'],
                    'description'  => $a['description'],
                    'program_type' => $a['program_type'],
                    'program_area' => $a['program_area'],
                    'media_type'   => $a['media_type'],
                    'media_url'    => $this->mediaUrl($a['media_path'], 'academic'),
                    'thumbnail'    => $this->mediaUrl($a['thumbnail_path'] ?? null, 'academic'),
                    'duration'     => (int) ($a['duration_seconds'] ?? $config['academic_duration']),
                    'updated_at'   => $a['updated_at'] ?? null,
                ];
            }, $academic);

            $center = CenterContent::getActiveForTv();
            $response['scenarios']['center'] = array_map(function ($c) use ($config) {
                return [
                    'id'          => (int) $c['id'],
                    'title'       => $c['title'],
                    'description' => $c['description'],
                    'sector'      => $c['sector'],
                    'media_type'  => $c['media_type'],
                    'media_url'   => $this->mediaUrl($c['media_path'], 'center'),
                    'thumbnail'   => $this->mediaUrl($c['thumbnail_path'] ?? null, 'center'),
                    'duration'    => (int) ($c['duration_seconds'] ?? $config['center_duration']),
                    'updated_at'  => $c['updated_at'] ?? null,
                ];
            }, $center);

            $general = GeneralContent::getActiveForTv();
            $generalSlides = array_map(function ($g) use ($config) {
                $extData = null;
                if (!empty($g['external_data'])) {
                    $extData = json_decode($g['external_data'], true);
                    if (is_array($extData) && count($extData) > 8) {
                        $mainCurrencies = ['COP', 'EUR', 'GBP', 'MXN', 'BRL', 'ARS'];
                        $filtered = [];
                        foreach ($mainCurrencies as $curr) {
                            if (isset($extData[$curr])) {
                                $filtered[$curr] = $extData[$curr];
                            }
                        }
                        if (count($filtered) < 4) {
                            $filtered = array_slice($extData, 0, 6, true);
                        }
                        $extData = $filtered;
                    }
                }
                return [
                    'id'          => (int) $g['id'],
                    'title'       => $g['title'],
                    'description' => $g['description'],
                    'category'    => $g['category'],
                    'icon'        => !empty($g['icon']) ? $g['icon'] : null,
                    'media_type'  => $this->detectMediaType($g['image_path'] ?? null),
                    'media_url'   => $this->mediaUrl($g['image_path'], 'general'),
                    'image_url'   => $this->mediaUrl($g['image_path'], 'general'),
                    'source_url'  => $g['source_url'],
                    'is_external' => (bool) $g['is_external'],
                    'external'    => $extData,
                    'duration'    => (int) ($g['duration_seconds'] ?? $config['general_duration']),
                    'updated_at'  => $g['updated_at'] ?? null,
                ];
            }, $general);
            $response['scenarios']['general'] = $this->groupGeneralIndicators($generalSlides);

            $gallery = GalleryContent::getActiveForTv();
            $response['scenarios']['gallery'] = array_map(function ($g) use ($config) {
                return [
                    'id'         => (int) $g['id'],
                    'title'      => $g['title'] ?? '',
                    'layout'     => 'galeria',
                    'media_type' => 'image',
                    'media_url'  => $this->mediaUrl($g['image_path'] ?? null, 'gallery'),
                    'duration'   => (int) ($g['duration_seconds'] ?? $config['gallery_duration'] ?? 15),
                    'updated_at' => $g['updated_at'] ?? null,
                ];
            }, $gallery);

            $response['last_update'] = $this->getLastUpdate();
            $this->ok($response);
        } catch (Exception $e) {
            $this->fail(APP_DEBUG ? $e->getMessage() : 'Error interno', 500);
        }
    }

    /**
     * Heartbeat ligero.
     * Incluye tv_enabled: last_update tiene resolución de segundos y un
     * apagado/encendido en el mismo segundo no bastaría para despertar la TV.
     */
    public function ping(): void {
        $this->ok([
            'server_ts'   => time(),
            'last_update' => $this->getLastUpdate(),
            'tv_enabled'  => DisplayConfig::get('tv_enabled', '1') === '0' ? '0' : '1',
        ]);
    }

    private function detectMediaType(?string $path): string {
        if (empty($path)) return 'image';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'], true) ? 'video' : 'image';
    }

    /**
     * Agrupa indicadores externos en 2 slides compuestos (3 + 3) para la TV.
     * Matching por título estable del cron; el resto queda como slides individuales.
     *
     * @param list<array<string,mixed>> $slides
     * @return list<array<string,mixed>>
     */
    private function groupGeneralIndicators(array $slides): array {
        $groups = [
            [
                'id'       => -101,
                'title'    => 'Divisas y criptomonedas',
                'matchers' => [
                    'Cotización del Dólar',
                    'TRM Colombia',
                    'Criptomonedas',
                ],
            ],
            [
                'id'       => -102,
                'title'    => 'Commodities y clima',
                'matchers' => [
                    'Café',
                    'Petróleo',
                    'Clima actual',
                ],
            ],
        ];

        $buckets = [];
        foreach ($groups as $gi => $group) {
            $buckets[$gi] = [];
        }
        $remaining = [];

        foreach ($slides as $si => $slide) {
            if (empty($slide['is_external'])) {
                $remaining[] = $slide;
                continue;
            }
            $title = (string) ($slide['title'] ?? '');
            $placed = false;
            foreach ($groups as $gi => $group) {
                foreach ($group['matchers'] as $needle) {
                    if ($title !== '' && stripos($title, $needle) !== false) {
                        if (!isset($buckets[$gi][$needle])) {
                            $buckets[$gi][$needle] = $slide;
                            $placed = true;
                        }
                        break 2;
                    }
                }
            }
            if (!$placed) {
                $remaining[] = $slide;
            }
        }

        $out = [];
        foreach ($groups as $gi => $group) {
            $items = [];
            $duration = 0;
            $updatedAt = null;
            foreach ($group['matchers'] as $needle) {
                if (!isset($buckets[$gi][$needle])) {
                    continue;
                }
                $member = $buckets[$gi][$needle];
                $items[] = [
                    'title'    => $member['title'],
                    'category' => $member['category'] ?? null,
                    'icon'     => $member['icon'] ?? null,
                    'external' => $member['external'] ?? null,
                ];
                $duration += (int) ($member['duration'] ?? 0);
                $u = $member['updated_at'] ?? null;
                if ($u !== null && ($updatedAt === null || strcmp((string) $u, (string) $updatedAt) > 0)) {
                    $updatedAt = $u;
                }
            }
            if (count($items) === 0) {
                continue;
            }
            $out[] = [
                'id'          => (int) $group['id'],
                'title'       => $group['title'],
                'layout'      => 'indicadores',
                'category'    => 'Indicadores',
                'is_external' => true,
                'items'       => $items,
                'duration'    => max(3, $duration),
                'updated_at'  => $updatedAt,
            ];
        }

        foreach ($remaining as $slide) {
            $out[] = $slide;
        }

        return $out;
    }

    private function getLastUpdate(): int {
        $sql = "SELECT GREATEST(
                    COALESCE((SELECT EXTRACT(EPOCH FROM MAX(updated_at)) FROM academic_content), 0),
                    COALESCE((SELECT EXTRACT(EPOCH FROM MAX(updated_at)) FROM center_content), 0),
                    COALESCE((SELECT EXTRACT(EPOCH FROM MAX(updated_at)) FROM general_content), 0),
                    COALESCE((SELECT EXTRACT(EPOCH FROM MAX(updated_at)) FROM gallery_content), 0),
                    COALESCE((SELECT EXTRACT(EPOCH FROM MAX(updated_at)) FROM display_config), 0)
                ) as last_update";
        try {
            $row = Database::getInstance()->fetch($sql);
            return (int) ($row['last_update'] ?? time());
        } catch (Exception $e) {
            return time();
        }
    }
}
