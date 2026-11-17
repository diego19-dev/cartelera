# Reporte de Resultados de Pruebas Unitarias SQL: Configuración, Auditoría y Rate Limiting
**Estándar:** ISO/IEC/IEEE 29119-3:2021 (Test Documentation)  
**Sistema:** Sistema de Cartelera Digital CSET-TV  
**Módulo:** `DisplayConfigAuditTest.php` (Configuración de Pantalla, Auditoría y Rate Limiting)  
**Motor de Base de Datos:** PostgreSQL 16.15  
**Fecha de Ejecución:** 2026-11-17  
**Ejecutor:** PHPUnit 10.5.64 (`--testsuite UnitariasSql`)  
**Estrategia Transaccional:** Aislamiento estricto con `ROLLBACK` en `tearDown()` (Zero-Side-Effects)  
**Resultado Global:** 5 / 5 APROBADOS (100%)

---

## 1. Identificación y Objetivos

El presente documento certifica los resultados de las pruebas unitarias y de integridad relacional sobre las tablas operativas y de seguridad: `display_config` (parámetros globales de la cartelera y control de encendido/apagado), `login_attempts` (mecanismo de defensa contra ataques de fuerza bruta y rate limiting por IP) y `activity_log` (trazabilidad y cronología de eventos).

---

## 2. Matriz de Cobertura y Casos de Prueba

| ID Caso | Tabla / Elemento Evaluado | Condición de Prueba | Entrada / Contexto | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **UT-SQL-CFG-001** | `display_config.config_key` | Restricción `UNIQUE` | Inserción de clave de configuración duplicada | Rechazo estricto por violación de índice UNIQUE | **PASS** |
| **UT-SQL-CFG-002** | `display_config.updated_by` | Integridad referencial con `users` | Registro de cambio asociando el ID del usuario editor | Vinculación correcta de clave foránea `updated_by` | **PASS** |
| **UT-SQL-CFG-003** | `login_attempts` | Registro de fallos de acceso | Intento fallido con motivo `'invalid_password'` | Almacena IP, usuario, `success = FALSE` y timestamp | **PASS** |
| **UT-SQL-CFG-004** | `login_attempts` | Ventana temporal de rate limit | Inserción de 3 fallos recientes y 1 fallo antiguo | Agrupación exacta en ventana de 15 min ignorando antiguos | **PASS** |
| **UT-SQL-CFG-005** | `activity_log` | Auditoría cronológica | Registro de múltiples eventos secuenciales | Recupera la secuencia ordenada en orden cronológico ascendente | **PASS** |

---

## 3. Resumen Cuantitativo

- **Total de pruebas ejecutadas:** 5
- **Aprobadas:** 5 (100%)
- **Fallidas:** 0 (0%)
- **Asertos evaluados:** 11
- **Persistencia en base de datos:** 0 registros residuales (100% revertidos por ROLLBACK)

---

## 4. Conclusiones y Dictamen

Las tablas de soporte de infraestructura validan la efectividad de los mecanismos de rate limiting para mitigar ataques de denegación de servicio o fuerza bruta contra el login, garantizando simultáneamente la auditoría inalterable de cambios en la configuración institucional.
