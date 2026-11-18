import subprocess
import zipfile
import re
import os

html_content = """<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Acta de Entrega de Software</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.3;">

<table border="1" style="width: 100%; border-collapse: collapse;">
  <tr style="background-color: #0d47a1; color: white;">
    <th colspan="7" style="text-align: center; font-size: 12pt; padding: 10px;">
      ACTA DE ENTREGA DE SOFTWARE Y SISTEMAS DE INFORMACIÓN
    </th>
  </tr>

  <tr>
    <td colspan="2" style="background-color: #f5f5f5;"><b>Dependencia Solicitante:</b></td>
    <td colspan="3" style="background-color: #f5f5f5;"><b>Proyecto/Sistema de información:</b></td>
    <td colspan="2" style="background-color: #f5f5f5;"><b>Tipo de entrega:</b></td>
  </tr>
  <tr>
    <td colspan="2">Dirección de Centro / Coordinación Académica CSET</td>
    <td colspan="3"><b>CSET-TV :: Cartelera Digital</b></td>
    <td colspan="2">Parcial [ &nbsp; ] &nbsp;&nbsp;&nbsp; <b>Total [ X ]</b></td>
  </tr>

  <tr>
    <td colspan="2" style="background-color: #f5f5f5;"><b>Módulo:</b></td>
    <td colspan="3" style="background-color: #f5f5f5;"><b>Versión:</b></td>
    <td colspan="2" style="background-color: #f5f5f5;"><b>Autor:</b></td>
  </tr>
  <tr>
    <td colspan="2">Sistema Integral (Player Signage, Panel Admin, API REST, BD, Crons)</td>
    <td colspan="3">2.0.0</td>
    <td colspan="2">Diego Alexander Muñoz Guzmán</td>
  </tr>

  <tr>
    <td colspan="2" style="background-color: #f5f5f5;"><b>Fecha de entrega:</b></td>
    <td colspan="3" style="background-color: #f5f5f5;"><b>Servidor en el que queda instalada la aplicación:</b></td>
    <td colspan="2" style="background-color: #f5f5f5;"><b>Forma de ingreso a la aplicación:</b></td>
  </tr>
  <tr>
    <td colspan="2">18/11/2026</td>
    <td colspan="3">Servidor Institucional CSET (Apache 2.4+ / PHP 8.2+ / PostgreSQL 12+)</td>
    <td colspan="2">Web / HTTP(S):<br>• Player Signage: <code>http://&lt;servidor&gt;/tv/</code><br>• Panel Admin: <code>http://&lt;servidor&gt;/tv/login.php</code></td>
  </tr>

  <tr style="background-color: #f5f5f5;">
    <td colspan="7"><b>Descripción del requerimiento:</b></td>
  </tr>
  <tr>
    <td colspan="7" style="text-align: justify; padding: 8px;">
      Implementación, configuración y puesta en marcha del sistema integral de <b>Cartelería Digital Institucional (CSET-TV)</b>. La solución permite la difusión continua y desatendida (24/7) de contenidos multimedia institucionales en pantallas Smart TV y monitores industriales de señalización digital (LG Signage webOS / Chromium 53).<br><br>
      El sistema dispone de un panel de administración web multi-rol (Administrador, Jefe de Programa y Editor) para la gestión descentralizada de contenidos organizados en tres escenarios rotativos principales (Información Académica, Nuestro Centro e Información General con indicadores económicos y climáticos en tiempo real), soportado por una arquitectura de API REST en PHP y base de datos relacional PostgreSQL.
    </td>
  </tr>

  <tr style="background-color: #1565c0; color: white;">
    <th colspan="7" style="text-align: center; font-size: 11pt; padding: 6px;">
      DETALLE DE COMPONENTES DE SOFTWARE ENTREGABLES
    </th>
  </tr>

  <tr style="background-color: #e3f2fd;">
    <th colspan="3" style="text-align: center; padding: 6px;">Descripción</th>
    <th colspan="4" style="text-align: center; padding: 6px;">Observaciones</th>
  </tr>

  <tr>
    <td colspan="3"><b>1. Player de Cartelera Digital (Frontend Signage):</b><br>Aplicación cliente web ligera construida en ES5 (<code>index.php</code>, <code>src/cartelera.html</code>, <code>js/</code>, <code>css/</code>). Reproducción continua en bucle, transición entre escenarios, soporte multimedia (imágenes, video transcodificado) y polling en segundo plano.</td>
    <td colspan="4">Optimizado para compatibilidad con navegadores embebidos antiguos (ej. LG Signage con Chromium 53) y Smart TVs estándar. No requiere dependencias cliente.</td>
  </tr>

  <tr>
    <td colspan="3"><b>2. Panel de Administración Web:</b><br>Módulo administrativo en PHP (<code>src/panel_*.php</code>) con diseño responsive. Gestión de usuarios, biblioteca de medios, control de escenarios, temas y auditoría de eventos.</td>
    <td colspan="4">Control de acceso basado en roles (RBAC): perfiles Administrador, Jefe de Programa y Editor con permisos segmentados por área. Rate limiting en autenticación.</td>
  </tr>

  <tr>
    <td colspan="3"><b>3. Servicios y API REST:</b><br>Capa de backend en PHP 8 (<code>src/api.php</code> y controladores) que expone endpoints RESTful para entrega de feed público a pantallas, autenticación y operaciones CRUD del panel.</td>
    <td colspan="4">Respuestas en formato JSON, sanitización contra inyección SQL/XSS, control de sesiones seguro y soporte para modo turbo de refresco.</td>
  </tr>

  <tr>
    <td colspan="3"><b>4. Base de Datos Relacional:</b><br>Scripts DDL y DML estructurados para motor PostgreSQL 12+ (<code>scripts/sql/</code>). Modelo relacional con tablas de usuarios, contenidos, logs de auditoría, secuencias e índices.</td>
    <td colspan="4">Incluye datos semilla iniciales y parametrización base lista para ambientes de pruebas y producción.</td>
  </tr>

  <tr>
    <td colspan="3"><b>5. Scripts de Automatización y Mantenimiento:</b><br>Scripts CLI y rutinas programadas (Cron jobs) para sincronización de indicadores externos (clima, dólar, café, noticias) y verificación de integridad.</td>
    <td colspan="4">Incluye rutinas de detección de duración y transcodificación de videos para compatibilidad con pantallas Signage.</td>
  </tr>

  <tr>
    <td colspan="3"><b>6. Documentación Técnica y Requerimientos:</b><br>Repositorio documental completo en carpeta <code>docs/</code>: Manual de despliegue y arquitectura (<code>README.md</code>), especificación de 25 Requerimientos Funcionales (<code>RF-01</code> a <code>RF-25</code>) y Requerimientos No Funcionales.</td>
    <td colspan="4">Provee soporte y trazabilidad completa para el equipo de soporte e infraestructura de la OTI.</td>
  </tr>

  <tr>
    <td colspan="3"><b>7. Suite de Pruebas Unitarias:</b><br>Pruebas automatizadas en PHP (<code>tests/</code>) para borrado seguro de usuarios (<code>UserDeletionTest</code>), cálculo de duración de video (<code>VideoDurationTest</code>) y vigencia de contenidos (<code>ContentValidityTest</code>).</td>
    <td colspan="4">Garantiza estabilidad del sistema frente a futuros mantenimientos y cambios en el código base.</td>
  </tr>

  <tr style="background-color: #1565c0; color: white;">
    <th colspan="7" style="text-align: center; font-size: 11pt; padding: 6px;">
      USUARIOS RESPONSABLES DE LA DEPENDENCIA SOLICITANTE DEL SOFTWARE
    </th>
  </tr>
  <tr>
    <td colspan="7" style="font-style: italic; font-size: 9pt; background-color: #fafafa; padding: 4px;">
      Nombres completos, cargo, dependencia y firma del jefe de área del solicitante del cambio, ajuste o creación del sistema de información:
    </td>
  </tr>

  <tr style="background-color: #e3f2fd;">
    <th style="text-align: center;">Nombre</th>
    <th colspan="3" style="text-align: center;">Dependencia</th>
    <th colspan="2" style="text-align: center;">Cargo</th>
    <th style="text-align: center;">Firma</th>
  </tr>
  <tr style="height: 45px;">
    <td>&nbsp;</td>
    <td colspan="3">&nbsp;</td>
    <td colspan="2">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr style="height: 45px;">
    <td>&nbsp;</td>
    <td colspan="3">&nbsp;</td>
    <td colspan="2">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>

  <tr style="background-color: #0d47a1; color: white;">
    <th colspan="7" style="text-align: center; font-size: 11pt; padding: 6px;">
      USUARIOS RESPONSABLES DE LA OTI DE LA ENTREGA DEL SOFTWARE
    </th>
  </tr>
  <tr>
    <td colspan="7" style="font-style: italic; font-size: 9pt; background-color: #fafafa; padding: 4px;">
      Nombres completos, cargo, dependencia y firma de las personas asignadas para la ejecución del requerimiento:
    </td>
  </tr>

  <tr style="background-color: #e3f2fd;">
    <th style="text-align: center;">Nombre</th>
    <th colspan="3" style="text-align: center;">Dependencia</th>
    <th colspan="2" style="text-align: center;">Cargo</th>
    <th style="text-align: center;">Firma</th>
  </tr>
  <tr style="height: 45px;">
    <td><b>Diego Alexander Muñoz Guzmán</b></td>
    <td colspan="3">Pasante</td>
    <td colspan="2">Desarrollador Frontend-Backend</td>
    <td>____________________</td>
  </tr>
  <tr style="height: 45px;">
    <td>[Líder de Desarrollo / OTI]</td>
    <td colspan="3">Oficina de Tecnologías de la Información (OTI)</td>
    <td colspan="2">Líder Técnico / Coordinador OTI</td>
    <td>____________________</td>
  </tr>

  <tr style="background-color: #f5f5f5;">
    <td colspan="7"><b>OBSERVACIONES GENERALES Y CONDICIONES DE GARANTÍA:</b></td>
  </tr>
  <tr>
    <td colspan="7" style="font-size: 9pt; padding: 6px;">
      • <b>Período de Acompañamiento:</b> Se establece una ventana de estabilización y soporte técnico de <b>30 días calendario</b> a partir de la firma de la presente acta para atender incidencias o ajustes menores.<br>
      • <b>Credenciales de Acceso:</b> La cuenta maestra de administración y las credenciales de base de datos se entregan bajo protocolo de seguridad al responsable designado por la OTI.<br>
      • <b>Hardware Receptor:</b> Se verificó la correcta visualización en Smart TVs y monitores LG Signage bajo resolución 1080p y red local institucional.
    </td>
  </tr>
</table>

</body>
</html>
"""

