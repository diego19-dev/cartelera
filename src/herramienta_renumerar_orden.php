<?php
/**
 * CLI one-shot: compacta display_order por created_at (1…N) en las 4 tablas.
 *
 * Uso: php tools/renumber_display_order.php
 * @see ContentDisplayOrder::renumberChronological()
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/bootstrap.php';

$tables = [
    'academic_content',
    'gallery_content',
    'general_content',
    'center_content',
];

echo "Renumerando display_order por created_at ASC…\n";
ContentDisplayOrder::renumberAllTables(true);

foreach ($tables as $table) {
    $rows = Database::getInstance()->fetchAll(
        "SELECT id, display_order, created_at FROM {$table} ORDER BY display_order ASC, id ASC LIMIT 5"
    );
    $count = (int) Database::getInstance()->fetchColumn("SELECT COUNT(*) FROM {$table}");
    $max = (int) Database::getInstance()->fetchColumn("SELECT COALESCE(MAX(display_order), 0) FROM {$table}");
    echo "{$table}: count={$count} max_order={$max}\n";
    foreach ($rows as $r) {
        echo "  id={$r['id']} order={$r['display_order']} created={$r['created_at']}\n";
    }
}

echo "Listo.\n";
