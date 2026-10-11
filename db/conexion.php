<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
$port     = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
$db       = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'abogados_socios';
$user     = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$pass     = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    try {
        $dsnWithoutDb = "mysql:host=$host;port=$port;charset=$charset";
        $pdoInit = new PDO($dsnWithoutDb, $user, $pass, $options);
        $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO($dsn, $user, $pass, $options);

        $check = $pdo->query("SHOW TABLES LIKE 'usuarios'")->rowCount();
        if ($check === 0 && file_exists(__DIR__ . '/datos.sql')) {
            $sql = file_get_contents(__DIR__ . '/datos.sql');
            $pdo->exec($sql);
        }
    } catch (PDOException $innerError) {
        die("<div style='background:#fee2e2;color:#991b1b;padding:20px;border-radius:8px;font-family:sans-serif;max-width:600px;margin:50px auto;border:1px solid #f87171;'>
            <h3 style='margin-top:0;'>⚠️ Error de Conexión a la Base de Datos</h3>
            <p><strong>Detalle:</strong> " . htmlspecialchars($innerError->getMessage()) . "</p>
            <p>Asegúrate de que el servidor MySQL en <strong>XAMPP</strong> esté encendido, o importa el script <code>db/datos.sql</code> en <strong>DBeaver / phpMyAdmin</strong>.</p>
        </div>");
    }
}
?>
