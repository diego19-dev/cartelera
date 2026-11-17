# Reporte de Resultados de Pruebas Unitarias SQL: Lógica de Vistas y Cartelera
**Estándar:** ISO/IEC/IEEE 29119-3:2021 (Test Documentation)  
**Sistema:** Sistema de Cartelera Digital CSET-TV  
**Módulo:** `ViewsBusinessLogicTest.php` (Lógica de Negocio en Vistas SQL)  
**Motor de Base de Datos:** PostgreSQL 16.15  
**Fecha de Ejecución:** 2026-11-17  
**Ejecutor:** PHPUnit 10.5.64 (`--testsuite UnitariasSql`)  
**Estrategia Transaccional:** Aislamiento estricto con `ROLLBACK` en `tearDown()` (Zero-Side-Effects)  
**Resultado Global:** 6 / 6 APROBADOS (100%)

---

## 1. Identificación y Objetivos

El presente documento certifica los resultados de las pruebas de lógica de negocio encapsuladas en las vistas relacionales de PostgreSQL: `v_active_academic`, `v_active_center`, `v_active_general` y `v_dashboard_stats`. Estas vistas son el canal principal a través del cual la API REST del televisor (`/api/tv`) y el panel de administración consultan los contenidos programados.

---

## 2. Matriz de Cobertura y Casos de Prueba

| ID Caso | Vista Evaluada | Condición de Prueba | Entrada / Contexto | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **UT-SQL-VEW-001** | `v_active_academic` | Filtrado de estado inactivo | Registro con `is_active = FALSE` | La vista no expone el contenido inactivo | **PASS** |
| **UT-SQL-VEW-002** | `v_active_academic` | Ventana temporal de vigencia | Contenido futuro, vencido y actualmente vigente | Solo el registro con ventana vigente se proyecta en la vista | **PASS** |
| **UT-SQL-VEW-003** | `v_active_center` | Orden secuencial de emisión | Dos registros con `display_order = 10` y `50` | La vista los recupera en orden ascendente por `display_order` | **PASS** |
| **UT-SQL-VEW-004** | `v_active_general` | Expiración de noticias | Noticias con `expire_date` en el pasado y futuro | Las noticias caducadas quedan automáticamente excluidas | **PASS** |
| **UT-SQL-VEW-005** | `v_dashboard_stats` | Agregación de indicadores | Lectura de métricas del sistema | Retorna conteos enteros no nulos de usuarios, medios y sesiones | **PASS** |
| **UT-SQL-VEW-006** | Integridad transaccional | Reactividad de vistas | Inserción en transacción viva antes de `commit` | Las vistas reflejan de forma atómica e inmediata la nueva información | **PASS** |

---

## 3. Resumen Cuantitativo

- **Total de pruebas ejecutadas:** 6
- **Aprobadas:** 6 (100%)
- **Fallidas:** 0 (0%)
- **Asertos evaluados:** 22
- **Persistencia en base de datos:** 0 registros residuales (100% revertidos por ROLLBACK)

---

## 4. Conclusiones y Dictamen

Las vistas de PostgreSQL procesan y encapsulan con absoluta exactitud las reglas de negocio de expiración y visibilidad, descargando a la aplicación PHP de procesamiento adicional y garantizando que el televisor físico nunca proyecte piezas caducadas o dadas de baja.
