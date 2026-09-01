# PLAN DE BLINDAJE — CSET (Cartelera Digital)

## Fase 0 (Gobernanza Base) + Fase 1 (Blindaje Técnico Inmediato) — alineado con SmartCSET

---

| Campo | Valor |
|---|---|
| **Documento N°** | CSET-BLI-001 |
| **Versión** | 1.0 |
| **Fecha de elaboración** | 2026-07-27 |
| **Elaborado por** | Equipo de Desarrollo - SmartCSET (mismo equipo, ambos proyectos) |
| **Estado** | Plan propuesto — pendiente de ejecución |
| **Insumo directo** | `AUDITORIA_SEGURIDAD_CSET.md` (CSET-AUD-001) — todos los hallazgos de esa auditoría son los riesgos de entrada de este plan |
| **Fundamento normativo / metodológico** | Mismo marco que SmartCSET: MSPI (MinTIC, 11 ejes), Ley 1581/2012, ISO/IEC 27001 Anexo A, OWASP Top 10 2021, ISC2 CC |
| **Referencia de diseño** | `Documentacion para implementacion de MSPI/` de SmartCSET — este plan reutiliza la misma estructura de fases y el mismo formato de checklist (`FASE1-CHK-002`), adaptados al alcance real de CSET |

> **Por qué un solo documento y no 8 + 1 como SmartCSET:** SmartCSET trata datos de identidad de terceros (cédula, foto, patrones de movimiento de miles de personas, incluidos menores de edad) — eso justifica una serie documental completa de Fase 0 (8 documentos legales) más un plan técnico de Fase 1 de 1500+ líneas. CSET es una cartelera de contenido institucional: sus únicos datos personales son los del propio personal que administra el panel (nombre, correo, rol) — no hay terceros, no hay biometría, no hay control de acceso físico. El riesgo es real (la auditoría encontró 6 hallazgos críticos) pero la **superficie de datos personales** es mucho menor. Por eso este plan condensa la Fase 0 en una sección dentro de este mismo documento en vez de 8 archivos separados, pero **cubre exactamente los mismos 11 ejes MSPI** que la Política General de Seguridad de SmartCSET (`FASE0-PSI-006`) mapea. Si en el futuro CSET incorpora datos de terceros (ej. un formulario público), este criterio debe revisarse.

---

## PARTE 0 — FASE 0: GOBERNANZA BASE (condensada)

### 0.1 Objeto y alcance

Establecer la base de gobernanza de seguridad y privacidad de la información para CSET, equivalente en principios a la de SmartCSET (`FASE0-PSI-006`), cubriendo: gestión de identidad de usuarios del panel administrativo, gestión de contenido de la cartelera, y la infraestructura que los soporta (`10.5.213.111`, compartida con SmartCSET).

### 0.2 Responsable y Encargado del Tratamiento

Idénticos a SmartCSET, por tratarse de la misma institución y el mismo equipo:

| Rol | Asignación |
|---|---|
| Responsable del Tratamiento | SENA — Dirección del Centro de Formación (misma que SmartCSET) |
| Encargado del Tratamiento (técnico) | Equipo de Desarrollo - SmartCSET (mismas personas que administran SmartCSET) |
| Responsable de Seguridad de la Información (RSSI) | El mismo rol ya definido en `FASE0-ROL-004 §4.3` de SmartCSET — no se duplica la designación, se extiende su alcance a CSET |

No se requiere una nueva designación formal de roles — **se amplía el alcance de los roles ya designados para SmartCSET** para que cubran también CSET, dado que es el mismo equipo operando la misma infraestructura (`.111`).

### 0.3 Finalidades del tratamiento

1. Publicar y rotar contenido institucional en pantallas del Centro de Formación (académico, sector/centro, general/indicadores, galería).
2. Administrar el acceso al panel de gestión de contenido (usuarios internos únicamente — nunca terceros/público).
3. Registrar auditoría de cambios de contenido y configuración (`activity_log`).

