<?php
/**
 * Autenticación de panel y API: login, sesión, roles y payload de usuario.
 *
 * Lo usan public/login.php, AuthController y auth_guard del panel. El rate limit
 * por IP (LoginAttempt) se comprueba ANTES de attempt(), para distinguir
 * "bloqueado" de "credenciales inválidas".
 */
class Auth {

    /**
     * Intenta login con username + password y abre sesión si es válido.
     *
     * Registra en login_attempts todo intento (exista o no el usuario). Rehash
     * bcrypt si el coste cambió. Side effect: Session + last_login en users.
     *
     * @param string $username
     * @param string $password Texto plano; nunca se sanitiza con htmlspecialchars
     * @return array<string, mixed>|null Fila de users o null si falla
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

    /** Copia id, nombre, rol y área de programa a la sesión PHP (cookie cset_session). */
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
     * Cierra sesión PHP, borra cookie y token en user_sessions.
     *
     * @return void
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
     * Exige sesión para páginas HTML; si no hay, redirige a login y termina.
     *
     * @param string $redirectTo
     * @return void
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
     * Exige uno de los roles; si falla, responde 403 HTML y termina.
     *
     * @param string|array<int, string> $roles
     * @param string $redirectTo Reservado (hoy se muestra 403, no redirect)
     * @return void
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
     * Refresca rol, área y nombre desde users (la sesión puede quedar vieja).
     *
     * @return void
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
     * True si el usuario puede editar contenido de esa área de programa.
     *
     * admin/editor: todo. program_head: solo su program_area (vacío = sí).
     *
     * @param string|null $area Clave de PROGRAM_AREAS o null
     * @return bool
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
     * Exige sesión en API; si no hay, JSON 401 y termina.
     *
     * @return void
     */
    public static function requireAuthApi(): void {
        if (!Session::isAuthenticated()) {
            Security::jsonResponse(['ok' => false, 'error' => 'No autenticado'], 401);
        }
    }

    /**
     * Exige rol en API; JSON 403 si no coincide.
     *
     * @param string|array<int, string> $roles
     * @return void
     */
    public static function requireRoleApi(string|array $roles): void {
        self::requireAuthApi();
        if (!Session::hasRole($roles)) {
            Security::jsonResponse(['ok' => false, 'error' => 'Acceso denegado'], 403);
        }
    }

    /**
     * Usuario de sesión sin password_hash, para GET /api/auth/me.
     *
     * @return array{id: ?int, username: mixed, full_name: mixed, role: ?string, program_area: ?string, email: mixed}
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
     * Fila actual de users desde BD, o null si no hay sesión.
     *
     * @return array<string, mixed>|null
     */
    public static function user(): ?array {
        $id = Session::userId();
        if (!$id) return null;
        return Database::getInstance()->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }
}