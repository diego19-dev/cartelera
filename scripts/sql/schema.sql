-- =============================================================
--  IES-TV :: Base de datos PostgreSQL
--  Sistema de cartelería digital para el CSET - SENA
--  PostgreSQL 16+
-- =============================================================

-- Eliminar si existe (solo en entorno de desarrollo)
DROP TABLE IF EXISTS activity_log CASCADE;
DROP TABLE IF EXISTS media_files CASCADE;
DROP TABLE IF EXISTS gallery_content CASCADE;
DROP TABLE IF EXISTS general_content CASCADE;
DROP TABLE IF EXISTS center_content CASCADE;
DROP TABLE IF EXISTS academic_content CASCADE;
DROP TABLE IF EXISTS display_config CASCADE;
DROP TABLE IF EXISTS user_sessions CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- =====================================================
-- USUARIOS DEL SISTEMA
-- =====================================================
CREATE TABLE users
(
    id                  SERIAL PRIMARY KEY,
    username            VARCHAR(50) UNIQUE NOT NULL,
    password_hash       VARCHAR(255) NOT NULL,
    full_name           VARCHAR(200) NOT NULL,
    email               VARCHAR(150),
    role                VARCHAR(20) NOT NULL CHECK (role IN ('admin','program_head','editor')),
    program_area        VARCHAR(50),  -- sectores que puede administrar
    is_active           BOOLEAN DEFAULT TRUE,
    last_login          TIMESTAMPTZ,
    created_at          TIMESTAMPTZ DEFAULT now(),
    updated_at          TIMESTAMPTZ DEFAULT now()
);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role     ON users(role);

COMMENT ON TABLE users IS 'Usuarios autenticados que cargan contenido en las TVs';

-- =====================================================
-- SESIONES
-- =====================================================
CREATE TABLE user_sessions
(
    id                  SERIAL PRIMARY KEY,
    user_id             INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token               VARCHAR(255) UNIQUE NOT NULL,
    ip_address          INET,
    user_agent          TEXT,
    last_activity       TIMESTAMPTZ DEFAULT now(),
    expires_at          TIMESTAMPTZ NOT NULL,
    created_at          TIMESTAMPTZ DEFAULT now()
);
CREATE INDEX idx_sessions_token ON user_sessions(token);
CREATE INDEX idx_sessions_user ON user_sessions(user_id);

-- =====================================================
-- CONFIGURACIÓN DE PANTALLAS
-- =====================================================
CREATE TABLE display_config
(
    id                  SERIAL PRIMARY KEY,
    config_key          VARCHAR(100) UNIQUE NOT NULL,
    config_value        TEXT,
    description         TEXT,
    updated_at          TIMESTAMPTZ DEFAULT now(),
    updated_by          INT REFERENCES users(id)
);

-- Valores por defecto
INSERT INTO display_config (config_key, config_value, description)
VALUES ('tv_rotation_seconds',     '20',           'Segundos por cada slide'),
       ('tv_scenario_order',       'academic,center,general,gallery', 'Orden de rotación de escenarios'),
       ('tv_academic_duration',    '15',           'Segundos por slide académico'),
       ('tv_center_duration',      '15',           'Segundos por slide de Nuestro Centro'),
       ('tv_general_duration',     '10',           'Segundos por slide general'),
       ('tv_show_clock',           '1',            'Mostrar reloj (1=si, 0=no)'),
       ('tv_show_weather',         '1',            'Mostrar clima (1=si, 0=no)'),
       ('tv_branding_title',       'IES - Cartelera Digital', 'Título principal'),
       ('tv_branding_subtitle',    'Formación para el trabajo', 'Subtítulo'),
       ('tv_fullscreen',           '1',            'Auto fullscreen al cargar'),
       ('site_name',               'IES',          'Nombre de la Institución'),
       ('api_poll_seconds',        '30',           'Frecuencia de polling para tiempo real'),
       ('refresh_external_minutes','30',           'Frecuencia de actualización de datos externos'),
       ('tv_enabled',              '1',            'Cartelera encendida (1) o apagada (0)'),
       ('tv_off_message',          'Cartelera apagada', 'Mensaje cuando la cartelera está apagada');

