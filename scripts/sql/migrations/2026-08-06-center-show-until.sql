-- Vigencia temporal (show_from / show_until) en Nuestro Centro

ALTER TABLE center_content
    ADD COLUMN IF NOT EXISTS show_from TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS show_until TIMESTAMPTZ;

CREATE INDEX IF NOT EXISTS idx_center_dates ON center_content(show_from, show_until);

COMMENT ON COLUMN center_content.show_from IS 'Desde cuándo mostrar en TV (NULL = ya vigente)';
COMMENT ON COLUMN center_content.show_until IS 'Hasta cuándo mostrar en TV (NULL = sin caducidad)';

CREATE OR REPLACE VIEW v_active_center AS
SELECT * FROM center_content
WHERE is_active = TRUE
  AND (show_from IS NULL OR show_from <= now())
  AND (show_until IS NULL OR show_until >= now())
ORDER BY display_order, created_at DESC;
