<?php
/**
 * Modelo User - Gestión de usuarios
 */

class User {

    public static function all(): array {
        return Database::getInstance()->fetchAll(
            'SELECT id, username, full_name, email, role, program_area, is_active, last_login, created_at
             FROM users ORDER BY created_at DESC'
        );
    }

    public static function find(int $id): ?array {
        return Database::getInstance()->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByUsername(string $username): ?array {
        return Database::getInstance()->fetch('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public static function create(array $data): int {
        $data['password_hash'] = Security::hashPassword($data['password']);
        unset($data['password']);
        $data['created_at'] = date('c');
        return Database::getInstance()->insert('users', $data);
    }

    public static function update(int $id, array $data): int {
        $data['updated_at'] = date('c');
        return Database::getInstance()->update('users', $data, 'id = :id', ['id' => $id]);
    }

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
}