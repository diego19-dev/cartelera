<?php
/**
 * Contenido académico (tabla academic_content).
 *
 * Lo usa el CRUD de public/admin/academic/ y TvController para el feed de la
 * cartelera. program_head solo ve/edita su program_area (Auth::canEditArea).
 * create() asigna display_order; delete() compacta la secuencia.
 */
class AcademicContent {

    /**
     * Lista registros (con author_name), opcionalmente solo vigentes.
     *
     * @param bool $activeOnly Si true, filtra is_active y ventana show_from/show_until
     * @return array<int, array<string, mixed>>
     */
    public static function all(bool $activeOnly = false): array {
        $sql = 'SELECT a.*, u.full_name AS author_name
                FROM academic_content a
                LEFT JOIN users u ON u.id = a.created_by';
        if ($activeOnly) {
            $sql .= ' WHERE a.is_active = TRUE
                       AND (a.show_from IS NULL OR a.show_from <= now())
                       AND (a.show_until IS NULL OR a.show_until >= now())';
        }
        $sql .= ' ORDER BY a.display_order ASC, a.created_at ASC';
        return Database::getInstance()->fetchAll($sql);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array {
        return Database::getInstance()->fetch(
            'SELECT * FROM academic_content WHERE id = ?', [$id]
        );
    }

    /**
     * Inserta slide. Asigna display_order y aplica ventana de vigencia.
     *
     * @param array<string, mixed> $data
     * @return int id
     */
    public static function create(array $data): int {
        $data = self::normalizeData($data);
        $data['display_order'] = ContentDisplayOrder::next('academic_content');
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

    /**
     * Actualiza campos; no toca display_order.
     *
     * @param array<string, mixed> $data
     * @return int Filas afectadas
     */
    public static function update(int $id, array $data): int {
        $existing = self::find($id);
        $data = self::normalizeData($data);
        unset($data['display_order']);
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
     * Bool/int y strings vacíos → null antes de persistir.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
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

    /**
     * Borra el registro y compacta display_order (1…N).
     *
     * @return int Filas borradas
     */
    public static function delete(int $id): int {
        $n = Database::getInstance()->delete('academic_content', 'id = :id', ['id' => $id]);
        if ($n > 0) {
            ContentDisplayOrder::renumber('academic_content');
        }
        return $n;
    }

    /** Invierte is_active. @return int Filas afectadas */
    public static function toggleActive(int $id): int {
        return Database::getInstance()->query(
            'UPDATE academic_content SET is_active = NOT is_active, updated_at = now() WHERE id = ?',
            [$id]
        )->rowCount();
    }

    /**
     * Slides vigentes para GET /api/tv (orden display_order).
     *
     * @param string|null $area Si se pasa, incluye filas de esa área o sin área
     * @return array<int, array<string, mixed>>
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
        $sql .= ' ORDER BY display_order ASC, created_at ASC';
        return Database::getInstance()->fetchAll($sql, $params);
    }

    /** Y-m-d o null si strtotime falla. */
    private static function parseDate(string $d): ?string {
        $ts = strtotime($d);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }
}
