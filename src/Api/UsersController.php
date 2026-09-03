<?php

class UsersController extends BaseController {

    public function index(): void {
        $this->requireAdmin();
        $items = User::all();
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $items = array_filter($items, fn($u) =>
                stripos($u['username'], $q) !== false ||
                stripos($u['full_name'], $q) !== false
            );
        }
        $this->ok(['items' => array_values($items)]);
    }

    public function show(int $id): void {
        $this->requireAdmin();
        $user = User::find($id);
        if (!$user) $this->fail('No encontrado', 404);
        unset($user['password_hash']);
        $this->ok(['item' => $user]);
    }

    public function store(): void {
        $this->requireAdmin();
        $this->requireCsrf();
        $data = Security::sanitizeArray($this->jsonInput());
        if (empty($data['username']) || empty($data['password']) || empty($data['full_name'])) {
            $this->fail('Campos requeridos incompletos', 422);
        }
        $username = trim((string) $data['username']);
        if ($username === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->fail('Usuario de ingreso inválido', 422);
        }
        if (!Security::isStrongPassword($data['password'])) {
            $this->fail('Contraseña débil', 422);
        }
        if (User::findByUsername($username)) {
            $this->fail('El usuario ya existe', 409);
        }
        try {
            $validated = cset_validate_user_fields(
                (string) ($data['role'] ?? 'program_head'),
                !empty($data['program_area']) ? (string) $data['program_area'] : null
            );
        } catch (RuntimeException $e) {
            $this->fail($e->getMessage(), 422);
        }
        $id = User::create([
            'username'     => $username,
            'password'     => $data['password'],
            'full_name'    => $data['full_name'],
            'email'        => $data['email'] ?? null,
            'role'         => $validated['role'],
            'program_area' => $validated['program_area'],
        ]);
        Logger::log(Session::userId(), 'create_user', 'users', $id);
        $user = User::find($id);
        unset($user['password_hash']);
        $this->ok(['item' => $user], 201);
    }

    public function update(int $id): void {
        $this->requireAdmin();
        $this->requireCsrf();
        if (!User::find($id)) $this->fail('No encontrado', 404);
        $data = Security::sanitizeArray($this->jsonInput());

        $username = trim((string) ($data['username'] ?? ''));
        if ($username === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->fail('Usuario de ingreso inválido', 422);
        }
        $existing = User::findByUsername($username);
        if ($existing && (int) $existing['id'] !== $id) {
            $this->fail('El usuario ya existe', 409);
        }

        try {
            $validated = cset_validate_user_fields(
                (string) ($data['role'] ?? 'program_head'),
                !empty($data['program_area']) ? (string) $data['program_area'] : null
            );
        } catch (RuntimeException $e) {
            $this->fail($e->getMessage(), 422);
        }

        $update = [
            'username'     => $username,
            'full_name'    => trim((string) ($data['full_name'] ?? '')),
            'email'        => $data['email'] ?? null,
            'role'         => $validated['role'],
            'program_area' => $validated['program_area'],
        ];
        if ($update['full_name'] === '') {
            $this->fail('El nombre completo es obligatorio', 422);
        }
        if (!empty($data['password'])) {
            if (!Security::isStrongPassword($data['password'])) {
                $this->fail('Contraseña débil', 422);
            }
            User::changePassword($id, $data['password']);
        }
        User::update($id, $update);
        if ($id === Session::userId()) {
            Session::set('user_username', $username);
            Session::set('user_role', $data['role']);
            Session::set('user_program_area', $update['program_area']);
        }
        Logger::log(Session::userId(), 'update_user', 'users', $id);
        $user = User::find($id);
        unset($user['password_hash']);
        $this->ok(['item' => $user]);
    }

    public function toggle(int $id): void {
        $this->requireAdmin();
        $this->requireCsrf();
        $existing = User::find($id);
        if (!$existing) $this->fail('No encontrado', 404);
        if ($id === Session::userId() && filter_var($existing['is_active'], FILTER_VALIDATE_BOOLEAN)) {
            $this->fail('No puede desactivar su propia cuenta', 422);
        }
        User::toggleActive($id);
        Logger::log(Session::userId(), 'toggle_user', 'users', $id);
        $user = User::find($id);
        unset($user['password_hash']);
        $this->ok(['item' => $user]);
    }
}
