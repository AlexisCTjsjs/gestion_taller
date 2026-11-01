<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `motor_express_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `motor_express_db`;");

    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        die("Error: No se encontró el archivo schema.sql en " . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);

    $pdo->exec($sql);

    echo "<h3>Base de datos 'motor_express_db' creada y configurada con exito (Tablas limpias sin registros de prueba)!</h3>";
    echo "<p>Puedes registrar tu primer usuario en: <a href='../register.php'>Registrar Usuario</a> o iniciar en <a href='../login.php'>Login</a></p>";

} catch (PDOException $e) {
    echo "<h3>Error al configurar la base de datos:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
