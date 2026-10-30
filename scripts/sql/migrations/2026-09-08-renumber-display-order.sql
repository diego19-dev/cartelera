-- One-shot: compactar display_order 1…N por created_at en módulos de contenido.
-- Preferible: php tools/renumber_display_order.php
-- Este SQL es equivalente si se ejecuta a mano.

WITH ordered AS (
  SELECT id, ROW_NUMBER() OVER (ORDER BY created_at ASC NULLS LAST, id ASC) AS rn
  FROM academic_content
)
UPDATE academic_content AS t
SET display_order = ordered.rn
FROM ordered
WHERE t.id = ordered.id;

WITH ordered AS (
  SELECT id, ROW_NUMBER() OVER (ORDER BY created_at ASC NULLS LAST, id ASC) AS rn
  FROM gallery_content
)
UPDATE gallery_content AS t
SET display_order = ordered.rn
FROM ordered
WHERE t.id = ordered.id;

WITH ordered AS (
  SELECT id, ROW_NUMBER() OVER (ORDER BY created_at ASC NULLS LAST, id ASC) AS rn
  FROM general_content
)
UPDATE general_content AS t
SET display_order = ordered.rn
FROM ordered
WHERE t.id = ordered.id;

WITH ordered AS (
  SELECT id, ROW_NUMBER() OVER (ORDER BY created_at ASC NULLS LAST, id ASC) AS rn
  FROM center_content
)
UPDATE center_content AS t
SET display_order = ordered.rn
FROM ordered
WHERE t.id = ordered.id;
