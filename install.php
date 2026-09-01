<?php
/**
 * Instalador PHP para CSET-TV
 * Uso CLI: php install.php
 *
 * Crea la base de datos, usuario y carga el schema sin necesidad de psql
 */

// Configurar para mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<pre style='font-family:monospace;font-size:14px;'>";
echo "=====================================================\n";
echo " SENA CSET - Instalador PHP (no requiere psql)\n";
echo "=====================================================\n\n";

// Solicitar credenciales de superusuario de PostgreSQL
echo "PASO 1: Conexion como superusuario de PostgreSQL\n";
echo "(Necesitamos un usuario con permisos para CREATE DATABASE)\n\n";

echo "Usuario postgres [postgres]: ";
$pgUser = trim(fgets(STDIN));
if (empty($pgUser)) $pgUser = 'postgres';

echo "Contrasena: ";
$pgPass = trim(fgets(STDIN));

echo "Host [localhost]: ";
$pgHost = trim(fgets(STDIN));
if (empty($pgHost)) $pgHost = 'localhost';

echo "Puerto [5432]: ";
$pgPort = trim(fgets(STDIN));
if (empty($pgPort)) $pgPort = '5432';

echo "\n";

// Probar conexion como superusuario
echo "Probando conexion como '$pgUser'...\n";
try {
    $dsn = "pgsql:host=$pgHost;port=$pgPort;dbname=postgres";
    $pdo = new PDO($dsn, $pgUser, $pgPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "[OK] Conectado como superusuario\n\n";
} catch (PDOException $e) {
    echo "[FAIL] No se pudo conectar: " . $e->getMessage() . "\n";
    echo "Verifica usuario, contraseña y que PostgreSQL esté corriendo.\n";
    exit(1);
}

// Datos de la nueva BD
echo "PASO 2: Datos de la base de datos db_cset\n\n";
echo "Nombre de BD [cset]: ";
$dbName = trim(fgets(STDIN));
if (empty($dbName)) $dbName = 'db_cset';

echo "Usuario de la app [postgres]: ";
$dbUser = trim(fgets(STDIN));
if (empty($dbUser)) $dbUser = 'postgres';

echo "Contrasena del usuario de la app: ";
$dbPass = trim(fgets(STDIN));
if (empty($dbPass)) {
    echo "\n[FAIL] La contrasena no puede estar vacia\n";
    exit(1);
}

echo "\n";

// Verificar si la BD existe
echo "PASO 3: Creando base de datos...\n";
try {
    $exists = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '$dbName'")->fetchColumn();
    if ($exists) {
        echo "[!] La BD '$dbName' ya existe. Continuando...\n";
    } else {
        $pdo->exec("CREATE DATABASE $dbName WITH ENCODING 'UTF8'");
        echo "[OK] Base de datos '$dbName' creada\n";
    }
} catch (PDOException $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar si el usuario existe
echo "\nPASO 4: Creando usuario de aplicacion...\n";
try {
    $exists = $pdo->query("SELECT 1 FROM pg_user WHERE usename = '$dbUser'")->fetchColumn();
    if ($exists) {
        echo "[!] El usuario '$dbUser' ya existe. Actualizando contrasena...\n";
        $pdo->exec("ALTER USER $dbUser WITH PASSWORD '$dbPass'");
    } else {
        $pdo->exec("CREATE USER $dbUser WITH PASSWORD '$dbPass'");
        echo "[OK] Usuario '$dbUser' creado\n";
    }
} catch (PDOException $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
    exit(1);
}

// Otorgar privilegios
echo "\nPASO 5: Otorgando privilegios...\n";
try {
    $pdo->exec("GRANT ALL PRIVILEGES ON DATABASE $dbName TO $dbUser");
    // Conectar a la nueva BD para dar permisos sobre el schema
    $dsn = "pgsql:host=$pgHost;port=$pgPort;dbname=$dbName";
    $pdo2 = new PDO($dsn, $pgUser, $pgPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo2->exec("GRANT ALL ON SCHEMA public TO $dbUser");
    echo "[OK] Privilegios otorgados\n";
} catch (PDOException $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
}

// Cargar el schema
echo "\nPASO 6: Cargando schema (sql/schema.sql)...\n";
$schemaFile = __DIR__ . '/../../sql/schema.sql';
if (!file_exists($schemaFile)) {
    echo "[FAIL] No se encuentra el archivo sql/schema.sql\n";
    exit(1);
}

$schema = file_get_contents($schemaFile);

// Conectar como el nuevo usuario para crear las tablas
try {
    $dsn = "pgsql:host=$pgHost;port=$pgPort;dbname=$dbName";
    $pdoApp = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Ejecutar el schema
    $pdoApp->exec($schema);
    echo "[OK] Schema cargado correctamente\n";
} catch (PDOException $e) {
    echo "[FAIL] Error al cargar schema: " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar tablas creadas
echo "\nPASO 7: Verificando tablas...\n";
try {
    $tables = $pdoApp->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
    echo "[OK] " . count($tables) . " tablas creadas:\n";
    foreach ($tables as $t) {
        echo "      - $t\n";
    }
} catch (PDOException $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
}

// Actualizar Config.php
echo "\nPASO 8: Actualizando src/Core/Config.php...\n";
$configFile = __DIR__ . '/../Core/Config.php';
$config = file_get_contents($configFile);

$config = preg_replace(
    "/define\('DB_HOST',\s*[^)]+\)/",
    "define('DB_HOST', '$pgHost')",
    $config
);
$config = preg_replace(
    "/define\('DB_PORT',\s*[^)]+\)/",
    "define('DB_PORT', '$pgPort')",
    $config
);
$config = preg_replace(
    "/define\('DB_NAME',\s*[^)]+\)/",
    "define('DB_NAME', '$dbName')",
    $config
);
$config = preg_replace(
    "/define\('DB_USER',\s*[^)]+\)/",
    "define('DB_USER', '$dbUser')",
    $config
);
$config = preg_replace(
    "/define\('DB_PASS',\s*[^)]+\)/",
    "define('DB_PASS', '$dbPass')",
    $config
);
$config = preg_replace(
    "/define\('APP_URL',\s*[^)]+\)/",
    "define('APP_URL', 'http://$pgHost/cset/public')",
    $config
);

if (file_put_contents($configFile, $config)) {
    echo "[OK] Config.php actualizado\n";
    echo "      BD: $dbName\n";
    echo "      Usuario: $dbUser\n";
} else {
    echo "[FAIL] No se pudo actualizar Config.php (permisos?)\n";
}

// Generar password de admin
echo "\nPASO 9: Generar contrasena de administrador\n";
echo "Contrasena para el usuario 'admin': ";
$adminPass = trim(fgets(STDIN));
if (empty($adminPass)) {
    $adminPass = 'Admin' . rand(1000, 9999);
    echo "Usando generada: $adminPass\n";
}

$hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
try {
    $stmt = $pdoApp->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);
    echo "[OK] Contrasena del admin actualizada\n";
} catch (PDOException $e) {
    echo "[FAIL] " . $e->getMessage() . "\n";
}

echo "\n=====================================================\n";
echo " INSTALACION COMPLETADA\n";
echo "=====================================================\n\n";

echo "CREDENCIALES:\n";
echo "  Panel admin: http://$pgHost/cset/public/login.php\n";
echo "  Usuario: admin\n";
echo "  Contrasena: $adminPass\n\n";
echo "  Vista TV: http://$pgHost/cset/public/index.php\n\n";

echo "PROXIMOS PASOS:\n";
echo "1. Abre la URL del panel admin en el navegador\n";
echo "2. Cambia las contrasenas de los usuarios jp_* y comunicaciones\n";
echo "3. Comienza a cargar contenido\n\n";

echo "</pre>";