-- =====================================================
-- CONTENIDO ACADÉMICO
-- =====================================================
CREATE TABLE academic_content
(
    id                  SERIAL PRIMARY KEY,
    title               VARCHAR(255) NOT NULL,
    description         TEXT,
    program_type        VARCHAR(30) CHECK (program_type IN ('annual','quarterly','specific','announcement')),
    program_area        VARCHAR(50),  -- empresarial, salud, tecnologia, hoteleria, turismo, barismo, eventos
    start_date          DATE,
    end_date            DATE,
    media_type          VARCHAR(20) CHECK (media_type IN ('image','video','text','pdf')),
    media_path          VARCHAR(500),
    thumbnail_path      VARCHAR(500),
    duration_seconds    INT DEFAULT 15,
    display_order       INT DEFAULT 0,
    is_active           BOOLEAN DEFAULT TRUE,
    show_from           TIMESTAMPTZ,  -- desde cuándo mostrar
    show_until          TIMESTAMPTZ,  -- hasta cuándo mostrar
    created_by          INT REFERENCES users(id),
    created_at          TIMESTAMPTZ DEFAULT now(),
    updated_at          TIMESTAMPTZ DEFAULT now()
);
CREATE INDEX idx_academic_active ON academic_content(is_active, display_order);
CREATE INDEX idx_academic_area ON academic_content(program_area);
CREATE INDEX idx_academic_dates ON academic_content(show_from, show_until);

COMMENT ON TABLE academic_content IS 'Programación académica, ofertas trimestrales, anuncios';

-- =====================================================
-- CONTENIDO NUESTRO CENTRO
-- =====================================================
CREATE TABLE center_content (
    id                  SERIAL PRIMARY KEY,
    sector              VARCHAR(50) NOT NULL,  -- empresarial, salud, tecnologia, hoteleria, turismo, barismo, eventos
    title               VARCHAR(255) NOT NULL,
    description         TEXT,
    media_type          VARCHAR(20) CHECK (media_type IN ('image','video','text')),
    media_path          VARCHAR(500),
    thumbnail_path      VARCHAR(500),
    duration_seconds    INT DEFAULT 15,
    display_order       INT DEFAULT 0,
    is_active           BOOLEAN DEFAULT TRUE,
    show_from           TIMESTAMPTZ,  -- desde cuándo mostrar
    show_until          TIMESTAMPTZ,  -- hasta cuándo mostrar
    created_by          INT REFERENCES users(id),
    created_at          TIMESTAMPTZ DEFAULT now(),
    updated_at          TIMESTAMPTZ DEFAULT now()
);
CREATE INDEX idx_center_active ON center_content(is_active, display_order);
CREATE INDEX idx_center_sector ON center_content(sector);
CREATE INDEX idx_center_dates ON center_content(show_from, show_until);

COMMENT ON TABLE center_content IS 'Información institucional de los sectores de formación';

-- =====================================================
-- CONTENIDO GENERAL (noticias, eventos, indicadores)
-- =====================================================
CREATE TABLE general_content
(
    id                  SERIAL PRIMARY KEY,
    category            VARCHAR(30) NOT NULL CHECK (category IN ('news','event','announcement','currency','commodity','sports','weather','institutional')),
    title               VARCHAR(255) NOT NULL,
    description         TEXT,
    source_url          VARCHAR(500),
    image_path          VARCHAR(500),
    icon                VARCHAR(50),
    external_data       JSONB,  -- datos crudos si proviene de API
    duration_seconds    INT DEFAULT 10,
    display_order       INT DEFAULT 0,
    is_active           BOOLEAN DEFAULT TRUE,
    is_external         BOOLEAN DEFAULT FALSE,
    publish_date        TIMESTAMPTZ DEFAULT now(),
    expire_date         TIMESTAMPTZ,
    created_by          INT REFERENCES users(id),
    created_at          TIMESTAMPTZ DEFAULT now(),
    updated_at          TIMESTAMPTZ DEFAULT now()
);
CREATE INDEX idx_general_active ON general_content(is_active, display_order);
CREATE INDEX idx_general_category ON general_content(category);
CREATE INDEX idx_general_dates ON general_content(publish_date, expire_date);

COMMENT ON TABLE general_content IS 'Noticias, eventos, anuncios, indicadores económicos y deportivos';

