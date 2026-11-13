# Reporte de Resultados de Pruebas Unitarias: BaseController
**Estándar:** ISO/IEC/IEEE 29119-3:2021 (Test Documentation)  
**Sistema:** Sistema de Cartelera Digital CSET-TV  
**Módulo:** `BaseController.php` (Clase base de controladores de API y Panel)  
**Fecha de Ejecución:** 2026-11-13  
**Ejecutor:** PHPUnit 10.5.64  
**Ambiente:** PHP 8.5.8 CLI — Linux 6.6  
**Resultado Global:** 7 / 7 APROBADOS (100%)

---

## 1. Identificación y Objetivos

El presente documento certifica los resultados de la ejecución de pruebas unitarias sobre la clase `BaseController` (`src/BaseController.php`), componente estructural responsable de la resolución de URLs públicas para recursos multimedia, la filtración de contenido según roles y áreas académicas delegadas (`filterByArea`), el consumo uniforme de payloads JSON y la verificación de módulos habilitados en el panel.

---

## 2. Matriz de Cobertura y Casos de Prueba

| ID Caso | Método Evaluado | Condición de Prueba | Entrada / Contexto | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **UT-PHP-BC-001** | `mediaUrl()` | Ruta vacía o nula | `$path = null`, `$path = ''` | Retorna `null` sin procesar prefijos | **PASS** |
| **UT-PHP-BC-002** | `mediaUrl()` | URLs externas absolutas | `http://` y `https://` | Retorna la URL externa idéntica sin alterar | **PASS** |
| **UT-PHP-BC-003** | `mediaUrl()` | Ruta relativa local | `$path = 'banner.jpg'`, categoría `'general'` | Retorna `/cartelera/assets/uploads/general/banner.jpg` | **PASS** |
| **UT-PHP-BC-004** | `filterByArea()` | Rol `admin` | Sesión con rol `admin` y lista con áreas mixtas | Retorna el 100% de los elementos sin filtrar | **PASS** |
| **UT-PHP-BC-005** | `filterByArea()` | Rol `program_head` | Sesión con rol `program_head` y área `'tecnologia'` | Conserva únicamente items del área `'tecnologia'` o con área `null` | **PASS** |
| **UT-PHP-BC-006** | `filterByArea()` | Rol `editor` | Sesión con rol `editor` y lista de elementos | Retorna todos los registros sin aplicar restricciones | **PASS** |
| **UT-PHP-BC-007** | Reflexión de API | Métodos protegidos expuestos | Inspección de clase `BaseController` | Expone `mediaUrl`, `filterByArea`, `requirePanelModule` y `jsonInput` | **PASS** |

---

## 3. Resumen Cuantitativo

- **Total de pruebas ejecutadas:** 7
- **Aprobadas:** 7 (100%)
- **Fallidas:** 0 (0%)
- **Asertos evaluados:** 19
- **Tiempo de ejecución:** 0.013 s
- **Consumo de memoria:** 8.00 MB

---

## 4. Conclusiones y Dictamen

La suite `BaseControllerTest` valida satisfactoriamente la integridad de la capa base de controladores, certificando el aislamiento de seguridad para roles jerárquicos como líderes de programa y garantizando la resolución consistente de recursos estáticos y multimedia requeridos por la cartelera.
