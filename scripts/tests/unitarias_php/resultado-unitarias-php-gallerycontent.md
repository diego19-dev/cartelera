# Reporte de Resultados de Pruebas Unitarias: GalleryContent
**Estándar:** ISO/IEC/IEEE 29119-3:2021 (Test Documentation)  
**Sistema:** Sistema de Cartelera Digital CSET-TV  
**Módulo:** `GalleryContent.php` (Modelo de Contenido de Galería Institucional)  
**Fecha de Ejecución:** 2026-11-13  
**Ejecutor:** PHPUnit 10.5.64  
**Ambiente:** PHP 8.5.8 CLI — Linux 6.6  
**Resultado Global:** 6 / 6 APROBADOS (100%)

---

## 1. Identificación y Objetivos

El presente informe documenta las pruebas unitarias realizadas sobre la clase `GalleryContent` (`src/GalleryContent.php`), componente del modelo de datos encargado de la normalización, sanitización y casteo estricto de los campos antes de su inserción y actualización en base de datos (estados activos, duraciones, cadenas vacías y ventanas temporales de vigencia).

---

## 2. Matriz de Cobertura y Casos de Prueba

| ID Caso | Método Evaluado | Condición de Prueba | Entrada / Contexto | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **UT-PHP-GAL-001** | `normalizeData()` | Conversión booleana | Valores truthy/falsy (`1`, `'1'`, `true`, `0`, `'0'`, `false`) | Normaliza a `bool` estricto en la clave `is_active` | **PASS** |
| **UT-PHP-GAL-002** | `normalizeData()` | Casting de enteros | Valores tipo string en `duration_seconds` y `created_by` | Castea estrictamente a `int` (`duration_seconds=15`, `created_by=7`) | **PASS** |
| **UT-PHP-GAL-003** | `normalizeData()` | Título en blanco | Cadenas con espacios en blanco o tabulaciones | Convierte a `null` al no contener texto visible | **PASS** |
| **UT-PHP-GAL-004** | `normalizeData()` | Fechas de vigencia vacías | `show_from = ''`, `show_until = '   '` | Transforma las claves en `null` para campos de fecha | **PASS** |
| **UT-PHP-GAL-005** | `normalizeData()` | Fechas de vigencia válidas | `show_from = '2026-11-13 08:00:00'` | Conserva el valor exacto de la fecha suministrada | **PASS** |
| **UT-PHP-GAL-006** | Reflexión de API | Métodos de ciclo de vida | Inspección de clase `GalleryContent` | Expone métodos estáticos `normalizeData`, `all`, `find`, `create`, `update`, `delete`, `reorder` | **PASS** |

---

## 3. Resumen Cuantitativo

- **Total de pruebas ejecutadas:** 6
- **Aprobadas:** 6 (100%)
- **Fallidas:** 0 (0%)
- **Asertos evaluados:** 14
- **Tiempo de ejecución:** 0.012 s
- **Consumo de memoria:** 8.00 MB

---

## 4. Conclusiones y Dictamen

La clase `GalleryContent` demuestra una normalización robusta de tipos y valores nulos, impidiendo que registros malformados o con cadenas en blanco afecten la programación de la galería y la integridad del motor de renderizado de la cartelera digital.
