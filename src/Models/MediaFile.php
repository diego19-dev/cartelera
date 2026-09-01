<?php
/**
 * Modelo MediaFile - Gestión de archivos multimedia
 */

class MediaFile {

    /**
     * Registra un archivo subido
     */
    public static function register(array $fileData, int $userId): int {
        $data = [
            'filename'      => $fileData['filename'],
            'original_name' => $fileData['original_name'] ?? null,
            'mime_type'     => $fileData['mime_type'] ?? null,
            'file_size'     => $fileData['file_size'] ?? 0,
            'file_path'     => $fileData['file_path'],
            'category'      => $fileData['category'] ?? null,
            'width'         => $fileData['width'] ?? null,
            'height'        => $fileData['height'] ?? null,
            'duration'      => $fileData['duration'] ?? null,
            'uploaded_by'   => $userId,
            'uploaded_at'   => date('c'),
        ];
        return Database::getInstance()->insert('media_files', $data);
    }

    public static function find(int $id): ?array {
        return Database::getInstance()->fetch('SELECT * FROM media_files WHERE id = ?', [$id]);
    }

    public static function delete(int $id): bool {
        $file = self::find($id);
        if ($file && file_exists($file['file_path'])) {
            @unlink($file['file_path']);
        }
        return Database::getInstance()->delete('media_files', 'id = :id', ['id' => $id]) > 0;
    }

    public static function all(int $limit = 100): array {
        return Database::getInstance()->fetchAll(
            'SELECT m.*, u.full_name AS uploader_name
             FROM media_files m
             LEFT JOIN users u ON u.id = m.uploaded_by
             ORDER BY m.uploaded_at DESC LIMIT ?',
            [$limit]
        );
    }
}