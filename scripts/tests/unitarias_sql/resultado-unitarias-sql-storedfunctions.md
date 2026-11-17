# Reporte de Resultados de Pruebas Unitarias SQL: Procedimientos Almacenados PL/pgSQL
**Estándar:** ISO/IEC/IEEE 29119-3:2021 (Test Documentation)  
**Sistema:** Sistema de Cartelera Digital CSET-TV  
**Módulo:** `StoredFunctionsTest.php` (Procedimientos Almacenados y Rutinas PL/pgSQL)  
**Motor de Base de Datos:** PostgreSQL 16.15  
**Fecha de Ejecución:** 2026-11-17  
**Ejecutor:** PHPUnit 10.5.64 (`--testsuite UnitariasSql`)  
**Estrategia Transaccional:** Aislamiento estricto con `ROLLBACK` en `tearDown()` (Zero-Side-Effects)  
**Resultado Global:** 5 / 5 APROBADOS (100%)

---

## 1. Identificación y Objetivos

El presente documento certifica los resultados de las pruebas unitarias y de integración sobre las funciones almacenadas en el motor PostgreSQL: `log_activity()` (mecanismo central de auditoría de seguridad y trazabilidad) y `cleanup_expired_sessions()` (rutina de saneamiento periódico de sesiones de usuario caducadas).

---

## 2. Matriz de Cobertura y Casos de Prueba

| ID Caso | Función Evaluada | Condición de Prueba | Entrada / Contexto | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **UT-SQL-FNC-001** | `log_activity()` | Inserción con parámetros mínimos | `$user_id` y `$action` definidos; restantes nulos | Inserta correctamente en `activity_log` con valores por defecto | **PASS** |
| **UT-SQL-FNC-002** | `log_activity()` | Metadatos avanzados | `JSONB` estructurado, `INET` IP y referencia a tabla/registro | Almacena y permite decodificar los atributos JSONB e IP intactos | **PASS** |
| **UT-SQL-FNC-003** | `cleanup_expired_sessions()` | Purga de sesiones caducadas | Sesión con `expires_at` menor a `now()` | La sesión caducada es eliminada de la base de datos | **PASS** |
| **UT-SQL-FNC-004** | `cleanup_expired_sessions()` | Preservación de sesiones activas | Sesión con `expires_at` futuro (`now() + 2 hours`) | La función conserva la sesión activa sin alterarla | **PASS** |
| **UT-SQL-FNC-005** | `log_activity()` | Acciones del sistema (`user_id` null) | Tareas en segundo plano o cron sin usuario autenticado | Registra la auditoría de sistema sin violar claves foráneas | **PASS** |

---

## 3. Resumen Cuantitativo

- **Total de pruebas ejecutadas:** 5
- **Aprobadas:** 5 (100%)
- **Fallidas:** 0 (0%)
- **Asertos evaluados:** 17
- **Persistencia en base de datos:** 0 registros residuales (100% revertidos por ROLLBACK)

---

## 4. Conclusiones y Dictamen

Las rutinas almacenadas en PostgreSQL garantizan la higiene de la base de datos y la auditabilidad completa de eventos administrativos, optimizando el rendimiento global al ejecutar estas operaciones a nivel de base de datos sin sobrecargar el servidor web.
