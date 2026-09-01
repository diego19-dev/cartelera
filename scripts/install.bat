@echo off
REM ===========================================================
REM  IES-TV :: Script de instalación para Windows
REM  Ejecutar como Administrador: tools\install.bat
REM ===========================================================

cd /d "%~dp0.."

echo =====================================================
echo   IES-TV - Instalador para Windows
echo =====================================================
echo.

REM Verificar PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] PHP no esta en el PATH.
    echo Instale PHP 8.2+ desde https://windows.php.net/download/
    pause
    exit /b 1
)

echo [OK] PHP encontrado:
php -v | findstr /C:"PHP"
echo.

REM Verificar psql
where psql >nul 2>nul
if %errorlevel% neq 0 (
    echo [ADVERTENCIA] psql no esta en el PATH.
    echo Para crear la BD necesitara PostgreSQL instalado.
    echo Descargar desde https://www.postgresql.org/download/windows/
    echo.
)

REM Solicitar datos de BD
set /p DB_HOST="Host de PostgreSQL [localhost]: " || set DB_HOST=localhost
set /p DB_PORT="Puerto [5432]: " || set DB_PORT=5432
set /p DB_NAME="Nombre de BD [cset]: " || set DB_NAME=cset
set /p DB_USER="Usuario de BD [ies_user]: " || set DB_USER=ies_user
set /p DB_PASS="Contrasena de BD: "

if "%DB_PASS%"=="" (
    echo [ERROR] Debe ingresar la contrasena
    pause
    exit /b 1
)

echo.
echo Creando base de datos...
echo.

REM Crear BD
psql -U postgres -h %DB_HOST% -p %DB_PORT% -c "CREATE DATABASE %DB_NAME% WITH ENCODING 'UTF8';" 2>nul
if %errorlevel% neq 0 (
    echo La BD ya existe o no se pudo crear. Continuando...
)

psql -U postgres -h %DB_HOST% -p %DB_PORT% -c "CREATE USER %DB_USER% WITH PASSWORD '%DB_PASS%';" 2>nul
if %errorlevel% neq 0 (
    echo El usuario ya existe o no se pudo crear. Continuando...
)

psql -U postgres -h %DB_HOST% -p %DB_PORT% -c "GRANT ALL PRIVILEGES ON DATABASE %DB_NAME% TO %DB_USER%;" 2>nul
psql -U postgres -h %DB_HOST% -p %DB_PORT% -d %DB_NAME% -c "GRANT ALL ON SCHEMA public TO %DB_USER%;" 2>nul

echo [OK] Base de datos creada/configurada.
echo.

REM Cargar schema
echo Cargando schema...
psql -U %DB_USER% -h %DB_HOST% -p %DB_PORT% -d %DB_NAME% -f sql\schema.sql
if %errorlevel% neq 0 (
    echo [ERROR] No se pudo cargar el schema.
    pause
    exit /b 1
)

echo.
echo [OK] Schema cargado.
echo.

REM Generar contraseña real
echo =====================================================
echo   Generacion de contrasena de administrador
echo =====================================================
echo.
echo Ingrese la contrasena para el usuario 'admin':
set /p ADMIN_PASS=

if "%ADMIN_PASS%"=="" set ADMIN_PASS=Admin123!

REM Generar hash bcrypt
php -r "echo password_hash('%ADMIN_PASS%', PASSWORD_BCRYPT);" > temp_hash.txt
set /p HASH=<temp_hash.txt
del temp_hash.txt

REM Actualizar BD
psql -U %DB_USER% -h %DB_HOST% -p %DB_PORT% -d %DB_NAME% -c "UPDATE users SET password_hash = '%HASH%' WHERE username = 'admin';"
echo [OK] Contrasena del admin actualizada.
echo.

REM Actualizar Config.php
echo =====================================================
echo   Configuracion del sistema
echo =====================================================
echo.
echo Editando src\Core\Config.php...

powershell -Command "(Get-Content 'src\Core\Config.php') -replace \"define\('DB_HOST'.*\", \"define('DB_HOST', '%DB_HOST%');\" | Set-Content 'src\Core\Config.php'"
powershell -Command "(Get-Content 'src\Core\Config.php') -replace \"define\('DB_NAME'.*\", \"define('DB_NAME', '%DB_NAME%');\" | Set-Content 'src\Core\Config.php'"
powershell -Command "(Get-Content 'src\Core\Config.php') -replace \"define\('DB_USER'.*\", \"define('DB_USER', '%DB_USER%');\" | Set-Content 'src\Core\Config.php'"
powershell -Command "(Get-Content 'src\Core\Config.php') -replace \"define\('DB_PASS'.*\", \"define('DB_PASS', '%DB_PASS%');\" | Set-Content 'src\Core\Config.php'"

echo [OK] Configuracion actualizada.
echo.

REM Crear carpetas necesarias
if not exist "public\assets\uploads\academic" mkdir public\assets\uploads\academic
if not exist "public\assets\uploads\center" mkdir public\assets\uploads\center
if not exist "public\assets\uploads\general" mkdir public\assets\uploads\general
if not exist "storage\logs" mkdir storage\logs
if not exist "storage\cache" mkdir storage\cache

echo [OK] Estructura de carpetas creada.
echo.

echo =====================================================
echo   Instalacion completada!
echo =====================================================
echo.
echo Acceda al panel admin en:
echo   http://localhost/cset/public/login.php
echo.
echo Usuario: admin
echo Contrasena: %ADMIN_PASS%
echo.
echo Vista TV:
echo   http://localhost/cset/public/index.php
echo.
echo Proximos pasos:
echo   1. Cambie las contrasenas de los usuarios iniciales.
echo   2. Configure las TVs para abrir la URL en kiosko (F11).
echo   3. Programe el cron de datos externos (cada 30 min).
echo.
pause