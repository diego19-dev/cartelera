<?php
/**
 * Uploads a img/uploads/{category}/ y registro en media_files.
 *
 * Lo llaman formularios del panel y la API. Valida extensión + MIME (finfo),
 * tamaño, y en video: duración (VideoDuration) + transcode Signage
 * (VideoTranscoder). SVG se sanitiza.
 */
class MediaController {

    /**
     * MIME real (finfo) esperado por extensión — antes se detectaba pero nunca se
     * comparaba contra la extensión declarada (ver AUDITORIA_SEGURIDAD_CSET.md A1).
     * Listas permisivas donde el propio finfo/navegador varía legítimamente entre
     * sistemas (ej. .ogg puede ser audio o video, .wav tiene varios MIME "correctos").
     */
    private const EXPECTED_MIME = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        // SVG bien formado casi siempre da image/svg+xml; algunos generadores lo
        // guardan sin declaración XML y finfo lo detecta como texto plano — el
        // parseo real en sanitizeSvg() es la validación fuerte, esto es solo la
        // primera barrera.
        'svg'  => ['image/svg+xml', 'text/xml', 'text/plain'],
        'mp4'  => ['video/mp4'],
        'webm' => ['video/webm'],
        'ogg'  => ['video/ogg', 'audio/ogg', 'application/ogg'],
        'mov'  => ['video/quicktime'],
        'mp3'  => ['audio/mpeg'],
        'wav'  => ['audio/wav', 'audio/x-wav', 'audio/vnd.wave'],
        'm4a'  => ['audio/mp4', 'audio/x-m4a', 'audio/mpeg'],
        'pdf'  => ['application/pdf'],
    ];

    /**
     * Mueve el upload, opcionalmente transcodifica video, y registra MediaFile.
     *
     * @param array<string, mixed> $file Entrada de $_FILES
     * @param string $category academic|center|general|gallery|branding|welcome
     * @return array<string, mixed> filename, url, type, duration_seconds, id, …
     * @throws RuntimeException
     */
    public static function upload(array $file, string $category): array {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::uploadErrorMessage($file['error'] ?? null));
        }

        $originalName = $file['name'];
        $tmpPath      = $file['tmp_name'];
        $size         = $file['size'];
        $mime         = self::detectMime($tmpPath, $originalName);

        // Validar MIME y extensión
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $type = self::resolveType($ext, $mime);

        $expectedMimes = self::EXPECTED_MIME[$ext] ?? null;
        if ($expectedMimes !== null && !in_array($mime, $expectedMimes, true)) {
            if (!(in_array($ext, ALLOWED_VIDEO_TYPES, true) && $mime === 'application/octet-stream')) {
                throw new RuntimeException(
                    'El contenido del archivo no coincide con la extensión .' . $ext
                );
            }
        }

        // Branding / welcome: solo JPG, PNG y SVG
        if ($category === 'branding' || $category === 'welcome') {
            $allowedImage = ['jpg', 'jpeg', 'png', 'svg'];
            if (!in_array($ext, $allowedImage)) {
                throw new RuntimeException('Solo se permiten JPG, PNG y SVG');
            }
            $type = 'image';
        }

        // Galería: solo imágenes
        if ($category === 'gallery') {
            if ($type !== 'image') {
                throw new RuntimeException('En Galería solo se permiten imágenes');
            }
        }

        if ($type === null) {
            throw new RuntimeException('Tipo de archivo no permitido: ' . $ext);
        }

        // Validar tamaño (logos / fondo welcome: máximo 5 MB)
        if ($category === 'branding' || $category === 'welcome') {
            if ($size > 5 * 1024 * 1024) {
                throw new RuntimeException('Imagen demasiado grande (máx 5 MB)');
            }
        } else {
            $maxSize = self::getMaxSize($type);
            if ($size > $maxSize) {
                throw new RuntimeException('Archivo demasiado grande (máx ' .
                    self::humanSize($maxSize) . ')');
            }
        }

        $videoDurationSeconds = null;
        if ($type === 'video') {
            $rawSeconds = VideoDuration::probe($tmpPath, $ext);
            $videoDurationSeconds = VideoDuration::toSlideSeconds($rawSeconds);
        }

        // Videos → siempre .mp4 Signage (H.264 Baseline + faststart)
        $outExt = ($type === 'video') ? 'mp4' : $ext;
        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $outExt;
        $destDir  = UPLOAD_PATH . '/' . $category;
        if (!is_dir($destDir)) {
            if (!@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
                throw new RuntimeException('No se pudo crear el directorio de uploads');
            }
        }
        if (!is_writable($destDir)) {
            throw new RuntimeException('El directorio de uploads no tiene permisos de escritura');
        }
        $destPath = $destDir . '/' . $filename;

        if ($type === 'video') {
            $rawTmp = $destDir . '/.' . $filename . '.src';
            if (!move_uploaded_file($tmpPath, $rawTmp)) {
                throw new RuntimeException('No se pudo guardar el archivo');
            }
            @chmod($rawTmp, 0644);
            try {
                VideoTranscoder::toSignageMp4($rawTmp, $destPath);
            } catch (RuntimeException $e) {
                @unlink($rawTmp);
                @unlink($destPath);
                throw $e;
            }
            @unlink($rawTmp);
            $mime = 'video/mp4';
            $size = (int) (filesize($destPath) ?: $size);
        } else {
            if (!move_uploaded_file($tmpPath, $destPath)) {
                throw new RuntimeException('No se pudo guardar el archivo');
            }
            @chmod($destPath, 0644);
        }

        if ($ext === 'svg') {
            try {
                self::sanitizeSvg($destPath);
            } catch (RuntimeException $e) {
                @unlink($destPath);
                throw $e;
            }
            $mime = 'image/svg+xml';
        }

        // Registrar en BD
        $mediaId = MediaFile::register([
            'filename'      => $filename,
            'original_name' => $originalName,
            'mime_type'     => $mime,
            'file_size'     => $size,
            'file_path'     => $destPath,
            'category'      => $category,
        ], Session::userId());

        $result = [
            'id'        => $mediaId,
            'filename'  => $filename,
            'url'       => self::publicUrl($category, $filename),
            'type'      => $type,
            'mime'      => $mime,
            'size'      => $size,
            'category'  => $category,
        ];
        if ($videoDurationSeconds !== null) {
            $result['duration_seconds'] = $videoDurationSeconds;
        }
        return $result;
    }

    /** Mensaje en español para códigos UPLOAD_ERR_*. */
    public static function uploadErrorMessage(mixed $code): string {
        $maxUpload = ini_get('upload_max_filesize') ?: '?';
        $maxPost = ini_get('post_max_size') ?: '?';
        return match ((int) $code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                "El archivo supera el límite del servidor (upload_max={$maxUpload}, post_max={$maxPost})",
            UPLOAD_ERR_PARTIAL => 'La subida se interrumpió (archivo incompleto)',
            UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal del servidor',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó la subida',
            default => 'Error en la subida del archivo',
        };
    }

    /**
     * Sanea un SVG en disco: quita <script>/<foreignObject>/<iframe>/<embed>/<object>
     * y cualquier atributo on* o href/xlink:href con esquema javascript:. Usa
     * DOMDocument (parseo XML real) en vez de regex — más robusto y además valida
     * que el archivo sea XML bien formado con raíz <svg> (rechaza cualquier cosa
     * que solo tenga la extensión .svg pero no sea realmente un SVG).
     * Ver AUDITORIA_SEGURIDAD_CSET.md hallazgo A2.
     */
    private static function sanitizeSvg(string $path): void {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            throw new RuntimeException('No se pudo leer el archivo SVG.');
        }

        $prevErrors = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // LIBXML_NONET: sin acceso a red durante el parseo. PHP 8+ ya deshabilita
        // la resolución de entidades externas por defecto (mitigación XXE).
        $loaded = $dom->loadXML($content, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);

        $root = $dom->documentElement;
        if (!$loaded || !$root || strtolower($root->localName) !== 'svg') {
            throw new RuntimeException('El archivo no es un SVG válido.');
        }

        foreach (['script', 'foreignObject', 'iframe', 'embed', 'object'] as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            for ($i = $nodes->length - 1; $i >= 0; $i--) {
                $node = $nodes->item($i);
                $node->parentNode?->removeChild($node);
            }
        }

        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//*') as $el) {
            if (!$el instanceof DOMElement) {
                continue;
            }
            $toRemove = [];
            foreach ($el->attributes as $attr) {
                $name = strtolower($attr->name);
                $value = trim($attr->value);
                if (str_starts_with($name, 'on')) {
                    $toRemove[] = $attr->name;
                } elseif (($name === 'href' || $name === 'xlink:href') && preg_match('/^\s*javascript:/i', $value)) {
                    $toRemove[] = $attr->name;
                }
            }
            foreach ($toRemove as $attrName) {
                $el->removeAttribute($attrName);
            }
        }

        $clean = $dom->saveXML($root);
        if ($clean === false || $clean === '') {
            throw new RuntimeException('No se pudo sanear el archivo SVG.');
        }
        if (file_put_contents($path, $clean) === false) {
            throw new RuntimeException('No se pudo guardar el SVG saneado.');
        }
    }

    private static function resolveType(string $ext, string $mime): ?string {
        if (in_array($ext, ALLOWED_IMAGE_TYPES, true)) return 'image';
        if (in_array($ext, ALLOWED_VIDEO_TYPES, true)) return 'video';
        if (in_array($ext, ALLOWED_AUDIO_TYPES, true)) return 'audio';
        if (in_array($ext, ALLOWED_DOC_TYPES, true))   return 'doc';
        return null;
    }

    private static function detectMime(string $path, string $filename): string {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
            return $mime ?: 'application/octet-stream';
        }
        return mime_content_type($path) ?: 'application/octet-stream';
    }

    private static function getMaxSize(string $type): int {
        return match ($type) {
            'image' => MAX_IMAGE_SIZE,
            'video' => MAX_VIDEO_SIZE,
            'audio' => MAX_AUDIO_SIZE,
            'doc'   => MAX_DOC_SIZE,
            default => 0,
        };
    }

    private static function humanSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /** URL relativa APP_BASE/assets/uploads/{category}/{filename}. */
    public static function publicUrl(string $category, string $filename): string {
        // Ruta relativa a la raíz (sin esquema/host): funciona igual sirviéndose
        // por HTTP (cartelera legacy en /) o HTTPS (/cset) sin quedar pegada a
        // un esquema fijo — ver bug de logo roto por mixed-content, 2026-07-27.
        return APP_BASE . '/img/uploads/' . $category . '/' . $filename;
    }

    /**
     * media_files no guarda sector/área por archivo. La biblioteca de medios
     * es solo admin; los editores siguen subiendo archivos desde formularios
     * de contenido (académico/centro), no desde esta biblioteca.
     * Ver AUDITORIA_SEGURIDAD_CSET.md hallazgo M3.
     */
    public static function delete(int $mediaId): bool {
        if (!Session::hasRole('admin')) {
            throw new RuntimeException('Sin permisos');
        }
        return MediaFile::delete($mediaId);
    }
}