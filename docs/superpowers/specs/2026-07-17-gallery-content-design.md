# Galería — diseño de contenido solo imágenes

Fecha: 2026-07-17

## Objetivo

Nuevo escenario de cartelera **Galería** (`gallery`): subir y rotar imágenes a pantalla completa en el área central de la TV, manteniendo header y footer existentes sin cambios.

## Decisiones

| Tema | Decisión |
|------|----------|
| Nombre UI | Galería |
| Clave escenario | `gallery` |
| TV body | Imagen full-bleed, sin overlay de título/descripción |
| Header / footer | Intactos (solo etiqueta de escenario en banner) |
| Rotación | Escenario propio, ordenable en Config junto a academic/center/general |
| Unidad | Cada imagen = un slide |
| Roles CRUD | `admin`, `program_head`, `editor` (sin filtro por sector) |
| Medios | Solo imagen (jpg/png/webp/gif); no video |
| Título | Opcional en admin (listado); no se muestra en TV |
| Duración | Por ítem (`duration_seconds`, default 15) |
| Uploads | `public/assets/uploads/gallery/` |

## Arquitectura

- Tabla `gallery_content` + modelo `GalleryContent`
- API `/api/gallery` (CRUD + toggle), espejo de academic/center/general
- Feed `/api/tv` incluye `scenarios.gallery` y respeta `tv_scenario_order`
- Admin: `/admin/gallery` + form mínimo
- Layout TV `galeria` en Signage ES5 y tv-modern

## Fuera de alcance

- No convertir Biblioteca de Medios en escenario
- No reutilizar `general_content` por categoría
- No cambiar chrome de header/footer