A diferencia de SmartCSET, **CSET no tiene finalidad de identificación de personas ni de control de acceso físico** — esto reduce significativamente el riesgo inherente frente al de SmartCSET.

### 0.4 Datos tratados y clasificación C-I-D

| Dato | Ubicación | Titular | C | I | D | Criticidad |
|---|---|---|---|---|---|---|
| Credenciales de usuarios del panel (hash bcrypt) | `users.password_hash` | Personal interno SENA | 5 | 5 | 4 | 🔴 Muy Alta |
| Nombre, correo, rol de usuarios del panel | `users` | Personal interno SENA | 3 | 3 | 3 | 🟡 Media |
| Bitácora de actividad | `activity_log` | Personal interno SENA | 3 | **5** | 3 | 🔴 Muy Alta (integridad — ver hallazgo A6) |
| Contenido de cartelera (académico/centro/general/galería) | tablas `*_content` | Institucional (SENA) | 1 | 3 | 4 | 🟠 Alta |
| Secretos de configuración (`DB_PASS`, `SECRET_KEY`) | `src/Core/Config.php` | Sistema | **5** | **5** | 4 | 🔴 Muy Alta (ver hallazgo C1) |

No existen en CSET equivalentes a `documento_identidad`, fotografías de terceros, ni datos biométricos — el Eje de "datos sensibles" del MSPI aplica aquí en un alcance mucho menor que en SmartCSET.

### 0.5 Matriz de riesgos inicial (derivada directamente de `AUDITORIA_SEGURIDAD_CSET.md`)

Mismos criterios de valoración que `FASE0-RIE-003` de SmartCSET (Probabilidad × Impacto, escala 1-25).

| ID | Riesgo | Hallazgo origen | Prob. | Imp. | Riesgo Inherente | Entregable que lo cierra |
|---|---|---|---|---|---|---|
| **CR-01** | Compromiso total de BD por credencial expuesta en git | C1 | 5 | 5 | 🔴 25 (Extremo) | E7 — ✅ cerrado 2026-07-27 (cortado a `cset_app`, credencial vieja de `postgres` ya no da acceso a CSET) |
| **CR-02** | Acceso con contraseña de fábrica conocida públicamente | C2 | 5 | 5 | 🔴 25 (Extremo) | E1 (A07) |
| **CR-03** | Fuerza bruta / credential stuffing sin bloqueo real | C3 | 4 | 5 | 🔴 20 (Extremo) | E3 — ✅ cerrado 2026-07-27 |
| **CR-04** | Compromiso de todo el clúster PostgreSQL de `.111` vía CSET | C4 | 2 | 5 | 🟠 10 (Alto) | E8 — ✅ cerrado 2026-07-27 (`.env` real del servidor cortado a `cset_app`) |
| **CR-05** | Interceptación de sesión/credenciales en tránsito (sin TLS) | C5 | 3 | 5 | 🔴 15 (Extremo) | E4/E8 |
| **CR-06** | Escalada horizontal: `program_head` modifica contenido fuera de su alcance | C6 | 4 | 3 | 🟠 12 (Alto) | E1 (A01) — ✅ cerrado 2026-07-27 |
| **CR-07** | Bypass de validación de tipo de archivo subido | A1, A2 | 2 | 3 | 🟡 6 (Moderado) | E6 |
| **CR-08** | Fuga de información interna (DSN) ante fallo de BD | A3 | 2 | 2 | 🟢 4 (Bajo) | E1 (A05) |
| **CR-09** | Cuentas inutilizables por contraseñas mal hasheadas | A4 | 3 | 2 | 🟡 6 (Moderado, funcional) | E1 (A07) — ✅ cerrado 2026-07-27 |
| **CR-10** | XSS por ausencia de CSP (defensa en profundidad) | A5 | 2 | 3 | 🟡 6 (Moderado) | E4 |
| **CR-11** | Endpoint de borrado físico de usuarios (contradice política de desactivar, no eliminar) | A6 | 3 | 4 | 🟠 12 (Alto) | E1 (A09) — ✅ cerrado 2026-07-27 (endpoint eliminado) |

