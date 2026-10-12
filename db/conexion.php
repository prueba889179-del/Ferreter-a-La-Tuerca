<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración flexible compatible tanto con Railway (MySQL Plugin) como con XAMPP local
$dbUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');

if ($dbUrl) {
    // Railway a menudo provee MYSQL_URL o DATABASE_URL: mysql://user:pass@host:port/dbname
    $urlParts = parse_url($dbUrl);
    $host = $urlParts['host'] ?? '127.0.0.1';
    $port = $urlParts['port'] ?? 3306;
    $user = $urlParts['user'] ?? 'root';
    $pass = $urlParts['pass'] ?? '';
    $db   = isset($urlParts['path']) ? ltrim($urlParts['path'], '/') : 'railway';
} else {
    // Variables individuales estándar de Railway o de entorno
    $host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
    $db   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';
    $user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '';
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Auto-inicializar tablas si no existen en Railway / Local
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'productos'")->rowCount();
    if ($tableCheck === 0 && file_exists(__DIR__ . '/datos.sql')) {
        $sql = file_get_contents(__DIR__ . '/datos.sql');
        $sqlClean = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
        $sqlClean = preg_replace('/USE[^;]+;/i', '', $sqlClean);
        $pdo->exec($sqlClean);
    }
} catch (PDOException $e) {
    try {
        // En caso de que la BD específica no exista en local y el usuario tenga permisos
        $dsnWithoutDb = "mysql:host=$host;port=$port;charset=$charset";
        $pdoInit = new PDO($dsnWithoutDb, $user, $pass, $options);
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO($dsn, $user, $pass, $options);

        $check = $pdo->query("SHOW TABLES LIKE 'productos'")->rowCount();
        if ($check === 0 && file_exists(__DIR__ . '/datos.sql')) {
            $sql = file_get_contents(__DIR__ . '/datos.sql');
            $sqlClean = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
            $sqlClean = preg_replace('/USE[^;]+;/i', '', $sqlClean);
            $pdo->exec($sqlClean);
        }
    } catch (PDOException $innerError) {
        $isRailway = getenv('RAILWAY_ENVIRONMENT') || getenv('MYSQLHOST') || getenv('MYSQL_URL');
        $mensajeGuia = $isRailway 
            ? "Verifica que el servicio MySQL en Railway esté desplegado y vinculado (Variables conectadas)."
            : "Asegúrate de que el servidor MySQL en <strong>XAMPP</strong> esté encendido o importa <code>db/datos.sql</code>.";

        die("<div style='background:#fee2e2;color:#991b1b;padding:20px;border-radius:8px;font-family:sans-serif;max-width:600px;margin:50px auto;border:1px solid #f87171;'>
            <h3 style='margin-top:0;'>⚠️ Error de Conexión a la Base de Datos</h3>
            <p><strong>Detalle:</strong> " . htmlspecialchars($innerError->getMessage()) . "</p>
            <p>$mensajeGuia</p>
        </div>");
    }
}
?>
