<?php
/**
 * Transcodifica a MP4 H.264 Baseline + AAC para LG Signage / Chromium 53.
 *
 * Lo llama MediaController tras un upload de video. Perfil: yuv420p, máx. 1080p,
 * +faststart. ffmpeg en scripts/bin/ffmpeg o en el PATH.
 */
class VideoTranscoder {

    /**
     * Escribe MP4 Signage en $destPath (debe terminar en .mp4).
     *
     * @throws RuntimeException Si no hay ffmpeg, el origen no se lee, o falla la conversión
     */
    public static function toSignageMp4(string $sourcePath, string $destPath): void {
        if (!is_readable($sourcePath)) {
            throw new RuntimeException('No se pudo leer el video para optimizarlo');
        }
        $ffmpeg = self::ffmpegBin();
        if ($ffmpeg === null) {
            throw new RuntimeException(
                'No se pudo optimizar el video para TV: instale ffmpeg en el servidor'
            );
        }

        $tmpOut = $destPath . '.tmp.' . bin2hex(random_bytes(4)) . '.mp4';
        // scale: limita a 1920x1080 manteniendo aspect; yuv420p para decoders antiguos
        $vf = "scale='min(1920,iw)':'min(1080,ih)':force_original_aspect_ratio=decrease,setsar=1,format=yuv420p";
        $cmd = escapeshellarg($ffmpeg)
            . ' -y -i ' . escapeshellarg($sourcePath)
            . ' -map 0:v:0 -map 0:a:0?'
            . ' -c:v libx264 -profile:v baseline -level 3.1 -preset veryfast -crf 23'
            . ' -vf ' . escapeshellarg($vf)
            . ' -c:a aac -b:a 128k -ac 2 -ar 44100'
            . ' -movflags +faststart'
            . ' -max_muxing_queue_size 1024'
            . ' ' . escapeshellarg($tmpOut)
            . ' 2>&1';

        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        if ($code !== 0 || !is_file($tmpOut) || filesize($tmpOut) < 64) {
            @unlink($tmpOut);
            $tail = implode("\n", array_slice($output, -12));
            throw new RuntimeException(
                'No se pudo optimizar el video para TV (ffmpeg).' .
                ($tail !== '' ? ' Detalle: ' . $tail : '')
            );
        }

        if (!@rename($tmpOut, $destPath)) {
            @unlink($tmpOut);
            throw new RuntimeException('No se pudo guardar el video optimizado');
        }
        @chmod($destPath, 0644);
    }

    /** Ruta de ffmpeg ejecutable, o null. */
    public static function ffmpegBin(): ?string {
        $local = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__)) . '/scripts/bin/ffmpeg';
        if (is_executable($local)) {
            return $local;
        }
        if (!function_exists('shell_exec')) {
            return null;
        }
        $bin = trim((string) shell_exec('command -v ffmpeg 2>/dev/null'));
        return $bin !== '' ? $bin : null;
    }

    public static function isAvailable(): bool {
        return self::ffmpegBin() !== null;
    }
}
