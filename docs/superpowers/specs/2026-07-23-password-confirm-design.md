# Diseño: confirmar contraseña y requisitos visibles

**Fecha:** 2026-07-23  
**Ámbito:** panel admin — formulario de usuario (`UserForm.svelte`)  
**Estado:** aprobado en conversación (enfoque 1, reglas estáticas, create + edit)

## Problema

Al crear o editar un usuario, el campo de contraseña solo muestra `minlength="10"`. El backend exige más reglas vía `Security::isStrongPassword`, y no hay confirmación para evitar typos.

## Decisiones

| Decisión | Valor |
|----------|--------|
| Enfoque | Solo frontend en `UserForm.svelte` |
| Requisitos | Texto estático (sin checklist en vivo) |
| Dónde | Crear usuario y editar usuario |
| API | Sin cambios |
| Confirmación en edición | Solo si el usuario escribe una nueva contraseña |

## Comportamiento

1. Campo **Confirmar contraseña** debajo de Contraseña (crear) / Nueva contraseña (editar).
2. Bloque de texto estático con los requisitos alineados a `Security::isStrongPassword`:
   - Mínimo 10 caracteres
   - Al menos una letra mayúscula
   - Al menos una letra minúscula
   - Al menos un número
   - Al menos un carácter especial (no alfanumérico)
3. Al enviar:
   - **Crear:** contraseña obligatoria; debe coincidir con la confirmación.
   - **Editar:** si `password` está vacío, no se cambia y no se exige confirmación. Si hay valor, debe coincidir con la confirmación.
4. Si no coinciden: no llamar a la API; mostrar feedback (toast o mensaje inline) al usuario.
5. La validación de fuerza real sigue en el servidor (`UsersController` + `isStrongPassword`). El frontend no replica la lógica de fuerza; solo documenta las reglas y valida el match.

## Archivos a tocar

- `frontend/src/lib/components/crud/UserForm.svelte` — único cambio de producto.

## Fuera de alcance

- Checklist en vivo (✓/✗) mientras se escribe
- Helper TS que replique `isStrongPassword`
- Campo `password_confirmation` en la API
- Cambios en login, tools CLI o `reset_admin`

## Criterios de aceptación

- [ ] En `/admin/users/new` y `/admin/users/[id]` aparecen confirmar + lista de requisitos.
- [ ] Crear con contraseñas distintas no envía el formulario.
- [ ] Editar sin tocar contraseña sigue guardando el resto de campos.
- [ ] Editar con nueva contraseña exige coincidencia con confirmar.
- [ ] El texto de requisitos refleja exactamente las reglas del backend actuales.
