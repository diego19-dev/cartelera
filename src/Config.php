<?php
/**
 * Constantes de configuración CSET-TV (DB, URLs, roles, límites de media).
 *
 * Lo carga bootstrap.php tras EnvLoader. Secretos (DB_PASS, SECRET_KEY) solo
 * desde .env — no versionar. APP_BASE por defecto es /tv (Alias Apache → raíz del repo).
 *
 * PANEL_DISABLED_MODULES oculta center/gallery del panel; TvController sigue
 * sirviendo ese contenido. PANEL_ASSIGNABLE_ROLES limita altas de usuarios.
 *
 * @see EnvLoader::load()
 * @see docs/AUDITORIA_SEGURIDAD_CSET.md
 */

// =====================================================
// CONFIGURACIÓN DE BASE DE DATOS
// =====================================================
define('DB_HOST',    EnvLoader::get('DB_HOST', '10.5.213.103'));
define('DB_PORT',    EnvLoader::get('DB_PORT', '5432'));
define('DB_NAME',    EnvLoader::get('DB_NAME', 'db_cset'));
define('DB_USER',    EnvLoader::get('DB_USER', 'postgres'));
define('DB_PASS',    EnvLoader::get('DB_PASS', ''));
define('DB_SCHEMA',  EnvLoader::get('DB_SCHEMA', 'public'));

if (DB_PASS === '') {
    error_log('[CSET] ADVERTENCIA: DB_PASS no configurada (.env ausente o incompleto).');
}

// =====================================================
// CONFIGURACIÓN DE LA APLICACIÓN
// =====================================================
define('APP_NAME',   'CSET-TV Cartelera Digital');
define('APP_ENV',    EnvLoader::get('APP_ENV', 'production'));
define('APP_DEBUG',  EnvLoader::get('APP_DEBUG', APP_ENV === 'development' ? '1' : '0') === '1');
define('APP_URL',    EnvLoader::get('APP_URL', 'http://10.5.213.103/tv'));
define('APP_BASE',   EnvLoader::get('APP_BASE', '/tv'));
define('APP_TIMEZONE','America/Bogota');          // Ajustar según país

// =====================================================
// SEGURIDAD
// =====================================================
define('SECRET_KEY',     EnvLoader::get('SECRET_KEY', ''));
if (SECRET_KEY === '') {
    error_log('[CSET] ADVERTENCIA: SECRET_KEY no configurada (.env ausente o incompleto).');
}
define('SESSION_NAME',   'cset_session');
define('SESSION_LIFETIME', 7200);                // 2 horas

// Rate limiting de login (E3 — ver AUDITORIA_SEGURIDAD_CSET.md hallazgo C3)
define('LOGIN_MAX_ATTEMPTS',   max(1, (int) EnvLoader::get('LOGIN_MAX_ATTEMPTS', '5')));
define('LOGIN_WINDOW_MINUTES', max(1, (int) EnvLoader::get('LOGIN_WINDOW_MINUTES', '15')));
// ISO-8601 opcional: si está en el futuro, no se aplica el bloqueo por IP (p. ej. ventana de 4 h).
define('LOGIN_RATE_LIMIT_DISABLED_UNTIL', trim((string) EnvLoader::get('LOGIN_RATE_LIMIT_DISABLED_UNTIL', '')));
// COOKIE_SECURE por .env (COOKIE_SECURE=1), default 0 — activar recién cuando TLS
// esté sirviendo /tv (E4). Si se pone en 1 sin TLS, el login se rompe (el
// navegador nunca envía cookies "Secure" por HTTP). Ver AUDITORIA_SEGURIDAD_CSET.md C5.
define('COOKIE_SECURE',   EnvLoader::get('COOKIE_SECURE', '0') === '1');
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Strict');
define('BCRYPT_COST',     12);

// =====================================================
// RUTAS
// =====================================================
// __DIR__ = src → BASE_PATH = raíz del repo
define('BASE_PATH',    dirname(__DIR__));
define('PUBLIC_PATH',  BASE_PATH);
define('UPLOAD_PATH',  BASE_PATH . '/img/uploads');
define('LOG_PATH',     BASE_PATH . '/logs');
define('CACHE_PATH',   BASE_PATH . '/logs/cache');

