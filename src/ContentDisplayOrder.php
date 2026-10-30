<?php
/**
 * Secuencia automática de display_order (1…999999) en tablas de contenido.
 *
 * next() al crear; renumber() al borrar. Tablas: academic_content,
 * gallery_content, general_content, center_content. tools/renumber_display_order.php
 * usa renumberAllTables().
 */
class ContentDisplayOrder {

    public const MAX = 999999;

    private const TABLES = [
        'academic_content',
        'gallery_content',
        'general_content',
        'center_content',
    ];

    /**
     * @throws InvalidArgumentException Si la tabla no está en TABLES
     */
    public static function assertTable(string $table): string {
        if (!in_array($table, self::TABLES, true)) {
            throw new InvalidArgumentException('Tabla no permitida para display_order: ' . $table);
        }
        return $table;
    }

    /** Siguiente número de orden (tope MAX). */
    public static function next(string $table): int {
        $table = self::assertTable($table);
        $max = (int) Database::getInstance()->fetchColumn(
            "SELECT COALESCE(MAX(display_order), 0) FROM {$table}"
        );
        return min($max + 1, self::MAX);
    }

    /**
     * Compacta a 1…N según orden actual (display_order, created_at, id).
     * Usado tras eliminar.
     */
    public static function renumber(string $table): void {
        self::renumberOrderedBy($table, 'display_order ASC, created_at ASC NULLS LAST, id ASC');
    }

    /**
     * Compacta a 1…N por antigüedad (migración one-shot).
     */
    public static function renumberChronological(string $table): void {
        self::renumberOrderedBy($table, 'created_at ASC NULLS LAST, id ASC');
    }

    /** Compacta las cuatro tablas. $chronological = por created_at (migración). */
    public static function renumberAllTables(bool $chronological = false): void {
        foreach (self::TABLES as $table) {
            if ($chronological) {
                self::renumberChronological($table);
            } else {
                self::renumber($table);
            }
        }
    }

    private static function renumberOrderedBy(string $table, string $orderBy): void {
        $table = self::assertTable($table);
        // orderBy viene solo de constantes internas (no input de usuario).
        Database::getInstance()->query(
            "WITH ordered AS (
                SELECT id, ROW_NUMBER() OVER (ORDER BY {$orderBy}) AS rn
                FROM {$table}
             )
             UPDATE {$table} AS t
             SET display_order = ordered.rn
             FROM ordered
             WHERE t.id = ordered.id"
        );
    }
}
