<?php
/**
 * Helpers de vistas del panel PHP.
 */
function e(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never {
    if (!str_starts_with($path, 'http') && !str_starts_with($path, '/')) {
        $path = APP_BASE . '/' . ltrim($path, '/');
    }
    header('Location: ' . $path);
    exit;
}

function flash_set(string $type, string $message): void {
    Session::flash('flash_type', $type);
    Session::flash('flash_message', $message);
}

function flash_get(): ?array {
    $msg = Session::flash('flash_message');
    if ($msg === null || $msg === '') {
        return null;
    }
    return [
        'type' => (string) (Session::flash('flash_type') ?: 'info'),
        'message' => (string) $msg,
    ];
}

function nav_active(string $needle): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, $needle) ? 'is-active' : '';
}

function panel_theme(): string {
    $t = strtolower((string) ($_COOKIE['cset_theme'] ?? 'light'));
    return $t === 'dark' ? 'dark' : 'light';
}

function role_label(?string $role): string {
    return match ($role) {
        'admin' => 'Administrador del Sistema',
        'editor' => 'Editor',
        'program_head' => 'Jefe de programa',
        'viewer' => 'Solo lectura',
        default => $role ?: 'Usuario',
    };
}

function icon(string $name): string {
    $svg = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V21h13V9.5"/>',
        'spark' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><circle cx="12" cy="12" r="4"/>',
        'academic' => '<path d="M3 10 12 5l9 5-9 5-9-5z"/><path d="M6 12.5V17c2 1.5 4 2.2 6 2.2S16 18.5 18 17v-4.5"/>',
        'building' => '<path d="M4 21V7l8-4 8 4v14"/><path d="M9 21v-6h6v6"/><path d="M9 10h.01M15 10h.01M12 10h.01"/>',
        'news' => '<path d="M4 5h12v14H4z"/><path d="M16 8h4v11a2 2 0 0 1-2 2H4"/><path d="M7 9h6M7 13h6M7 17h3"/>',
        'images' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 15-4.5-4.5L6 21"/>',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3 2.5-5 6-5s6 2 6 5"/><circle cx="17" cy="9" r="2.2"/><path d="M21 20c0-2.2-1.5-3.8-3.5-4.4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M4.9 6.5l1.4 1.4M17.7 16.1l1.4 1.4M3 12h2M19 12h2M4.9 17.5l1.4-1.4M17.7 7.9l1.4-1.4"/>',
        'tv' => '<rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8M12 17v4"/>',
        'moon' => '<path d="M20 14.5A8 8 0 1 1 9.5 4 6.5 6.5 0 0 0 20 14.5z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M3 12h2M19 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/>',
        'logout' => '<path d="M10 17v2a2 2 0 0 0 2 2h7V3h-7a2 2 0 0 0-2 2v2"/><path d="M15 12H3m0 0 3-3M3 12l3 3"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
    ];
    $body = $svg[$name] ?? '';
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

function format_activity_when(?string $iso): string {
    if ($iso === null || $iso === '') {
        return '';
    }
    try {
        $dt = (new DateTimeImmutable($iso))->setTimezone(new DateTimeZone('America/Bogota'));
        return $dt->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $iso;
    }
}

function cset_url(string $path = ''): string {
    $base = rtrim(APP_BASE, '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function cset_can_manage_users(): bool {
    return Session::hasRole('admin');
}

function cset_can_manage_config(): bool {
    return Session::hasRole('admin');
}

function cset_can_manage_general(): bool {
    return Session::hasRole('admin');
}

function cset_can_manage_media(): bool {
    return Session::hasRole('admin');
}

/** Roles disponibles al crear o editar usuarios. */
function cset_assignable_roles(): array {
    return [
        'admin' => 'Admin',
        'program_head' => 'Jefe de programa',
    ];
}

/** Opciones de rol para el formulario (incluye rol legacy al editar). */
function cset_user_role_options(?string $currentRole = null): array {
    $roles = cset_assignable_roles();
    $currentRole = trim((string) $currentRole);
    if ($currentRole !== '' && !isset($roles[$currentRole])) {
        $roles[$currentRole] = role_label($currentRole) . ' (deprecado)';
    }
    return $roles;
}

/**
 * Valida rol y área antes de guardar un usuario.
 *
 * @return array{role: string, program_area: ?string}
 */
function cset_validate_user_fields(string $role, ?string $programArea): array {
    $role = trim($role);
    if (!in_array($role, PANEL_ASSIGNABLE_ROLES, true)) {
        throw new RuntimeException('Rol no permitido.');
    }
    $programArea = $programArea !== null && trim($programArea) !== '' ? trim($programArea) : null;
    if ($role === 'program_head' && $programArea === null) {
        throw new RuntimeException('Los jefes de programa deben tener un área asignada.');
    }
    if ($role === 'admin') {
        $programArea = null;
    }
    return ['role' => $role, 'program_area' => $programArea];
}

/**
 * Área de programa al guardar contenido académico.
 * Jefes de programa siempre usan su sector; admin conserva el existente.
 */
function cset_academic_program_area(?array $item): ?string {
    if ($item !== null && array_key_exists('program_area', $item)) {
        $existing = trim((string) ($item['program_area'] ?? ''));
        if ($existing !== '') {
            return $existing;
        }
    }
    if (Session::hasRole('program_head') && !Session::hasRole('admin')) {
        $area = Session::userProgramArea();
        return $area !== null && trim($area) !== '' ? trim($area) : null;
    }
    return null;
}

/** Listado académico del panel: admin ve todo; demás usuarios solo lo que subieron. */
function cset_academic_list_for_user(): array {
    $items = AcademicContent::all();
    if (Session::hasRole('admin')) {
        return $items;
    }
    $userId = Session::userId();
    if (!$userId) {
        return [];
    }
    return array_values(array_filter(
        $items,
        static fn(array $item): bool => (int) ($item['created_by'] ?? 0) === $userId
    ));
}

/** Puede ver/editar/eliminar un ítem académico en el panel. */
function cset_can_manage_academic_item(?array $item): bool {
    if (!$item) {
        return false;
    }
    if (Session::hasRole('admin')) {
        return true;
    }
    $userId = Session::userId();
    return $userId !== null && (int) ($item['created_by'] ?? 0) === $userId;
}

/** Módulos del panel deshabilitados (sin entrada en menú ni CRUD). */
function cset_panel_module_enabled(string $module): bool {
    return !in_array($module, PANEL_DISABLED_MODULES, true);
}

function cset_require_panel_module(string $module): void {
    if (!cset_panel_module_enabled($module)) {
        redirect(cset_url('admin/dashboard.php'));
    }
}

function cset_nav_key(): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $pairs = [
        '/admin/dashboard' => 'dashboard',
        '/admin/welcome' => 'bienvenida',
        '/admin/academic' => 'academic',
        '/admin/center' => 'center',
        '/admin/general' => 'general',
        '/admin/gallery' => 'gallery',
        '/admin/media' => 'media',
        '/admin/users' => 'users',
        '/admin/config' => 'config',
    ];
    foreach ($pairs as $needle => $key) {
        if (str_contains($uri, $needle)) {
            return $key;
        }
    }
    return '';
}

function cset_nav_active(string $key): string {
    return cset_nav_key() === $key ? 'bg-white/15 font-semibold' : '';
}

function cset_format_activity_at($value): string {
    return format_activity_when($value === null ? null : (string) $value);
}

function cset_branding(): array {
    try {
        return DisplayConfig::getAllForTv();
    } catch (Throwable $e) {
        return [];
    }
}

require_once __DIR__ . '/icons.php';
