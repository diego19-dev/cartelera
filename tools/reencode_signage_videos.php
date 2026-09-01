#!/usr/bin/env php
<?php
/**
 * Reencoda videos existentes al perfil LG Signage (H.264 Baseline + faststart).
 *
 * Uso:
 *   php tools/reencode_signage_videos.php           # academic + center
 *   php tools/reencode_signage_videos.php academic
 *   php tools/reencode_signage_videos.php --dry-run
 */

require_once __DIR__ . '/../src/Core/bootstrap.php';

$args = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $args, true);
$categories = array_values(array_filter($args, static fn($a) => $a !== '--dry-run' && $a !== ''));
if ($categories === []) {
    $categories = ['academic', 'center'];
}

if (!VideoTranscoder::isAvailable()) {
    fwrite(STDERR, "ERROR: ffmpeg no está instalado (command -v ffmpeg).\n");
    exit(1);
}

$ok = 0;
$fail = 0;
$skip = 0;

foreach ($categories as $category) {
    $dir = UPLOAD_PATH . '/' . $category;
    if (!is_dir($dir)) {
        echo "SKIP dir inexistente: {$dir}\n";
        continue;
    }
    $files = glob($dir . '/*.{mp4,webm,mov,ogg,m4v}', GLOB_BRACE) ?: [];
    foreach ($files as $path) {
        $base = basename($path);
        if (str_starts_with($base, '.')) {
            continue;
        }
        $out = preg_replace('/\.[^.]+$/', '.mp4', $path);
        if ($out === null) {
            $out = $path . '.mp4';
        }
        echo ($dryRun ? '[DRY] ' : '') . "{$category}/{$base} → " . basename($out) . "\n";
        if ($dryRun) {
            $skip++;
            continue;
        }
        $bak = $path . '.bak-signage';
        try {
            if (!@copy($path, $bak)) {
                throw new RuntimeException('no se pudo crear backup');
            }
            VideoTranscoder::toSignageMp4($path, $out);
            if (realpath($path) !== realpath($out) && is_file($out)) {
                @unlink($path);
            }
            @unlink($bak);
            $ok++;
            echo "  OK (" . round(filesize($out) / 1024 / 1024, 2) . " MB)\n";
        } catch (Throwable $e) {
            $fail++;
            if (is_file($bak) && !is_file($path)) {
                @rename($bak, $path);
            }
            @unlink($bak);
            echo '  FAIL: ' . $e->getMessage() . "\n";
        }
    }
}

echo "\nListo: ok={$ok} fail={$fail} dry/skip={$skip}\n";
exit($fail > 0 ? 2 : 0);
