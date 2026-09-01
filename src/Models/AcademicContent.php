<?php
/**
 * Modelo AcademicContent - Contenido académico
 */

class AcademicContent {

    public static function all(bool $activeOnly = false): array {
        $sql = 'SELECT a.*, u.full_name AS author_name
                FROM academic_content a
                LEFT JOIN users u ON u.id = a.created_by';
        if ($activeOnly) {
            $sql .= ' WHERE a.is_active = TRUE
                       AND (a.show_from IS NULL OR a.show_from <= now())
                       AND (a.show_until IS NULL OR a.show_until >= now())';
        }
        $sql .= ' ORDER BY a.display_order, a.created_at DESC';
        return Database::getInstance()->fetchAll($sql);
    }

    public static function find(int $id): ?array {
        return Database::getInstance()->fetch(
            'SELECT * FROM academic_content WHERE id = ?', [$id]
        );
    }

    public static function create(array $data): int {
        $data = self::normalizeData($data);
        ContentValidity::applyShowWindow($data);
        $data['created_at'] = date('c');
        $data['updated_at'] = date('c');

        if (!empty($data['start_date']) && is_string($data['start_date'])) {
            $data['start_date'] = self::parseDate($data['start_date']);
        } else {
            unset($data['start_date']);
        }
        if (!empty($data['end_date']) && is_string($data['end_date'])) {
            $data['end_date'] = self::parseDate($data['end_date']);
        } else {
            unset($data['end_date']);
        }

        return Database::getInstance()->insert('academic_content', $data);
    }

    public static function update(int $id, array $data): int {
        $existing = self::find($id);
        $data = self::normalizeData($data);
        ContentValidity::applyShowWindow($data, $existing['show_until'] ?? null);
        $data['updated_at'] = date('c');

        if (!empty($data['start_date']) && is_string($data['start_date'])) {
            $data['start_date'] = self::parseDate($data['start_date']);
        } else {
            unset($data['start_date']);
        }
        if (!empty($data['end_date']) && is_string($data['end_date'])) {
            $data['end_date'] = self::parseDate($data['end_date']);
        } else {
            unset($data['end_date']);
        }

        return Database::getInstance()->update('academic_content', $data,
            'id = :id', ['id' => $id]);
    }

    /**
     * Normaliza los datos antes de guardar
     */
    private static function normalizeData(array $data): array {
        // Convertir booleanos correctamente
        $data['is_active'] = !empty($data['is_active']) && $data['is_active'] !== '0' && $data['is_active'] !== 'false';

        // Convertir enteros
        foreach (['duration_seconds', 'display_order', 'created_by'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }

        // Limpiar strings vacíos
        foreach (['media_path', 'thumbnail_path', 'description', 'program_area'] as $field) {
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

    public static function delete(int $id): int {
        return Database::getInstance()->delete('academic_content', 'id = :id', ['id' => $id]);
    }

    public static function toggleActive(int $id): int {
        return Database::getInstance()->query(
            'UPDATE academic_content SET is_active = NOT is_active, updated_at = now() WHERE id = ?',
            [$id]
        )->rowCount();
    }

    /**
     * Listado activo para la TV
     */
    public static function getActiveForTv(?string $area = null): array {
        $sql = 'SELECT * FROM academic_content
                WHERE is_active = TRUE
                AND (show_from IS NULL OR show_from <= now())
                AND (show_until IS NULL OR show_until >= now())';
        $params = [];
        if ($area) {
            $sql .= ' AND (program_area IS NULL OR program_area = ?)';
            $params[] = $area;
        }
        $sql .= ' ORDER BY display_order, created_at DESC';
        return Database::getInstance()->fetchAll($sql, $params);
    }

    private static function parseDate(string $d): ?string {
        $ts = strtotime($d);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }
}