// =====================================================
// MEDIOS PERMITIDOS
// =====================================================
define('ALLOWED_IMAGE_TYPES',  ['jpg','jpeg','png','gif','webp','svg']);
define('ALLOWED_VIDEO_TYPES',  ['mp4','webm','ogg','mov']);
define('ALLOWED_AUDIO_TYPES',  ['mp3','wav','ogg','m4a']);
define('ALLOWED_DOC_TYPES',    ['pdf']);

define('MAX_IMAGE_SIZE', 10 * 1024 * 1024);   // 10 MB
define('MAX_VIDEO_SIZE', 500 * 1024 * 1024);  // 500 MB
define('MAX_AUDIO_SIZE', 50 * 1024 * 1024);   // 50 MB
define('MAX_DOC_SIZE',   20 * 1024 * 1024);   // 20 MB

/** Tope de permanencia del slide y de duración real del archivo de video (10 min). */
define('MAX_SLIDE_DURATION_SECONDS', 600);
define('MAX_VIDEO_DURATION_SECONDS', 600);
define('MIN_SLIDE_DURATION_SECONDS', 5);

// =====================================================
// SEGMENTOS (sectores del centro)
// =====================================================
define('PROGRAM_AREAS', [
    'empresarial' => 'Empresarial',
    'salud'       => 'Salud',
    'tecnologia'  => 'Tecnología',
    'hoteleria'   => 'Hotelería',
    'turismo'     => 'Turismo',
    'barismo'     => 'Barismo',
    'eventos'     => 'Gestión de Eventos',
]);

/** Módulos ocultos del panel (sin menú ni CRUD). La TV sigue sirviendo su contenido. */
define('PANEL_DISABLED_MODULES', ['center', 'gallery']);

/** Roles asignables al crear/editar usuarios desde el panel. */
define('PANEL_ASSIGNABLE_ROLES', ['admin', 'program_head']);

define('CONTENT_CATEGORIES', [
    'news'         => 'Noticias',
    'event'        => 'Eventos',
    'announcement' => 'Anuncios',
    'currency'     => 'Divisas',
    'commodity'    => 'Materias Primas',
    'sports'       => 'Deportes',
    'weather'      => 'Clima',
    'institutional'=> 'Institucional',
]);

define('GENERAL_ICONS', [
    'dollar-sign',
    'trending-up',
    'cloud',
    'thermometer',
    'newspaper',
    'calendar',
    'megaphone',
    'building-2',
    'landmark',
    'trophy',
    'droplets',
    'bell',
    'info',
    'bitcoin',
]);

define('GENERAL_ICON_LABELS', [
    'dollar-sign'  => 'Dólar',
    'trending-up'  => 'Tendencia',
    'bitcoin'      => 'Cripto',
    'cloud'        => 'Clima',
    'thermometer'  => 'Temperatura',
    'newspaper'    => 'Noticia',
    'calendar'     => 'Evento',
    'megaphone'    => 'Anuncio',
    'building-2'   => 'Institucional',
    'landmark'     => 'Edificio',
    'trophy'       => 'Deportes',
    'droplets'     => 'Líquidos',
    'bell'         => 'Alerta',
    'info'         => 'Info',
]);

// =====================================================
// APIs EXTERNAS (opcional - para datos en tiempo real)
// =====================================================
define('EXCHANGE_API_URL',  'https://api.exchangerate-api.com/v4/latest/USD');
define('COFFEE_API_URL',    '');   // Configurar según proveedor
define('OIL_API_URL',       '');   // Configurar según proveedor
define('WEATHER_API_URL',   '');   // OpenWeatherMap u otro

// =====================================================
// ERROR REPORTING
// =====================================================
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOG_PATH . '/php-errors.log');
}

// =====================================================
// TIMEZONE
// =====================================================
date_default_timezone_set(APP_TIMEZONE);

