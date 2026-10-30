<?php
/**
 * Contenido «Nuestro centro» (tabla center_content), por sector (PROGRAM_AREAS).
 *
 * El módulo está en PANEL_DISABLED_MODULES: no hay CRUD en el panel, pero
 * TvController sigue sirviendo getActiveForTv().
 */
class CenterContent {

    /**
     * @param bool $activeOnly Filtra vigencia show_from/show_until
     * @return array<int, array<string, mixed>>
     */
    public static function all(bool $activeOnly = false): array {
        $sql = 'SELECT c.*, u.full_name AS author_name
                FROM center_content c
                LEFT JOIN users u ON u.id = c.created_by';
        if ($activeOnly) {
            $sql .= ' WHERE c.is_active = TRUE
                       AND (c.show_from IS NULL OR c.show_from <= now())
                       AND (c.show_until IS NULL OR c.show_until >= now())';
        }
        $sql .= ' ORDER BY c.sector, c.display_order ASC, c.created_at ASC';
        return Database::getInstance()->fetchAll($sql);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array {
        return Database::getInstance()->fetch(
            'SELECT * FROM center_content WHERE id = ?', [$id]
        );
    }

    /**
     * Slides activos de un sector (clave de PROGRAM_AREAS).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function bySector(string $sector): array {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM center_content
             WHERE sector = ? AND is_active = TRUE
               AND (show_from IS NULL OR show_from <= now())
               AND (show_until IS NULL OR show_until >= now())
             ORDER BY display_order ASC, created_at ASC',
            [$sector]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return int id
     */
    public static function create(array $data): int {
        $data = self::normalizeData($data);
        $data['display_order'] = ContentDisplayOrder::next('center_content');
        ContentValidity::applyShowWindow($data);
        $data['created_at'] = date('c');
        $data['updated_at'] = date('c');
        return Database::getInstance()->insert('center_content', $data);
    }

    /**
     * @param array<string, mixed> $data
     * @return int Filas afectadas
     */
    public static function update(int $id, array $data): int {
        $existing = self::find($id);
        $data = self::normalizeData($data);
        unset($data['display_order']);
        ContentValidity::applyShowWindow($data, $existing['show_until'] ?? null);
        $data['updated_at'] = date('c');
        return Database::getInstance()->update('center_content', $data,
            'id = :id', ['id' => $id]);
    }

    /**
     * Normaliza los datos antes de guardar
     */
    private static function normalizeData(array $data): array {
        $data['is_active'] = !empty($data['is_active']) && $data['is_active'] !== '0' && $data['is_active'] !== 'false';

        foreach (['duration_seconds', 'display_order', 'created_by'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }

        foreach (['media_path', 'thumbnail_path', 'description'] as $field) {
            if (isset($data[$field]) && trim((string) $data[$field]) === '') {
                $data[$field] = null;
            }
        }

        // Título opcional (NOT NULL → cadena vacía)
        if (!isset($data['title']) || trim((string) $data['title']) === '') {
            $data['title'] = '';
        }

        return $data;
    }

    /**
     * Borra y compacta display_order.
     *
     * @return int Filas borradas
     */
    public static function delete(int $id): int {
        $n = Database::getInstance()->delete('center_content', 'id = :id', ['id' => $id]);
        if ($n > 0) {
            ContentDisplayOrder::renumber('center_content');
        }
        return $n;
    }

    /** Invierte is_active. @return int Filas afectadas */
    public static function toggleActive(int $id): int {
        return Database::getInstance()->query(
            'UPDATE center_content SET is_active = NOT is_active, updated_at = now() WHERE id = ?',
            [$id]
        )->rowCount();
    }

    /**
     * Slides vigentes para TV, ordenados por sector y display_order.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getActiveForTv(): array {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM center_content WHERE is_active = TRUE
               AND (show_from IS NULL OR show_from <= now())
               AND (show_until IS NULL OR show_until >= now())
             ORDER BY sector, display_order ASC, created_at ASC'
        );
    }
}