html_path = "/tmp/acta_entrega.html"
docx_path = "docs/acta_de_entrega.docx"

with open(html_path, "w", encoding="utf-8") as f:
    f.write(html_content)

# Run pandoc
cmd = ["pandoc", html_path, "-f", "html", "-t", "docx", "-o", docx_path]
subprocess.run(cmd, check=True)

# Now enhance table borders and styles in the docx
temp_docx = docx_path + ".tmp"
with zipfile.ZipFile(docx_path, 'r') as zin, zipfile.ZipFile(temp_docx, 'w', compression=zipfile.ZIP_DEFLATED) as zout:
    for item in zin.infolist():
        data = zin.read(item.filename)
        if item.filename == "word/document.xml":
            xml_str = data.decode('utf-8')
            # Inject tblBorders inside w:tblPr if not present
            borders_xml = (
                '<w:tblBorders>'
                '<w:top w:val="single" w:sz="6" w:space="0" w:color="999999"/>'
                '<w:left w:val="single" w:sz="6" w:space="0" w:color="999999"/>'
                '<w:bottom w:val="single" w:sz="6" w:space="0" w:color="999999"/>'
                '<w:right w:val="single" w:sz="6" w:space="0" w:color="999999"/>'
                '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>'
                '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>'
                '</w:tblBorders>'
            )
            # Add cell margins for readability
            margins_xml = (
                '<w:tblCellMar>'
                '<w:top w:w="120" w:type="dxa"/>'
                '<w:left w:w="160" w:type="dxa"/>'
                '<w:bottom w:w="120" w:type="dxa"/>'
                '<w:right w:w="160" w:type="dxa"/>'
                '</w:tblCellMar>'
            )
            xml_str = re.sub(r'(<w:tblPr>.*?)((?:<w:tblBorders>.*?</w:tblBorders>)?)(</w:tblPr>)',
                             r'\1' + borders_xml + margins_xml + r'\3',
                             xml_str, count=1)
            data = xml_str.encode('utf-8')
        zout.writestr(item, data)

os.replace(temp_docx, docx_path)
print("DOCX successfully generated and formatted at:", docx_path)

