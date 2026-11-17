# Reporte de Resultados de Pruebas Unitarias SQL: Contenido Multimedia y Restricciones
**Estándar:** ISO/IEC/IEEE 29119-3:2021 (Test Documentation)  
**Sistema:** Sistema de Cartelera Digital CSET-TV  
**Módulo:** `ContentConstraintsTest.php` (Restricciones e Integridad de Contenido Multimedia)  
**Motor de Base de Datos:** PostgreSQL 16.15  
**Fecha de Ejecución:** 2026-11-17  
**Ejecutor:** PHPUnit 10.5.64 (`--testsuite UnitariasSql`)  
**Estrategia Transaccional:** Aislamiento estricto con `ROLLBACK` en `tearDown()` (Zero-Side-Effects)  
**Resultado Global:** 7 / 7 APROBADOS (100%)

---

## 1. Identificación y Objetivos

El presente documento certifica los resultados de las pruebas de integridad referencial, de dominio (`CHECK`), de no nulidad (`NOT NULL`), unicidad (`UNIQUE`) y valores predeterminados (`DEFAULT`) sobre las tablas que almacenan y programan el material audiovisual de la cartelera: `academic_content`, `center_content`, `general_content`, `gallery_content` y `media_files`.

---

## 2. Matriz de Cobertura y Casos de Prueba

| ID Caso | Tabla / Elemento Evaluado | Condición de Prueba | Entrada / Contexto | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **UT-SQL-CNT-001** | `academic_content.program_type` | Restricción `CHECK` de periodicidad | Inserción con `program_type = 'semestral'` | PostgreSQL rechaza la fila por violación de CHECK | **PASS** |
| **UT-SQL-CNT-002** | `academic_content.media_type` | Restricción `CHECK` de tipo multimedia | Inserción con `media_type = 'audio'` | Rechazo inmediato por tipo de medio no soportado | **PASS** |
| **UT-SQL-CNT-003** | `center_content.media_type` | Restricción `CHECK` específica de centro | Inserción con `media_type = 'pdf'` | Violación de CHECK (solo `image`, `video`, `text` permitidos) | **PASS** |
| **UT-SQL-CNT-004** | `general_content.category` | Restricción `CHECK` de categoría | Inserción con `category = 'promocion'` | PostgreSQL emite error por categoría fuera de lista blanca | **PASS** |
| **UT-SQL-CNT-005** | `gallery_content.image_path` | Restricción `NOT NULL` | Inserción omitiendo la ruta de imagen | Rechazo por restricción de no nulidad | **PASS** |
| **UT-SQL-CNT-006** | `academic_content` y `general_content` | Asignación de valores por defecto | Inserción omitiendo duración y estado activo | Asigna 15s/10s respectivamente e `is_active = TRUE` | **PASS** |
| **UT-SQL-CNT-007** | `media_files.filename` | Restricción `UNIQUE` | Inserción de dos archivos con el mismo nombre | Rechazo por clave duplicada en índice único | **PASS** |

---

## 3. Resumen Cuantitativo

- **Total de pruebas ejecutadas:** 7
- **Aprobadas:** 7 (100%)
- **Fallidas:** 0 (0%)
- **Asertos evaluados:** 12
- **Persistencia en base de datos:** 0 registros residuales (100% revertidos por ROLLBACK)

---

## 4. Conclusiones y Dictamen

La base de datos impone barreras relacionales infranqueables ante datos corruptos o valores multimedia inválidos. Queda garantizado que los slides proyectados en las pantallas del CSET cumplen con las duraciones predeterminadas de rotación y formatos soportados por el player.
