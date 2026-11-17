# Reporte de Resultados de Pruebas Unitarias SQL: Usuarios, Roles y Sesiones
**Estándar:** ISO/IEC/IEEE 29119-3:2021 (Test Documentation)  
**Sistema:** Sistema de Cartelera Digital CSET-TV  
**Módulo:** `UsersAuthConstraintsTest.php` (Restricciones e Integridad de Usuarios y Sesiones)  
**Motor de Base de Datos:** PostgreSQL 16.15  
**Fecha de Ejecución:** 2026-11-17  
**Ejecutor:** PHPUnit 10.5.64 (`--testsuite UnitariasSql`)  
**Estrategia Transaccional:** Aislamiento estricto con `ROLLBACK` en `tearDown()` (Zero-Side-Effects)  
**Resultado Global:** 6 / 6 APROBADOS (100%)

---

## 1. Identificación y Objetivos

El presente documento certifica los resultados de las pruebas unitarias y de integridad sobre el esquema de base de datos relacional para los modelos de autenticación (`users`) y gestión de sesiones (`user_sessions`), verificando el cumplimiento de las restricciones de dominio `CHECK`, unicidad de identificadores, integridad referencial y propagación de eliminaciones en cascada (`ON DELETE CASCADE`).

---

## 2. Matriz de Cobertura y Casos de Prueba

| ID Caso | Tabla / Elemento Evaluado | Condición de Prueba | Entrada / Contexto | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **UT-SQL-USR-001** | `users.role` | Restricción `CHECK` de rol | Inserción con `role = 'superadmin'` | PostgreSQL emite `PDOException` por violación de CHECK | **PASS** |
| **UT-SQL-USR-002** | `users.username` | Restricción `UNIQUE` | Inserción de dos usuarios con idéntico `username` | PostgreSQL emite `PDOException` por clave duplicada | **PASS** |
| **UT-SQL-USR-003** | `user_sessions.user_id` | Regla `ON DELETE CASCADE` | Eliminación de registro en `users` con sesiones activas | Las filas asociadas en `user_sessions` se purgan automáticamente | **PASS** |
| **UT-SQL-USR-004** | `users` | Inserción válida y defaults | Creación de rol `program_head` con área `'tecnologia'` | `is_active` asigna `TRUE`, timestamps `created_at` válidos | **PASS** |
| **UT-SQL-USR-005** | `user_sessions.token` | Restricción `UNIQUE` | Inserción duplicada del mismo token criptográfico | Rechazo inmediato con violación de restricción UNIQUE | **PASS** |
| **UT-SQL-USR-006** | `user_sessions.user_id` | Restricción `FOREIGN KEY` | Inserción de sesión con `user_id = 99999999` inexistente | Rechazo por clave foránea ausente (`foreign key constraint`) | **PASS** |

---

## 3. Resumen Cuantitativo

- **Total de pruebas ejecutadas:** 6
- **Aprobadas:** 6 (100%)
- **Fallidas:** 0 (0%)
- **Asertos evaluados:** 13
- **Persistencia en base de datos:** 0 registros residuales (100% revertidos por ROLLBACK)

---

## 4. Conclusiones y Dictamen

El esquema de base de datos para autenticación y usuarios demuestra una robustez total frente a inconsistencias lógicas. Se comprueba que las sesiones huérfanas quedan descartadas por diseño relacional ante la baja de un funcionario y que ningún rol fuera de la política de seguridad institucional puede ser persistido en el motor PostgreSQL.

