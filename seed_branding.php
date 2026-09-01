<?php
require_once __DIR__ . '/src/Core/bootstrap.php';

$db = Database::getInstance();
$configs = [
    'brand_logo_path'    => '',
    'brand_logo_url'     => '',
    'brand_primary_color' => '#0b3d91',
    'brand_secondary_color' => '#1e5ba8',
    'brand_accent_color' => '#e6b800',
    'brand_bg_color' => '#f1f5f9',
    'brand_text_color' => '#1e293b',
    'brand_font_family' => "'Segoe UI', Roboto, sans-serif",
    'brand_logo_size' => 'medium',
    
    // Modo oscuro
    'brand_dark_primary_color' => '#0b3d91',
    'brand_dark_secondary_color' => '#1e5ba8',
    'brand_dark_accent_color' => '#e6b800',
    'brand_dark_bg_color' => '#0a0e27',
    'brand_dark_text_color' => '#ffffff',
];
foreach ($configs as $key => $value) {
    $exists = $db->fetch('SELECT id FROM display_config WHERE config_key = ?', [$key]);
    if (!$exists) {
        $db->insert('display_config', [
            'config_key' => $key,
            'config_value' => $value,
            'description' => 'Branding - ' . $key,
        ]);
        echo "[+] $key\n";
    } else {
        echo "[=] $key ya existe\n";
    }
}
echo PHP_EOL . "Configuraciones de branding listas" . PHP_EOL;