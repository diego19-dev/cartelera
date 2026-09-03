<?php
/**
 * Clase Auth - Autenticación con base de datos
 */

class Auth {

    /**
     * Intenta login con username + password.
     * El bloqueo por IP se verifica antes de llamar aquí (AuthController::login()),
     * para poder devolver un mensaje distinto de "credenciales inválidas". Esta función
     * registra en login_attempts TODO intento — exista o no el usuario — a diferencia
     * del diseño original que solo auditaba usuarios existentes.
     */
    public static function attempt(string $username, string $password): ?array {
        $db = Database::getInstance();
        $ip = Security::clientIp();
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $user = $db->fetch('SELECT * FROM users WHERE username = ?', [$username]);

        if (!$user) {
            LoginAttempt::record($ip, $username, false, 'usuario_no_existe', $userAgent);
            return null;
        }

        if (!filter_var($user['is_active'], FILTER_VALIDATE_BOOLEAN)) {
            LoginAttempt::record($ip, $username, false, 'usuario_inactivo', $userAgent);
            return null;
        }

        if (!Security::verifyPassword($password, $user['password_hash'])) {
            LoginAttempt::record($ip, $username, false, 'contrasena_incorrecta', $userAgent);
            Logger::log($user['id'], 'login_failed', 'users', $user['id']);
            return null;
        }

        // Rehash si es necesario
        if (Security::needsRehash($user['password_hash'])) {
            $newHash = Security::hashPassword($password);
            $db->update('users',
                ['password_hash' => $newHash, 'updated_at' => date('c')],
                'id = :id', ['id' => $user['id']]
            );
        }

        // Crear sesión
        self::createSession($user);

        // Actualizar último login
        $db->update('users',
            ['last_login' => date('c')],
            'id = :id', ['id' => $user['id']]
        );

        LoginAttempt::record($ip, $username, true, null, $userAgent);
        Logger::log($user['id'], 'login_success', 'users', $user['id']);

        return $user;
    }

    private static function createSession(array $user): void {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('user_name', $user['full_name']);
        Session::set('user_username', $user['username']);
        Session::set('user_role', $user['role']);
        Session::set('user_program_area', $user['program_area']);
        Session::set('user_email', $user['email']);
    }

    /**
     * Cierra sesión
     */
    public static function logout(): void {
        $userId = Session::userId();
        if ($userId) {
            Logger::log($userId, 'logout', 'users', $userId);
        }
        // Eliminar sesión en BD si existe token
        $token = $_COOKIE[SESSION_NAME] ?? null;
        if ($token) {
            Database::getInstance()->delete('user_sessions', 'token = :token', ['token' => $token]);
        }
        Session::destroy();
    }

    /**
     * Verifica autenticación o redirige
     */
    public static function requireAuth(string $redirectTo = APP_BASE . '/login.php'): void {
        if (!Session::isAuthenticated()) {
            // Intentar redirect, sino mostrar mensaje
            if (!headers_sent()) {
                header('Location: ' . $redirectTo);
            }
            // Siempre terminar ejecución
            exit;
        }
    }

    /**
     * Verifica rol específico
     */
    public static function requireRole(string|array $roles, string $redirectTo = APP_BASE . '/admin/dashboard.php'): void {
        self::requireAuth();
        if (!Session::hasRole($roles)) {
            if (!headers_sent()) {
                http_response_code(403);
            }
            $home = APP_BASE . '/admin/dashboard.php';
            echo <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Acceso denegado</title>
<style>body{font-family:sans-serif;background:#0a0e27;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center}
.box{background:#1e293b;padding:40px;border-radius:12px;max-width:500px}
h1{color:#fbbf24;font-size:48px;margin:0}p{color:#cbd5e1}
a{color:#fbbf24;text-decoration:none;border:1px solid #fbbf24;padding:10px 20px;border-radius:6px;display:inline-block;margin-top:20px}</style></head>
<body><div class="box"><h1>403</h1><p>Acceso denegado. No tiene permisos suficientes.</p>
<a href="{$home}">Volver al inicio</a></div></body></html>
HTML;
            exit;
        }
    }

    /**
     * Sincroniza rol y área de programa desde BD (sesión puede quedar desactualizada).
     */
    public static function syncSessionFromDb(): void {
        $id = Session::userId();
        if (!$id) {
            return;
        }
        $user = Database::getInstance()->fetch(
            'SELECT role, program_area, full_name, is_active FROM users WHERE id = ?',
            [$id]
        );
        if (!$user || !filter_var($user['is_active'], FILTER_VALIDATE_BOOLEAN)) {
            return;
        }
        Session::set('user_role', $user['role']);
        Session::set('user_program_area', $user['program_area']);
        Session::set('user_name', $user['full_name']);
    }

    /**
     * Verifica que el usuario puede editar un sector
     */
    public static function canEditArea(?string $area): bool {
        if (Session::hasRole(['admin', 'editor'])) {
            return true;
        }
        if (!Session::hasRole('program_head')) {
            return false;
        }
        $userArea = Session::userProgramArea();
        if ($userArea === null || trim($userArea) === '') {
            return false;
        }
        $area = $area !== null ? trim($area) : '';
        if ($area === '') {
            return true;
        }
        return $area === $userArea;
    }

    /**
     * Verifica autenticación para API (JSON 401)
     */
    public static function requireAuthApi(): void {
        if (!Session::isAuthenticated()) {
            Security::jsonResponse(['ok' => false, 'error' => 'No autenticado'], 401);
        }
    }

    /**
     * Verifica rol para API (JSON 403)
     */
    public static function requireRoleApi(string|array $roles): void {
        self::requireAuthApi();
        if (!Session::hasRole($roles)) {
            Security::jsonResponse(['ok' => false, 'error' => 'Acceso denegado'], 403);
        }
    }

    /**
     * Usuario actual como array seguro para API
     */
    public static function userPayload(): array {
        return [
            'id'           => Session::userId(),
            'username'     => Session::get('user_username'),
            'full_name'    => Session::get('user_name'),
            'role'         => Session::userRole(),
            'program_area' => Session::userProgramArea(),
            'email'        => Session::get('user_email'),
        ];
    }

    /**
     * Obtiene el usuario actual desde BD (refrescado)
     */
    public static function user(): ?array {
        $id = Session::userId();
        if (!$id) return null;
        return Database::getInstance()->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }
}