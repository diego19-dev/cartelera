<?php

class AuthController extends BaseController {

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->fail('Método no permitido', 405);
        }
        $body = $this->jsonInput();
        $username = trim($body['username'] ?? $_POST['username'] ?? '');
        $password = $body['password'] ?? $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $this->fail('Usuario y contraseña requeridos', 422);
        }

        // Verificar bloqueo por IP ANTES de tocar la BD de usuarios — igual patrón que
        // fun_verificar_bloqueo_ip de SmartCSET. Real por IP (login_attempts), no por
        // sesión PHP. Ver AUDITORIA_SEGURIDAD_CSET.md hallazgo C3.
        if (LoginAttempt::isBlocked(Security::clientIp(), LOGIN_MAX_ATTEMPTS, LOGIN_WINDOW_MINUTES)) {
            $this->fail(
                'Demasiados intentos fallidos. Intente de nuevo en ' . LOGIN_WINDOW_MINUTES . ' minutos.',
                429
            );
        }

        $user = Auth::attempt($username, $password);
        if (!$user) {
            usleep(500000);
            $this->fail('Credenciales inválidas', 401);
        }

        $this->ok([
            'user' => Auth::userPayload(),
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function logout(): void {
        $this->requireAuth();
        $this->requireCsrf();
        Auth::logout();
        $this->ok(['message' => 'Sesión cerrada']);
    }

    public function me(): void {
        if (!Session::isAuthenticated()) {
            $this->fail('No autenticado', 401);
        }
        $this->ok([
            'user' => Auth::userPayload(),
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function csrf(): void {
        $this->ok(['csrf' => Security::csrfToken()]);
    }
}
