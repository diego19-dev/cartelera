<?php
/**
 * Contenido general: noticias, eventos, indicadores (tabla general_content).
 *
 * Lo usa public/admin/general/ y TvController. Categorías en CONTENT_CATEGORIES.
 * upsertExternal() lo llama cron/refresh_external.php (divisas, clima, etc.).
 * Vigencia vía expire_date (no show_from/show_until).
 */
class GeneralContent {

    /**
     * @param bool $activeOnly Filtra is_active y expire_date
     * @return array<int, array<string, mixed>>
     */
    public static function all(bool $activeOnly = false): array {
        $sql = 'SELECT g.*, u.full_name AS author_name
                FROM general_content g
                LEFT JOIN users u ON u.id = g.created_by';
        if ($activeOnly) {
            $sql .= ' WHERE g.is_active = TRUE
                       AND (g.expire_date IS NULL OR g.expire_date >= now())';
        }
        $sql .= ' ORDER BY g.display_order ASC, g.publish_date ASC NULLS LAST, g.created_at ASC';
        return Database::getInstance()->fetchAll($sql);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array {
        return Database::getInstance()->fetch(
            'SELECT * FROM general_content WHERE id = ?', [$id]
        );
    }

    /**
     * @param string $category Clave de CONTENT_CATEGORIES
     * @return array<int, array<string, mixed>>
     */
    public static function byCategory(string $category, int $limit = 20): array {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM general_content
             WHERE category = ? AND is_active = TRUE
             AND (expire_date IS NULL OR expire_date >= now())
             ORDER BY publish_date DESC LIMIT ?',
            [$category, $limit]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return int id
     */
    public static function create(array $data): int {
        // Limpiar y normalizar campos antes de insertar
        $data = self::normalizeData($data);
        $data['display_order'] = ContentDisplayOrder::next('general_content');

        $data['created_at'] = date('c');
        $data['updated_at'] = date('c');

        if (empty($data['publish_date'])) {
            $data['publish_date'] = date('c');
        }
        if (empty($data['expire_date'])) {
            unset($data['expire_date']); // No guardar NULL como string vacío
        }

        if (!empty($data['external_data']) && is_array($data['external_data'])) {
            $data['external_data'] = json_encode($data['external_data']);
        } elseif (isset($data['external_data']) && $data['external_data'] === '') {
            $data['external_data'] = null;
        }

        return Database::getInstance()->insert('general_content', $data);
    }

    /**
     * @param array<string, mixed> $data
     * @return int Filas afectadas
     */
    public static function update(int $id, array $data): int {
        $data = self::normalizeData($data);
        unset($data['display_order']);
        $data['updated_at'] = date('c');

        if (empty($data['expire_date'])) {
            unset($data['expire_date']);
        }

        if (!empty($data['external_data']) && is_array($data['external_data'])) {
            $data['external_data'] = json_encode($data['external_data']);
        } elseif (isset($data['external_data']) && $data['external_data'] === '') {
            $data['external_data'] = null;
        }

        return Database::getInstance()->update('general_content', $data,
            'id = :id', ['id' => $id]);
    }

    /**
     * Clave del banco GENERAL_ICONS; vacío o inválido → null.
     */
    public static function sanitizeIcon(?string $icon): ?string {
        if ($icon === null) {
            return null;
        }
        $raw = trim($icon);
        if ($raw === '' || !in_array($raw, GENERAL_ICONS, true)) {
            return null;
        }
        return $raw;
    }

    /**
     * Normaliza los datos antes de guardar
     * Convierte tipos correctamente (boolean, integer, null)
     */
    private static function normalizeData(array $data): array {
        // Solo columnas editables vía CRUD/form (evita id, author_name, JSON corrupto, etc.)
        $allowed = [
            'category', 'title', 'description', 'source_url', 'image_path', 'icon',
            'external_data', 'duration_seconds', 'display_order', 'is_active', 'is_external',
            'publish_date', 'expire_date', 'created_by',
        ];
        $data = array_intersect_key($data, array_flip($allowed));

        // Convertir booleanos
        $data['is_active'] = !empty($data['is_active']) && $data['is_active'] !== '0' && $data['is_active'] !== 'false';
        $data['is_external'] = !empty($data['is_external']) && $data['is_external'] !== '0' && $data['is_external'] !== 'false';

        // Convertir enteros
        if (isset($data['duration_seconds'])) {
            $data['duration_seconds'] = (int) $data['duration_seconds'];
        }
        if (isset($data['display_order'])) {
            $data['display_order'] = (int) $data['display_order'];
        }
        if (isset($data['created_by'])) {
            $data['created_by'] = (int) $data['created_by'];
            // Form/API vacíos o 0 no deben pisar el autor (rompe FK users).
            if ($data['created_by'] <= 0) {
                unset($data['created_by']);
            }
        }

        // Limpiar strings vacíos en campos opcionales
        foreach (['source_url', 'image_path', 'description'] as $field) {
            if (isset($data[$field]) && trim($data[$field]) === '') {
                $data[$field] = null;
            }
        }

        // Título opcional (NOT NULL → cadena vacía)
        if (!isset($data['title']) || trim((string) $data['title']) === '') {
            $data['title'] = '';
        }

        if (array_key_exists('icon', $data)) {
            $data['icon'] = self::sanitizeIcon(
                $data['icon'] === null ? null : (string) $data['icon']
            );
        }

        // Security::sanitize convierte " en &quot; y rompe JSONB: revertir o descartar.
        if (array_key_exists('external_data', $data) && is_string($data['external_data'])) {
            $raw = html_entity_decode($data['external_data'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $parsed = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                $data['external_data'] = json_encode($parsed);
            } else {
                unset($data['external_data']);
            }
        }

        // Limpiar fechas vacías o inválidas (incluye "null"/"undefined" que envía el front)
        foreach (['publish_date', 'expire_date', 'show_from', 'show_until'] as $field) {
            if (!isset($data[$field])) continue;
            $raw = trim((string) $data[$field]);
            if ($raw === '' || strtolower($raw) === 'null' || strtolower($raw) === 'undefined' || strtotime($raw) === false) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Borra y compacta display_order.
     *
     * @return int Filas borradas
     */
    public static function delete(int $id): int {
        $n = Database::getInstance()->delete('general_content', 'id = :id', ['id' => $id]);
        if ($n > 0) {
            ContentDisplayOrder::renumber('general_content');
        }
        return $n;
    }

    /** Invierte is_active. @return int Filas afectadas */
    public static function toggleActive(int $id): int {
        return Database::getInstance()->query(
            'UPDATE general_content SET is_active = NOT is_active, updated_at = now() WHERE id = ?',
            [$id]
        )->rowCount();
    }

    /**
     * Slides vigentes para TV. expire_date nulo = sin caducidad.
     *
     * @param string|null $category Si se pasa, filtra esa categoría
     * @return array<int, array<string, mixed>>
     */
    public static function getActiveForTv(?string $category = null): array {
        $sql = 'SELECT * FROM general_content
                WHERE is_active = TRUE
                AND (expire_date IS NULL OR expire_date >= now())';
        $params = [];
        if ($category) {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }
        $sql .= ' ORDER BY display_order ASC, publish_date ASC NULLS LAST, created_at ASC';
        return Database::getInstance()->fetchAll($sql, $params);
    }

    /**
     * @return array<string, array<int, string>> Título canónico => alias a desactivar
     */
    private static function externalTitleAliases(): array {
        return [
            'Café (Arábica) - Precio Internacional' => [
                'Cafe (Arabica) - Precio Internacional',
                'Café - Precio Internacional',
                'Cafe - Precio Internacional',
            ],
            'Petróleo - Precios WTI y Brent' => [
                'Petroleo - Precios WTI y Brent',
            ],
            'Cotización del Dólar (USD)' => [
                'Cotizacion del Dolar (USD)',
                'Cotización del Dolar (USD)',
                'Cotizacion del Dólar (USD)',
            ],
        ];
    }

    /**
     * Inserta o actualiza un indicador externo (cron). Desactiva títulos alias.
     *
     * @param mixed $data Array que se guarda como JSON en external_data
     * @return int id
     */
    public static function upsertExternal(string $category, string $title, $data, ?string $sourceUrl = null): int {
        $db = Database::getInstance();
        $existing = $db->fetch(
            'SELECT id FROM general_content WHERE category = ? AND title = ?',
            [$category, $title]
        );
        $payload = [
            'category'     => $category,
            'title'        => $title,
            // Keep description empty for indicators; TV shows external_data as a table.
            'description'  => null,
            'external_data'=> is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
            'source_url'   => $sourceUrl,
            'is_external'  => true,
            'is_active'    => true,
            'publish_date' => date('c'),
            'updated_at'   => date('c'),
        ];
        if ($existing) {
            $db->update('general_content', $payload,
                'id = :id', ['id' => $existing['id']]);
            $id = (int) $existing['id'];
        } else {
            $payload['created_at'] = date('c');
            $id = $db->insert('general_content', $payload);
        }

        // Desactivar alias legacy (sin tilde / títulos viejos) para no mostrar duplicados
        $aliases = self::externalTitleAliases()[$title] ?? [];
        foreach ($aliases as $alias) {
            $db->query(
                'UPDATE general_content SET is_active = FALSE, updated_at = now()
                 WHERE category = ? AND title = ? AND id <> ?',
                [$category, $alias, $id]
            );
        }

        return $id;
    }
}