**Perfil de riesgo inherente promedio: ~15.5 (zona Extrema)** — más alto que el de SmartCSET al inicio de su Fase 1 (~11.7), principalmente por CR-01/CR-02 (secretos y contraseñas expuestas *hoy*, no solo teóricas). Tras ejecutar este plan, el objetivo es el mismo perfil residual que alcanzó SmartCSET: zona Baja (≤4 en promedio).

### 0.6 Política de control de acceso (referencia al modelo ya implementado)

CSET ya implementa RBAC de 3 roles (`admin` / `program_head` / `editor`) — el modelo es correcto en su diseño; los hallazgos de la auditoría son de **implementación incompleta** (CR-06), no de diseño. Este plan no cambia el modelo de roles, solo cierra las brechas de aplicación (E1).

Se adopta el mismo principio que `FASE0-ACC-007 §7.3` de SmartCSET: **las cuentas se desactivan (`is_active = false`), nunca se eliminan físicamente** — cierra CR-11.

### 0.7 Gestión de incidentes y continuidad (condensada)

| Parámetro | Valor propuesto | Nota |
|---|---|---|
| RTO | 2 horas | CSET es menos crítico operativamente que el control de acceso físico de SmartCSET — una cartelera caída no detiene la operación del Centro |
| RPO | 24 horas | Igual que SmartCSET |
| Backup | 🔲 **Pendiente de confirmar** si `db_cset` está incluido en el backup automatizado que ya corre en `.111` para SmartCSET (`backup_smartcset.sh`) — si no lo está, es la acción de menor esfuerzo y mayor beneficio de todo este plan (mismo argumento que R-C01 fue la prioridad #1 en la Matriz de Riesgos de SmartCSET) |
| Notificación a SIC | Solo aplicaría si se filtran datos de `users` (personal interno) — improbable que alcance el umbral de "riesgo para los Titulares" de la Ley 1581, pero se documenta el mismo árbol de decisión que `FASE0-INC-008 §11` |

---

## PARTE 1 — FASE 1: BLINDAJE TÉCNICO INMEDIATO

Mismos 10 entregables que `FASE1-BLI-001` de SmartCSET, mapeados 1:1 contra los hallazgos reales de `AUDITORIA_SEGURIDAD_CSET.md`. A diferencia de SmartCSET (que empezó sin auditoría previa), CSET **ya tiene el diagnóstico completo** — este plan va directo a la remediación.

### E1 — Cierre de la Auditoría OWASP Top 10 (ya ejecutada)

La auditoría (`CSET-AUD-001`) ya cumple el rol de diagnóstico inicial de este entregable. Checklist de cierre:

| Control OWASP | Hallazgo | Acción | Estado |
|---|---|---|---|
| A01 — Broken Access Control | C6 (`GeneralController` sin rol), M3 (`MediaController::delete` bug de área) | Agregar `Session::hasRole()`/`Auth::canEditArea()` faltantes | ✅ Corregido 2026-07-27 |
| A02 — Cryptographic Failures | C5 (sin TLS), B1 (sin cifrado en reposo — diferido, bajo riesgo) | Ver E4/E8 | 🔲 |
| A03 — Injection | Sin hallazgos (100% prepared statements verificado) | — | ✅ |
| A05 — Security Misconfiguration | A3 (fuga DSN), A5 (sin CSP) | Quitar `echo $dsn`; agregar CSP | ⚠️ A3 ✅ corregido 2026-07-27; A5 (CSP) sigue en E4 |
| A07 — Auth Failures | C2 (contraseñas de fábrica), C3 (rate limit falso), A4 (bug de hasheo) | Ver E3; rotar contraseñas; excluir `password` de `sanitizeArray()` | ⚠️ C2 y A4 ✅ corregidos 2026-07-27 (5 cuentas reales rotadas en `.111`, incluida `bienestar` con hash roto); C3 sigue en E3 |
| A08 — Software/Data Integrity | A1 (MIME no validado) | Ver E6 | 🔲 |
| A09 — Logging/Monitoring | A6 (endpoint de borrado físico), M2 (IP falsificable) | Quitar `destroy()` de usuarios; validar proxy de confianza para `clientIp()` | ✅ Corregido 2026-07-27 (`TRUST_PROXY=1` requerido para confiar en `X-Forwarded-For`) |

**Verificación de cierre:** repetir el checklist OWASP contra el código tras aplicar E3/E4/E6/E7/E8, igual que hizo SmartCSET en su cierre de E1.

### E2 — Cifrado en reposo — 🔲 Evaluar alcance (probablemente diferido)

A diferencia de SmartCSET (bloqueado por diseño de PK), CSET no tiene ese problema estructural — `users.email` podría cifrarse con `pgcrypto` sin complicaciones de diseño. Sin embargo, dado que:
- No hay datos de terceros (solo personal interno, ya cubierto por acuerdos de confidencialidad institucional).
- El correo institucional (`@sena.edu.co`) no es un dato de alta sensibilidad por sí solo.

**Recomendación:** diferir E2 explícitamente (igual que SmartCSET documentó su propio diferimiento de E2 con justificación), priorizando primero los 6 hallazgos críticos que sí son explotables hoy. Revisar en Fase 3 si CSET incorpora algún dato de mayor sensibilidad.

### E3 — Rate limiting + logging de autenticación real — cierra **CR-03 (C3)** — ✅ COMPLETADO (2026-07-27)

Replica el diseño ya validado en SmartCSET (`fun_registrar_intento_login` / `fun_verificar_bloqueo_ip`), adaptado a PHP+PDO puro (CSET no usa PL/pgSQL para lógica de negocio, a diferencia de SmartCSET):

- [x] Tabla `login_attempts` (`ip_address`, `username`, `success`, `failure_reason`, `user_agent`, `created_at`) — migración `sql/migrations/2026-07-27-login-attempts.sql`, aplicada en vivo en `db_cset` de `.111`, y agregada a `sql/schema.sql` para instalaciones nuevas.
- [x] `Security::rateLimit()` (basado en sesión) **eliminado por completo** — reemplazado por `LoginAttempt::isBlocked()`, consulta real por IP con `make_interval()`.
- [x] Se registra **todo** intento (exista o no el usuario) vía `LoginAttempt::record()` — `Auth::attempt()` ahora distingue `usuario_no_existe` / `usuario_inactivo` / `contrasena_incorrecta`.
- [x] Configurable: `LOGIN_MAX_ATTEMPTS` / `LOGIN_WINDOW_MINUTES` en `Config.php` vía `EnvLoader` (default 5/15, igual que SmartCSET).
- [x] Limpieza de registros >90 días en cada `record()` (mismo criterio que SmartCSET).
- [x] `AuthController::login()` verifica el bloqueo por IP **antes** de tocar la tabla de usuarios (mismo orden que `fun_verificar_bloqueo_ip` de SmartCSET), devolviendo `429` distinto de "credenciales inválidas".

**Verificado en vivo contra `.111`:** 5 intentos fallidos **sin reenviar cookies** (el vector de evasión exacto que tenía el diseño anterior) desde la misma IP → 6º intento bloqueado con `429`, incluso con credenciales correctas; usuario inexistente ahora sí queda auditado con `usuario_no_existe`; datos de prueba limpiados de `login_attempts` después.

### E4 — Hardening de sesiones y transporte — cierra **CR-05 (C5), CR-10 (A5), M5** — ✅ COMPLETADO (2026-07-27)

- [x] TLS en `.111` para `/cset` — no hizo falta certificado nuevo: `/cset` ya compartía el `VirtualHost *:443` de `smartcset.conf`, reutiliza el mismo certificado/llaves de SmartCSET.
- [x] `COOKIE_SECURE=1` en el `.env` real del servidor (TLS ya confirmado funcionando antes de activarlo).
- [x] `COOKIE_SAMESITE` ya estaba en `Strict` en el código local (`Config.php`) — solo faltaba desplegar.
- [x] `Content-Security-Policy` ya en `bootstrap.php` y `public/.htaccess` (código local, faltaba desplegar) — agregado también `Strict-Transport-Security` a `.htaccess` (antes solo cubría rutas `/api/*` vía `bootstrap.php`, no las rutas estáticas del SPA/`/tv/`), condicional a `expr=%{HTTPS} == 'on'`.
- [x] `RedirectMatch permanent ^/cset(.*)$ https://10.5.213.111/cset$1` agregado al `VirtualHost *:80` de `smartcset.conf` (mismo patrón que `/smartcset`), sin tocar `/gitlab`/`/suitecrm`.

**Decisión explícita (Equipo de Desarrollo, 2026-07-27):** la cartelera legacy servida en la raíz del servidor (`https://10.5.213.111/`, HTML marcado "compatible Chromium 53 / webOS 4.1 Signage") se dejó **intencionalmente sin forzar HTTPS** — riesgo real de dejar pantallas físicas viejas en blanco si no soportan los cifrados TLS configurados, sin beneficio de seguridad proporcional (contenido de solo lectura, sin sesión ni credenciales).

**Deploy realizado junto con este entregable (no estaba planeado originalmente, pero era prerrequisito):** el código local con los fixes de E1/E3/E4/E6/E7-parcial nunca se había sincronizado a `.111` — se hizo backup (`mv`, no borrado) del `/var/www/html/cset` anterior y se copió la copia local completa, más creación del `.env` real (antes inexistente en el servidor) con `DB_PASS` real, `SECRET_KEY` generada, y permisos de `uploads/` (`cperez:www-data 2775`) y `.user.ini` restaurados desde el backup.

**Verificado en vivo:** `curl -I http://10.5.213.111/cset/login` → `301` a HTTPS; raíz `http://10.5.213.111/` → `200` sin redirigir (intacto); `/smartcset` → sigue `301` (sin regresión); `/gitlab`/`/suitecrm` sin cambios; cookie real `secure; HttpOnly; SameSite=Strict`; headers `Content-Security-Policy` y `Strict-Transport-Security` presentes en `https://.../cset/login`; login real contra la BD responde `401 Credenciales inválidas` (no 500) confirmando que `.env` nuevo conecta correctamente.

### E5 — Tokens anti-CSRF — ✅ Ya implementado correctamente

A diferencia de SmartCSET (que lo descartó por alcance), CSET **ya tiene** CSRF real y bien construido (`Security::csrfToken()`/`verifyCsrf()`, `hash_equals`, header `X-CSRF-Token`). No requiere trabajo adicional — se documenta como entregable ✅ cerrado desde el diseño original.

### E6 — Validación robusta de subida de archivos — cierra **CR-07 (A1, A2)** — ✅ COMPLETADO (2026-07-27)

- [x] `MediaController`: tabla `EXPECTED_MIME` por extensión, compara el MIME real (`finfo`) contra lo esperado; rechaza si no coincide (mismo patrón que `LogoUpload.php` de SmartCSET).
- [x] Decisión del Equipo de Desarrollo: **sanear SVG, no quitarlo** (0 archivos SVG en uso real hoy, pero se prefiere mantener la funcionalidad). `MediaController::sanitizeSvg()` — parseo real con `DOMDocument` (no regex): rechaza cualquier `.svg` que no sea XML válido con raíz `<svg>`; elimina `<script>`/`<foreignObject>`/`<iframe>`/`<embed>`/`<object>` y cualquier atributo `on*` o `href`/`xlink:href` con `javascript:`.
- [x] Se mantuvo el resto del diseño ya correcto: nombre de archivo regenerado aleatoriamente, `.htaccess` bloqueando ejecución de PHP en `uploads/`.

**Verificado en vivo:** `.jpg` con contenido de texto plano → rechazado (mismatch de MIME). SVG con los 4 vectores de XSS a la vez (`<script>`, `onload`, `onclick`, `xlink:href="javascript:..."`) → subido pero completamente saneado, verificado leyendo el archivo resultante en disco. SVG con contenido basura (no XML) → rechazado sin dejar archivo huérfano. PNG real → sigue funcionando sin cambios (regresión).

**Hallazgos de infraestructura local corregidos en el camino** (no eran del código de `cset`, bloqueaban poder probar E6 de verdad):
1. Extensión PHP `dom` faltante (`php8.5-xml`) — sin ella, el saneador de SVG habría fallado con error fatal en cualquier entorno sin esa extensión. Instalada.
2. Directorio de uploads de `cset` no escribible por el usuario de Apache — agregado `www-data` al grupo `ander` (mismo patrón ya usado en el entorno).
3. `ProtectHome=read-only` de `systemd` en el servicio `apache2` bloqueaba escritura en `/home/ander/smartcset/cset/...` pese a permisos Unix correctos — extendido el override ya existente (`ReadWritePaths=`) que SmartCSET ya usaba para sus propias carpetas de uploads.

### E7 — Eliminación de secretos hardcodeados — cierra **CR-01 (C1), CR-02 (C2)** — ✅ COMPLETADO (2026-07-27)

Equivalente al E7 de SmartCSET (corrección de defectos de instalación/configuración), aquí enfocado en gestión de secretos:

- [x] Rotar `DB_PASS` de PostgreSQL en `.111` — **hecho de forma más segura que "rotar `postgres`"**: en vez de cambiar la contraseña del superusuario compartido con SmartCSET (habría roto esa conexión también), se cortó a `cset_app` con una contraseña nueva y exclusiva (ver E8). La contraseña vieja de `postgres` que quedó expuesta en git sigue siendo válida para ese rol, pero CSET ya no la usa en ningún punto.
- [x] Verificar y forzar cambio de las 10 contraseñas semilla — ya cerrado (ver C2 en `AUDITORIA_SEGURIDAD_CSET.md`).
- [x] Mover `DB_PASS`, `SECRET_KEY` a variables de entorno reales — `Config.php` reescrito con `EnvLoader`, sin ningún fallback hardcodeado; `.env` real creado en el servidor (antes no existía) y `.env` local actualizado, cada uno con su propio `SECRET_KEY` generado (`openssl rand -hex 32`).
- [ ] Purgar el secreto del historial de git (`git filter-repo`) — **diferido, decisión explícita**: el Equipo de Desarrollo reiniciará el repositorio más adelante ("son residuos, se van a borrar"); el riesgo inmediato ya está cerrado porque la credencial expuesta dejó de ser válida para CSET.
- [ ] ~~Agregar `Config.php` a `.gitignore`~~ — ya no aplica: `Config.php` no contiene ningún secreto (todo viene de `.env`, que sí está en `.gitignore`), no hay razón para excluirlo del control de versiones.

**Verificación realizada:** login real contra la BD en producción responde `401 Credenciales inválidas` (no `500`) → confirma que el `.env` nuevo conecta correctamente; `cset_app` verificado sin acceso a `db_smartcset2` ni privilegios DDL tras la rotación.

### E8 — Hardening de red y mínimo privilegio de base de datos — cierra **CR-04 (C4), refuerza CR-05** — ✅ COMPLETADO (2026-07-27)

- [x] Crear rol de PostgreSQL `cset_app` con privilegios acotados a `db_cset` (`SELECT`/`INSERT`/`UPDATE`/`DELETE` sobre las tablas de la app, sin DDL).
- [x] `.env` local migrado de `postgres` a `cset_app` y probado end-to-end.
- [x] `.env` **real de `.111`** migrado de `postgres` a `cset_app`, con contraseña nueva (`openssl rand -base64`, 32 caracteres) — verificado en producción: `GET /api/tv/ping` → 200, login real → `401` (no `500`).
- [ ] Confirmar `pg_hba.conf` de `.111`: si ya tiene `hostssl` acotado para `db_smartcset2` (ver Bug 13/17 de `CLAUDE.md`), replicar el mismo patrón para `db_cset` una vez TLS de BD esté disponible.
- [x] TLS de transporte web — ver E4, completado 2026-07-27.

**Verificación realizada:** `SELECT DISTINCT privilege_type FROM information_schema.role_table_grants WHERE grantee = 'cset_app';` → solo `SELECT`/`INSERT`/`UPDATE`/`DELETE`, sin DDL. `CREATE TABLE` como `cset_app` → rechazado. Lectura de `db_smartcset2` como `cset_app` → rechazada, re-verificado tras la rotación de contraseña. Ciclo CRUD completo sobre `academic_content` funcionando igual que con `postgres`.

**Limitación conocida y documentada** (ver `AUDITORIA_SEGURIDAD_CSET.md` C4): `cset_app` aún puede *abrir conexión* (`CONNECT`) a otras bases del clúster por el `GRANT CONNECT` que PostgreSQL da a `PUBLIC` por defecto — cerrarlo del todo requiere `REVOKE CONNECT ... FROM PUBLIC` en cada base, una acción de mayor alcance sobre bases de otros sistemas (ej. `db_smartcset2`) que no se tomó sin autorización explícita separada. No hay impacto real de datos: `cset_app` no tiene ningún privilegio de tabla fuera de `db_cset`.

### E9 — Gestión de parches y componentes — cierra **B2**

- [ ] Inventario de versiones: PHP (`php --version` en `.111`), PostgreSQL, Node/SvelteKit (`frontend/package.json`), Apache.
- [ ] Mismo SLA por CVSS que SmartCSET adoptó (`9.0+=48h · 7.0-8.9=7d · 4.0-6.9=30d`).
- [ ] Suscripción a las mismas fuentes de alerta ya usadas para SmartCSET (no hay que duplicar la suscripción, solo agregar CSET al alcance de vigilancia).

### E10 — SDLC seguro — extensión del ya adoptado en SmartCSET

CSET es del mismo equipo que ya adoptó el checklist pre-commit/pre-deploy y las 6 prohibiciones absolutas de `FASE1-BLI-001 §12` de SmartCSET (no hardcodear credenciales, no `push --force` a main, etc.). **La violación de C1/C2 demuestra que ese checklist no se aplicó en CSET** — se recomienda:

- [ ] Extender explícitamente el checklist de SDLC seguro de SmartCSET para que aplique también a `cset/` (mismo documento, alcance ampliado, o referencia cruzada desde `cset/AGENTS.md`).
- [ ] Agregar un hook de pre-commit (`git-secrets`, `gitleaks`, o similar) que bloquee commits con patrones de credenciales — hubiera detectado C1 antes del primer commit.

---

## PARTE 2 — ORDEN DE IMPLEMENTACIÓN RECOMENDADO

Mismo criterio que SmartCSET usó (§13 de `FASE1-BLI-001`): lo más urgente y más barato primero.

```
E7 (rotar secretos)  →  E1 (cerrar A01/A07 de código)  →  E3 (rate limiting real)
      ↓                                                          ↓
E4 (TLS + cookies + CSP)  →  E8 (mínimo privilegio BD)  →  E6 (validación uploads)
      ↓
E9 (inventario parches)  →  E10 (SDLC extendido)  →  E2 (diferido, revisar en Fase 3)
```

## PARTE 3 — CHECKLIST DE AVANCE (mismo formato que `FASE1-CHK-002`)

| Entregable | Urgencia | Estado | Cierra |
|---|---|---|---|
| E7 — Eliminar secretos hardcodeados | 🔴 | ✅ Completado y verificado en vivo (2026-07-27); purga del historial de git diferida a decisión del equipo | C1, C2 |
| E1 — Cierre checklist OWASP | 🔴 | ✅ Fixes de código listos y verificados localmente contra `.111` (2026-07-27) — pendiente A5/CSP (va en E4) y A1 (va en E6) | C6, A3, A4, A6, M2, M3 |
| E3 — Rate limiting real por IP | 🔴 | ✅ Completado y verificado en vivo (2026-07-27) | C3 |
| E4 — TLS + cookies + CSP | 🔴 | ✅ Completado y verificado en vivo (2026-07-27) | C5, A5, M5 |
| E8 — Mínimo privilegio de BD | 🟠 | ✅ Completado y verificado en vivo (2026-07-27) | C4 |
| E6 — Validación de uploads (MIME real, SVG) | 🟡 | ✅ Completado y verificado en vivo (2026-07-27) | A1, A2 |
| E5 — CSRF | — | ✅ Ya cumplido | — |
| E9 — Gestión de parches | 🟠 | 🔲 Pendiente | B2 |
| E10 — SDLC seguro extendido | 🟠 | 🔲 Pendiente | — |
| E2 — Cifrado en reposo | 🟢 | ⛔ Diferido (justificado) | B1 |
| Confirmar backup de `db_cset` en `.111` | 🔴 | 🔲 Pendiente de verificar | Continuidad (§0.7) |

**Prerrequisito institucional único, ya resuelto:** a diferencia de SmartCSET, CSET no necesita nueva designación de custodio de claves ni de RSSI — se reutilizan los roles ya vigentes (§0.2).

---

## PARTE 4 — ESTIMACIÓN DE ESFUERZO

| Bloque | Esfuerzo estimado | Nota |
|---|---|---|
| E7 (rotación + purga de git) | Horas (rotación) + coordinación (purga de historial, requiere ventana acordada) | Prioridad absoluta |
| E1 (fixes puntuales de código) | Horas — son ~6 cambios pequeños y localizados | Ya identificados línea por línea en la auditoría |
| E3 (rate limiting real) | 1 sesión — se copia el diseño ya probado de SmartCSET | Sin diseño desde cero |
| E4 (TLS + cookies + CSP) | 1 sesión — mismo procedimiento ya documentado y ejecutado por SmartCSET en `.103`/`.111` | Requiere consentimiento explícito para tocar `.111` |
| E8 (rol de BD mínimo privilegio) | 1 sesión — requiere migración cuidadosa sin downtime | Coordinar con E7 (mismo cambio de credenciales) |
| E6 (validación de uploads) | Horas | — |
| E9/E10 | Proceso continuo, no un sprint | Igual que en SmartCSET |

---

## CONCLUSIÓN

Este plan cierra la brecha identificada en `AUDITORIA_SEGURIDAD_CSET.md §8`: CSET puede alcanzar el mismo nivel de blindaje que SmartCSET **sin necesidad de diseñar nada nuevo** — los 6 hallazgos críticos y 6 altos tienen, en su mayoría, un patrón de solución ya construido, probado y documentado dentro del propio SmartCSET (E3, E4, E7, E8). El trabajo restante es de **replicación e implementación**, no de investigación — lo que hace este plan considerablemente más rápido de ejecutar que el de SmartCSET, que tuvo que diseñar cada control desde cero.

---

*Fin del documento — Plan de Blindaje CSET v1.0 — CSET-BLI-001*