-- =====================================================
-- GALERÍA (solo imágenes en cartelera)
-- =====================================================
CREATE TABLE gallery_content
(
    id                  SERIAL PRIMARY KEY,
    title               VARCHAR(255),
    image_path          VARCHAR(500) NOT NULL,
    duration_seconds    INT DEFAULT 15,
    display_order       INT DEFAULT 0,
    is_active           BOOLEAN DEFAULT TRUE,
    show_from           TIMESTAMPTZ,
    show_until          TIMESTAMPTZ,
    created_by          INT REFERENCES users(id),
    created_at          TIMESTAMPTZ DEFAULT now(),
    updated_at          TIMESTAMPTZ DEFAULT now()
);
CREATE INDEX idx_gallery_active ON gallery_content(is_active, display_order);
CREATE INDEX idx_gallery_dates ON gallery_content(show_from, show_until);

COMMENT ON TABLE gallery_content IS 'Imágenes a pantalla completa para el escenario Galería de la cartelera';

-- =====================================================
-- ARCHIVOS MULTIMEDIA
-- =====================================================
CREATE TABLE media_files
(
    id                  SERIAL PRIMARY KEY,
    filename            VARCHAR(255) UNIQUE NOT NULL,
    original_name       VARCHAR(500),
    mime_type           VARCHAR(100),
    file_size           BIGINT,
    file_path           VARCHAR(500) NOT NULL,
    category            VARCHAR(30),  -- academic / center / general / gallery
    width               INT,
    height              INT,
    duration            INT,  -- segundos, para video/audio
    uploaded_by         INT REFERENCES users(id),
    uploaded_at         TIMESTAMPTZ DEFAULT now()
);
CREATE INDEX idx_media_category ON media_files(category);

-- =====================================================
-- LOG DE ACTIVIDAD
-- =====================================================
CREATE TABLE activity_log
(
    id                  BIGSERIAL PRIMARY KEY,
    user_id             INT REFERENCES users(id),
    action              VARCHAR(50) NOT NULL,
    table_name          VARCHAR(50),
    record_id           INT,
    details             JSONB,
    ip_address          INET,
    user_agent          TEXT,
    created_at          TIMESTAMPTZ DEFAULT now()
);
CREATE INDEX idx_log_user ON activity_log(user_id, created_at DESC);

