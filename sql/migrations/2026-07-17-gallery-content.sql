-- Escenario Galería: imágenes full-bleed en cartelera

CREATE TABLE IF NOT EXISTS gallery_content
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

CREATE INDEX IF NOT EXISTS idx_gallery_active ON gallery_content(is_active, display_order);
CREATE INDEX IF NOT EXISTS idx_gallery_dates ON gallery_content(show_from, show_until);

COMMENT ON TABLE gallery_content IS 'Imágenes a pantalla completa para el escenario Galería de la cartelera';

-- Añadir gallery al orden si aún no está
UPDATE display_config
SET config_value = CASE
        WHEN config_value IS NULL OR trim(config_value) = '' THEN 'academic,center,general,gallery'
        WHEN position('gallery' IN config_value) > 0 THEN config_value
        ELSE config_value || ',gallery'
    END,
    updated_at = now()
WHERE config_key = 'tv_scenario_order';

DROP VIEW IF EXISTS v_dashboard_stats;
CREATE VIEW v_dashboard_stats AS
SELECT (SELECT COUNT(*) FROM users WHERE is_active = TRUE) AS active_users,
       (SELECT COUNT(*) FROM academic_content WHERE is_active = TRUE) AS active_academic,
       (SELECT COUNT(*) FROM center_content WHERE is_active = TRUE) AS active_center,
       (SELECT COUNT(*) FROM general_content WHERE is_active = TRUE) AS active_general,
       (SELECT COUNT(*) FROM gallery_content WHERE is_active = TRUE) AS active_gallery,
       (SELECT COUNT(*) FROM media_files) AS total_media,
       (SELECT COUNT(*) FROM user_sessions WHERE expires_at > now()) AS active_sessions,
       (SELECT COUNT(*) FROM activity_log WHERE created_at > now() - INTERVAL '24 hours') AS activity_24h;
