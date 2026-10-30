<?php
/**
 * Duración de videos en uploads: ffprobe, o átomo mvhd si es MP4.
 *
 * Lo usa MediaController al subir video para duration_seconds del slide
 * (tope MAX_VIDEO_DURATION_SECONDS = 10 min).
 */
class VideoDuration {

    /**
     * Convierte duración real a segundos de slide (ceil, clamp min/max).
     *
     * @throws RuntimeException Si supera MAX_VIDEO_DURATION_SECONDS
     */
    public static function toSlideSeconds(float $seconds): int {
        if ($seconds > MAX_VIDEO_DURATION_SECONDS + 0.5) {
            throw new RuntimeException('El video supera el máximo de 10 minutos');
        }
        $slide = (int) ceil($seconds);
        if ($slide < MIN_SLIDE_DURATION_SECONDS) {
            $slide = MIN_SLIDE_DURATION_SECONDS;
        }
        if ($slide > MAX_SLIDE_DURATION_SECONDS) {
            $slide = MAX_SLIDE_DURATION_SECONDS;
        }
        return $slide;
    }

    /** Acota segundos de slide a MIN_SLIDE_DURATION_SECONDS … MAX_SLIDE_DURATION_SECONDS. */
    public static function clampSlideSeconds(int $seconds): int {
        if ($seconds < MIN_SLIDE_DURATION_SECONDS) {
            return MIN_SLIDE_DURATION_SECONDS;
        }
        if ($seconds > MAX_SLIDE_DURATION_SECONDS) {
            return MAX_SLIDE_DURATION_SECONDS;
        }
        return $seconds;
    }

    /**
     * Duración en segundos del archivo, o excepción si no se puede leer.
     *
     * @param string $ext Extensión sin punto (mp4, mov, …)
     * @throws RuntimeException
     */
    public static function probe(string $path, string $ext): float {
        $ext = strtolower($ext);
        $seconds = self::fromFfprobe($path);
        if ($seconds === null && ($ext === 'mp4' || $ext === 'm4v' || $ext === 'mov')) {
            $seconds = self::fromMp4Mvhd($path);
        }
        if ($seconds === null || $seconds <= 0) {
            throw new RuntimeException(
                'No se pudo leer la duración del video. Use MP4 o instale ffprobe.'
            );
        }
        return $seconds;
    }

    /**
     * ffprobe local (scripts/bin/ffprobe) o del PATH; null si no hay binario.
     *
     * @return float|null Segundos o null
     */
    public static function fromFfprobe(string $path): ?float {
        if (!is_readable($path)) {
            return null;
        }
        $local = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__)) . '/scripts/bin/ffprobe';
        $bin = is_executable($local) ? $local : '';
        if ($bin === '' && function_exists('shell_exec')) {
            $bin = trim((string) shell_exec('command -v ffprobe 2>/dev/null'));
        }
        if ($bin === '') {
            return null;
        }
        $cmd = escapeshellarg($bin)
            . ' -v error -show_entries format=duration'
            . ' -of default=noprint_wrappers=1:nokey=1 '
            . escapeshellarg($path)
            . ' 2>/dev/null';
        $out = shell_exec($cmd);
        if ($out === null || trim($out) === '' || !is_numeric(trim($out))) {
            return null;
        }
        $val = (float) trim($out);
        return $val > 0 ? $val : null;
    }

    /** Fallback: recorre boxes MP4 hasta mvhd. */
    public static function fromMp4Mvhd(string $path): ?float {
        $size = @filesize($path);
        if ($size === false || $size < 16) {
            return null;
        }
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return null;
        }
        try {
            return self::scanBoxesForMvhd($fh, 0, (int) $size);
        } finally {
            fclose($fh);
        }
    }

    /** @param resource $fh */
    private static function scanBoxesForMvhd($fh, int $start, int $end): ?float {
        $offset = $start;
        while ($offset + 8 <= $end) {
            if (fseek($fh, $offset) !== 0) {
                return null;
            }
            $header = fread($fh, 8);
            if ($header === false || strlen($header) < 8) {
                return null;
            }
            $boxSize = unpack('N', substr($header, 0, 4))[1];
            $type = substr($header, 4, 4);
            $headerLen = 8;

            if ($boxSize === 1) {
                $large = fread($fh, 8);
                if ($large === false || strlen($large) < 8) {
                    return null;
                }
                $parts = unpack('N2', $large);
                $boxSize = ($parts[1] << 32) + $parts[2];
                $headerLen = 16;
            } elseif ($boxSize === 0) {
                $boxSize = $end - $offset;
            }

            if ($boxSize < $headerLen) {
                return null;
            }
            $boxEnd = $offset + $boxSize;
            if ($boxEnd > $end) {
                return null;
            }

            if (in_array($type, ['moov', 'trak', 'mdia', 'minf', 'stbl'], true)) {
                $found = self::scanBoxesForMvhd($fh, $offset + $headerLen, $boxEnd);
                if ($found !== null) {
                    return $found;
                }
            } elseif ($type === 'mvhd') {
                return self::readMvhdDuration($fh, $offset + $headerLen);
            }

            $offset = $boxEnd;
        }
        return null;
    }

    /** @param resource $fh */
    private static function readMvhdDuration($fh, int $bodyStart): ?float {
        if (fseek($fh, $bodyStart) !== 0) {
            return null;
        }
        $versionFlags = fread($fh, 4);
        if ($versionFlags === false || strlen($versionFlags) < 4) {
            return null;
        }
        $version = ord($versionFlags[0]);

        if ($version === 1) {
            $skip = fread($fh, 16);
            if ($skip === false || strlen($skip) < 16) {
                return null;
            }
            $ts = fread($fh, 4);
            $dur = fread($fh, 8);
            if ($ts === false || $dur === false || strlen($ts) < 4 || strlen($dur) < 8) {
                return null;
            }
            $timescale = unpack('N', $ts)[1];
            $parts = unpack('N2', $dur);
            $duration = ($parts[1] << 32) + $parts[2];
        } else {
            $skip = fread($fh, 8);
            if ($skip === false || strlen($skip) < 8) {
                return null;
            }
            $ts = fread($fh, 4);
            $dur = fread($fh, 4);
            if ($ts === false || $dur === false || strlen($ts) < 4 || strlen($dur) < 4) {
                return null;
            }
            $timescale = unpack('N', $ts)[1];
            $duration = unpack('N', $dur)[1];
        }

        if ($timescale <= 0 || $duration <= 0) {
            return null;
        }
        return $duration / $timescale;
    }
}
