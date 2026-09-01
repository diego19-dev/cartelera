ALTER TABLE general_content
  ADD COLUMN IF NOT EXISTS icon VARCHAR(50) NULL;

COMMENT ON COLUMN general_content.icon IS
  'Clave Lucide del banco (ej. dollar-sign). NULL = sin icono en TV.';
