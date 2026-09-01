<?php
/**
 * Modelo GalleryContent - Escenario Galería (solo imágenes)
 */

class GalleryContent {

    public static function all(bool $activeOnly = false): array {
        $sql = 'SELECT g.*, u.full_name AS author_name
                FROM gallery_content g
                LEFT JOIN users u ON u.id = g.created_by';
        if ($activeOnly) {
            $sql .= ' WHERE g.is_active = TRUE';
        }
        $sql .= ' ORDER BY g.display_order, g.created_at DESC';
        return Database::getInstance()->fetchAll($sql);
    }

    public static function find(int $id): ?array {
        return Database::getInstance()->fetch(
            'SELECT * FROM gallery_content WHERE id = ?', [$id]
        );
    }

    public static function create(array $data): int {
        $data = self::normalizeData($data);
        ContentValidity::applyShowWindow($data);
        $data['created_at'] = date('c');
        $data['updated_at'] = date('c');
        return Database::getInstance()->insert('gallery_content', $data);
    }

    public static function update(int $id, array $data): int {
        $existing = self::find($id);
        $data = self::normalizeData($data);
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

    public static function delete(int $id): int {
        return Database::getInstance()->delete('gallery_content', 'id = :id', ['id' => $id]);
    }

    public static function toggleActive(int $id): int {
        return Database::getInstance()->query(
            'UPDATE gallery_content SET is_active = NOT is_active, updated_at = now() WHERE id = ?',
            [$id]
        )->rowCount();
    }

    public static function getActiveForTv(): array {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM gallery_content
             WHERE is_active = TRUE
               AND (show_from IS NULL OR show_from <= now())
               AND (show_until IS NULL OR show_until >= now())
               AND image_path IS NOT NULL
               AND image_path <> \'\'
             ORDER BY display_order, created_at DESC'
        );
    }
}
