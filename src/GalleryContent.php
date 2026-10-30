<?php
/**
 * Galería de imágenes (tabla gallery_content). Solo image_path, sin video.
 *
 * Módulo en PANEL_DISABLED_MODULES (sin CRUD de panel); la TV sí llama
 * getActiveForTv().
 */
class GalleryContent {

    /**
     * @param bool $activeOnly Solo is_active (sin ventana temporal en el listado)
     * @return array<int, array<string, mixed>>
     */
    public static function all(bool $activeOnly = false): array {
        $sql = 'SELECT g.*, u.full_name AS author_name
                FROM gallery_content g
                LEFT JOIN users u ON u.id = g.created_by';
        if ($activeOnly) {
            $sql .= ' WHERE g.is_active = TRUE';
        }
        $sql .= ' ORDER BY g.display_order ASC, g.created_at ASC';
        return Database::getInstance()->fetchAll($sql);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array {
        return Database::getInstance()->fetch(
            'SELECT * FROM gallery_content WHERE id = ?', [$id]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return int id
     */
    public static function create(array $data): int {
        $data = self::normalizeData($data);
        $data['display_order'] = ContentDisplayOrder::next('gallery_content');
        ContentValidity::applyShowWindow($data);
        $data['created_at'] = date('c');
        $data['updated_at'] = date('c');
        return Database::getInstance()->insert('gallery_content', $data);
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
        return Database::getInstance()->update('gallery_content', $data,
            'id = :id', ['id' => $id]);
    }

    private static function normalizeData(array $data): array {
        $data['is_active'] = !empty($data['is_active']) && $data['is_active'] !== '0' && $data['is_active'] !== 'false';

        foreach (['duration_seconds', 'display_order', 'created_by'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }

        if (isset($data['title']) && trim((string) $data['title']) === '') {
            $data['title'] = null;
        }

        foreach (['show_from', 'show_until'] as $field) {
            if (isset($data[$field]) && trim((string) $data[$field]) === '') {
                $data[$field] = null;
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
        $n = Database::getInstance()->delete('gallery_content', 'id = :id', ['id' => $id]);
        if ($n > 0) {
            ContentDisplayOrder::renumber('gallery_content');
        }
        return $n;
    }

    /** Invierte is_active. @return int Filas afectadas */
    public static function toggleActive(int $id): int {
        return Database::getInstance()->query(
            'UPDATE gallery_content SET is_active = NOT is_active, updated_at = now() WHERE id = ?',
            [$id]
        )->rowCount();
    }

    /**
     * Imágenes vigentes con image_path no vacío, para el escenario gallery de la TV.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getActiveForTv(): array {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM gallery_content
             WHERE is_active = TRUE
               AND (show_from IS NULL OR show_from <= now())
               AND (show_until IS NULL OR show_until >= now())
               AND image_path IS NOT NULL
               AND image_path <> \'\'
             ORDER BY display_order ASC, created_at ASC'
        );
    }
}
