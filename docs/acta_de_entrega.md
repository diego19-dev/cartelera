# ACTA DE ENTREGA DE SOFTWARE Y SISTEMAS DE INFORMACIÓN

---

| **Dependencia Solicitante:** | [Nombre de la Dependencia Solicitante / Dirección CSET] | **Proyecto / Sistema de Información:** | CSET-TV :: Cartelera Digital | **Tipo de Entrega:** | Parcial [ ] &nbsp;&nbsp;&nbsp;&nbsp; **Total [X]** |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Módulo:** | Sistema Integral (Player Signage, Panel Admin, API REST, Base de Datos, Crons) | **Versión:** | 2.0.0 | **Autor / Desarrollador:** | Diego Alexander Muñoz Guzmán |
| **Fecha de Entrega:** | 18/11/2026 | **Servidor de Instalación:** | Servidor Institucional CSET (Apache 2.4+ / PHP 8.2+ / PostgreSQL 12+) | **Forma de Ingreso:** | Web / HTTP(S)<br>• **Cartelera Signage:** `http://<servidor>/tv/`<br>• **Panel Admin:** `http://<servidor>/tv/login.php` |

---

### Descripción del Requerimiento

Implementación, configuración y puesta en marcha del sistema integral de **Cartelería Digital Institucional (CSET-TV)**. La solución permite la difusión continua y desatendida (24/7) de contenido multimedia institucional en pantallas Smart TV y monitores industriales de señalización digital (LG Signage webOS / Chromium 53). 

El sistema cuenta con un panel de administración web multi-rol (Administrador, Jefe de Programa y Editor) para la gestión descentralizada de contenidos organizados en tres escenarios rotativos principales (Información Académica, Nuestro Centro e Información General con indicadores económicos y climáticos en tiempo real), soportado por una arquitectura de API REST en PHP y base de datos relacional PostgreSQL.

---

### Detalle de Componentes de Software Entregables

| No. | Componente / Entregable | Descripción | Observaciones |
| :---: | :--- | :--- | :--- |
| **1** | **Player de Cartelera Digital (Frontend Signage)** | Aplicación cliente web ligera construida en ES5 (`index.php`, `src/cartelera.html`, `js/`, `css/`). Gestiona la reproducción en bucle, transición entre escenarios, soporte multimedia (imágenes, videos transcodificados) y sincronización por polling. | Optimizado para compatibilidad con navegadores embebidos antiguos (ej. LG Signage con Chromium 53) y Smart TVs estándar. |
| **2** | **Panel de Administración Web** | Módulo de gestión administrativa en PHP (`src/panel_*.php`) con interfaz responsive. Incluye autenticación con rate limiting, gestión de usuarios, biblioteca multimedia, auditoría de eventos y configuración de temas/branding. | Control de acceso basado en roles (RBAC): perfiles de Administrador, Jefe de Programa y Editor con permisos segmentados por área. |
| **3** | **Servicios y API REST** | Capa de servicios backend en PHP 8 (`src/api.php` y controladores) que expone endpoints RESTful para la entrega del feed público a los monitores, autenticación segura y operaciones CRUD del panel administrativo. | Incluye sanitización de entradas, protección CSRF y rate limiting preventivo ante ataques de fuerza bruta. |
| **4** | **Base de Datos Relacional** | Scripts DDL y DML estructurados para motor PostgreSQL 12+ (`scripts/sql/`). Contiene el modelo de datos relacional, tablas de usuarios, contenidos, logs de auditoría, secuencias e índices de rendimiento. | Incluye datos semilla iniciales y estructura lista para ambientes de pruebas y producción. |
| **5** | **Scripts de Automatización y Mantenimiento** | Scripts en línea de comandos (CLI) y configuración para tareas programadas (Cron jobs) que actualizan periódicamente datos externos (clima, dólar, café, noticias) y herramientas de verificación (`scripts/`). | Incluye rutinas para detección de duración y transcodificación de videos para compatibilidad con Signage. |
| **6** | **Documentación Técnica y Requerimientos** | Repositorio documental completo ubicado en el directorio `docs/`: Guía general de arquitectura y despliegue (`README.md`), especificación de 25 Requerimientos Funcionales (`RF-01` a `RF-25`) y Requerimientos No Funcionales. | Documentación técnica detallada para el personal de soporte e infraestructura de la OTI. |
| **7** | **Suite de Pruebas Unitarias** | Pruebas unitarias automatizadas en PHP (`tests/`) para validación de lógica crítica: integridad en borrado de usuarios (`UserDeletionTest`), cálculo de duración de video (`VideoDurationTest`) y vigencia de contenidos (`ContentValidityTest`). | Permite verificar la estabilidad del código ante futuros mantenimientos o actualizaciones. |

---

### Requisitos de Infraestructura y Clientes Verificados

- **Servidor Web y Backend:** Apache 2.4+ (`mod_rewrite` habilitado), PHP 8.0+ (recomendado 8.2+) con extensiones `pdo_pgsql`, `fileinfo`, `mbstring`, `openssl`, `gd`.
- **Motor de Base de Datos:** PostgreSQL 12 o superior con soporte UTF-8.
- **Dispositivos Clientes / Monitores:** Pantallas Smart TV o monitores LG Signage con conectividad de red LAN o Wi-Fi estable y resolución recomendada 1080p (Full HD) o 4K (16:9).

---

### Usuarios Responsables de la Dependencia Solicitante del Software

*Certifican la recepción a satisfacción del sistema de información descrito en la presente acta, conforme a las especificaciones y requerimientos concertados.*

| Nombre Completo | Dependencia | Cargo | Firma |
| :--- | :--- | :--- | :--- |
| &nbsp; | &nbsp; | &nbsp; | &nbsp; |
| &nbsp; | &nbsp; | &nbsp; | &nbsp; |
| &nbsp; | &nbsp; | &nbsp; | &nbsp; |

---

### Usuarios Responsables de la OTI de la Entrega del Software

*Certifican la entrega del código fuente, componentes de software, scripts de base de datos y documentación técnica asociada al requerimiento.*

| Nombre Completo | Dependencia | Cargo | Firma |
| :--- | :--- | :--- | :--- |
| **Diego Alexander Muñoz Guzmán** | Pasante | Desarrollador Frontend-Backend | _______________________ |
| [Nombre del Líder OTI / Coordinador] | Oficina de Tecnologías (OTI) | Líder de Desarrollo / Coordinador TI | _______________________ |
| &nbsp; | &nbsp; | &nbsp; | &nbsp; |

---

### Observaciones Finales y Garantía

- **Período de Acompañamiento:** Se establece una ventana de estabilización y soporte técnico de **30 días calendario** a partir de la firma de la presente acta para atender incidencias o dudas operativas.
- **Credenciales de Acceso:** La cuenta maestra del administrador y las credenciales de conexión a la base de datos se entregan por canal seguro al delegado técnico de la dependencia.

