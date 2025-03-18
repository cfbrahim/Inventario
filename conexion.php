<?php
$host = 'localhost';
$dbname = 'Inventario'; // Nombre correcto de la BD
$username = 'nuevo_usuario';
$password = 'brahim@123a';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>