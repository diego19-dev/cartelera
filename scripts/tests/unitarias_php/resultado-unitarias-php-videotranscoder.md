# CSET-TV — Caso de Prueba Unitaria (PHP)
Resultado 11: VideoTranscoder — 5 casos, 5 aprobados

---

## ENCABEZADO

| | |
|:---|:---|
| **Nombre del proyecto** | CSET-TV Cartelera Digital |
| **Módulo / Componente** | Transcodificación de video para LG Signage (`src/VideoTranscoder.php`) |
| **Versión del código** | Código en repositorio local. `src/VideoTranscoder.php` — rama principal. |
| **Responsable de la prueba** | Equipo de Desarrollo CSET-TV |
| **Fecha(s) de ejecución** | 2026-11-12 |
| **Entorno de prueba** | Local (desarrollo). PHP 8.5.8, PHPUnit 10.5.64. Bootstrap aislado `bootstrap_unitarias.php`. |

---

## RESUMEN DE RESULTADOS

| Total de casos | Aprobados | Fallidos | Bloqueados | Porcentaje de éxito |
|:---:|:---:|:---:|:---:|:---:|
| 5 | 5 | 0 | 0 | 100% |

---

## CASOS DE PRUEBA

### UT-PHP-VT-001: isAvailable devuelve un valor booleano según presencia de ffmpeg
- **Objetivo**: Verificar que el chequeo de disponibilidad sea un booleano estricto.
- **Resultado obtenido**: Aprobado.

### UT-PHP-VT-002: ffmpegBin retorna una cadena no vacía o null
- **Objetivo**: Validar detección de la ruta ejecutable de ffmpeg en el sistema.
- **Resultado obtenido**: Aprobado (`/usr/bin/ffmpeg`).

### UT-PHP-VT-003: toSignageMp4 lanza RuntimeException si el archivo fuente no existe
- **Objetivo**: Prevenir ejecución innecesaria de subprocesos cuando el archivo origen no se puede leer.
- **Resultado obtenido**: RuntimeException capturada con mensaje exacto. Aprobado.

### UT-PHP-VT-004..005: Firmas y parámetros obligatorios de VideoTranscoder
- **Objetivo**: Validar firmas de métodos públicos estáticos y argumentos no nulos.
- **Resultado obtenido**: Aprobado.