-- =====================================================
-- INTENTOS DE LOGIN (rate limiting por IP, ver E3 en PLAN_BLINDAJE_CSET.md)
-- =====================================================
CREATE TABLE login_attempts
(
    id              BIGSERIAL PRIMARY KEY,
    ip_address      VARCHAR(45) NOT NULL,
    username        VARCHAR(50),
    success         BOOLEAN NOT NULL,
    failure_reason  VARCHAR(30),
    user_agent      VARCHAR(255),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_login_attempts_ip_created ON login_attempts(ip_address, created_at DESC);
CREATE INDEX idx_login_attempts_username_created ON login_attempts(username, created_at DESC);
CREATE INDEX idx_log_action ON activity_log(action);

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Usuario administrador (contraseña: Admin123!)
-- Hash generado con PASSWORD_DEFAULT (bcrypt)
INSERT INTO users(username, password_hash, full_name, email, role, program_area)
VALUES ('admin', '$2y$10$xJ9Y2vE5LqHZLzJKJz9zO.5Yqg0K5XnF6YxvVmP6mD.7TkGqYyZWu', 'Administrador del Sistema', 'admin@sena.edu.co', 'admin', NULL),
       ('jp_empresarial', '$2y$10$xJ9Y2vE5LqHZLzJKJz9zO.5Yqg0K5XnF6YxvVmP6mD.7TkGqYyZWu', 'Jefe Programa Empresarial',    'empresarial@sena.edu.co',  'program_head', 'empresarial'),
       ('jp_salud',       '$2y$10$xJ9Y2vE5LqHZLzJKJz9zO.5Yqg0K5XnF6YxvVmP6mD.7TkGqYyZWu', 'Jefe Programa Salud',          'salud@sena.edu.co',        'program_head', 'salud'),
       ('jp_tecnologia',  '$2y$10$xJ9Y2vE5LqHZLzJKJz9zO.5Yqg0K5XnF6YxvVmP6mD.7TkGqYyZWu', 'Jefe Programa Tecnología',     'tecnologia@sena.edu.co',   'program_head', 'tecnologia'),
       ('jp_hoteleria',   '$2y$10$xJ9Y2vE5LqHZLzJKJz9zO.5Yqg0K5XnF6YxvVmP6mD.7TkGqYyZWu', 'Jefe Programa Hotelería',      'hoteleria@sena.edu.co',    'program_head', 'hoteleria'),
       ('jp_turismo',     '$2y$10$xJ9Y2vE5LqHZLzJKJz9zO.5Yqg0K5XnF6YxvVmP6mD.7TkGqYyZWu', 'Jefe Programa Turismo',        'turismo@sena.edu.co',      'program_head', 'turismo'),
       ('jp_barismo',     '$2y$10$xJ9Y2vE5LqHZLzJKJz9zO.5Yqg0K5XnF6YxvVmP6mD.7TkGqYyZWu', 'Jefe Programa Barismo',        'barismo@sena.edu.co',      'program_head', 'barismo'),
       ('jp_eventos',     '$2y$10$xJ9Y2vE5LqHZLzJKJz9zO.5Yqg0K5XnF6YxvVmP6mD.7TkGqYyZWu', 'Jefe Programa Eventos',        'eventos@sena.edu.co',      'program_head', 'eventos'),
       ('comunicaciones', '$2y$10$xJ9Y2vE5LqHZLzJKJz9zO.5Yqg0K5XnF6YxvVmP6mD.7TkGqYyZWu', 'Coordinador de Comunicaciones','comunicaciones@sena.edu.co','editor', NULL);

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

CREATE OR REPLACE VIEW v_active_academic AS SELECT * FROM academic_content
WHERE is_active = TRUE                           AND
      (show_from  IS NULL OR show_from  <= now()) AND
      (show_until IS NULL OR show_until >= now())
ORDER BY display_order, created_at DESC;

CREATE OR REPLACE VIEW v_active_center AS
SELECT * FROM center_content
WHERE is_active = TRUE
  AND (show_from IS NULL OR show_from <= now())
  AND (show_until IS NULL OR show_until >= now())
ORDER BY display_order, created_at DESC;

CREATE OR REPLACE VIEW v_active_general AS
SELECT * FROM general_content
WHERE is_active = TRUE AND
      (expire_date IS NULL OR expire_date >= now())
ORDER BY display_order, publish_date DESC;

-- =====================================================
-- FUNCIÓN DE LIMPIEZA DE SESIONES EXPIRADAS
-- =====================================================
CREATE OR REPLACE FUNCTION cleanup_expired_sessions() RETURNS void AS $$
BEGIN
    DELETE FROM user_sessions WHERE expires_at < now();
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- FUNCIÓN DE LOGGING AUTOMÁTICO
-- =====================================================
CREATE OR REPLACE FUNCTION log_activity
(
    p_user_id           INT,
    p_action            VARCHAR,
    p_table_name        VARCHAR DEFAULT NULL,
    p_record_id         INT DEFAULT NULL,
    p_details JSONB     DEFAULT NULL,
    p_ip                INET DEFAULT NULL
) RETURNS void AS $$
BEGIN
    INSERT INTO activity_log (user_id, action, table_name, record_id, details, ip_address)
    VALUES (p_user_id, p_action, p_table_name, p_record_id, p_details, p_ip);
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- VISTA PARA DASHBOARD ADMIN
-- =====================================================
CREATE OR REPLACE VIEW v_dashboard_stats AS
SELECT (SELECT COUNT(*) FROM users WHERE is_active = TRUE) AS active_users,
       (SELECT COUNT(*) FROM academic_content WHERE is_active = TRUE) AS active_academic,
       (SELECT COUNT(*) FROM center_content WHERE is_active = TRUE) AS active_center,
       (SELECT COUNT(*) FROM general_content WHERE is_active = TRUE) AS active_general,
       (SELECT COUNT(*) FROM gallery_content WHERE is_active = TRUE) AS active_gallery,
       (SELECT COUNT(*) FROM media_files) AS total_media,
       (SELECT COUNT(*) FROM user_sessions WHERE expires_at > now()) AS active_sessions,
       (SELECT COUNT(*) FROM activity_log WHERE created_at > now() - INTERVAL '24 hours') AS activity_24h;

COMMENT ON VIEW v_dashboard_stats IS 'Estadísticas para el dashboard del administrador';

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
SELECT 'IES-TV :: Base de datos creada exitosamente' AS status;
SELECT 'IMPORTANTE: Cambie las contraseñas después del primer ingreso' AS warning;