<?php
/**
 * Usuarios del panel (tabla users). Roles: admin, program_head (y legado editor).
 *
 * Lo usan public/admin/users/ y UsersController. delete() anula FKs de contenido
 * y no permite borrar la propia cuenta ni al último admin.
 */
class User {

    /**
     * Listado sin password_hash.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array {
        return Database::getInstance()->fetchAll(
            'SELECT id, username, full_name, email, role, program_area, is_active, last_login, created_at
             FROM users ORDER BY created_at DESC'
        );
    }

    /** @return array<string, mixed>|null Incluye password_hash */
    public static function find(int $id): ?array {
        return Database::getInstance()->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByUsername(string $username): ?array {
        return Database::getInstance()->fetch('SELECT * FROM users WHERE username = ?', [$username]);
    }

    /**
     * Hashea $data['password'] a password_hash y lo quita del insert.
     *
     * @param array<string, mixed> $data
     * @return int id
     */
    public static function create(array $data): int {
        $data['password_hash'] = Security::hashPassword($data['password']);
        unset($data['password']);
        $data['created_at'] = date('c');
        return Database::getInstance()->insert('users', $data);
    }

    /**
     * @param array<string, mixed> $data No hashea password; usar changePassword()
     * @return int Filas afectadas
     */
    public static function update(int $id, array $data): int {
        $data['updated_at'] = date('c');
        return Database::getInstance()->update('users', $data, 'id = :id', ['id' => $id]);
    }

    /** bcrypt del texto plano y UPDATE password_hash. */
    public static function changePassword(int $id, string $newPassword): bool {
        $hash = Security::hashPassword($newPassword);
        return Database::getInstance()->update('users',
            ['password_hash' => $hash, 'updated_at' => date('c')],
            'id = :id', ['id' => $id]
        ) > 0;
    }

    public static function toggleActive(int $id): int {
        return Database::getInstance()->query(
            'UPDATE users SET is_active = NOT is_active, updated_at = now() WHERE id = ?',
            [$id]
        )->rowCount();
    }

    public static function countAdmins(): int {
        return (int) Database::getInstance()->fetchColumn(
            "SELECT COUNT(*) FROM users WHERE role = 'admin'"
        );
    }

    /**
     * Motivo para no borrar, o null si se puede.
     *
     * @param array<string, mixed>|null $target
     */
    public static function deletionBlockReason(?array $target, int $actorId, int $adminCount): ?string {
        if ($target === null) {
            return 'Usuario no encontrado.';
        }
        if ((int) $target['id'] === $actorId) {
            return 'No puede eliminar su propia cuenta.';
        }
        if (($target['role'] ?? '') === 'admin' && $adminCount <= 1) {
            return 'No se puede eliminar al último administrador del sistema.';
        }
        return null;
    }

    /**
     * Borra usuario tras detachReferences(). Transacción.
     *
     * @throws RuntimeException Si está bloqueado o falla el DELETE
     */
    public static function delete(int $id, int $actorId): void {
        $target = self::find($id);
        $reason = self::deletionBlockReason($target, $actorId, self::countAdmins());
        if ($reason !== null) {
            throw new RuntimeException($reason);
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            self::detachReferences($id);
            $deleted = $db->delete('users', 'id = :id', ['id' => $id]);
            if ($deleted < 1) {
                throw new RuntimeException('No se pudo eliminar el usuario.');
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException('No se pudo eliminar el usuario.', 0, $e);
        }
    }

    /** Pone created_by / uploaded_by / user_id a NULL en tablas que referencian al usuario. */
    private static function detachReferences(int $id): void {
        $db = Database::getInstance();
        $updates = [
            'UPDATE academic_content SET created_by = NULL WHERE created_by = ?',
            'UPDATE center_content SET created_by = NULL WHERE created_by = ?',
            'UPDATE general_content SET created_by = NULL WHERE created_by = ?',
            'UPDATE gallery_content SET created_by = NULL WHERE created_by = ?',
            'UPDATE media_files SET uploaded_by = NULL WHERE uploaded_by = ?',
            'UPDATE display_config SET updated_by = NULL WHERE updated_by = ?',
            'UPDATE activity_log SET user_id = NULL WHERE user_id = ?',
        ];
        foreach ($updates as $sql) {
            $db->query($sql, [$id]);
        }
    }
}