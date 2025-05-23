<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'conexion.php';

// Inicializar variables
$es_cliente = false;
$es_admin = false;
$usuario = null;

// Verificar si es cliente
$stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();

if ($cliente) {
    $es_cliente = true;
    $usuario = $cliente;
} else {
    // Verificar si es admin
    $stmt = $pdo->prepare("SELECT * FROM personal p
                           INNER JOIN cargo c WHERE p.id_personal = ? AND  c.cargo = 'admin'");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        $es_admin = true;
        $usuario = $admin;
        // Redirigir a panel de admin si es necesario
        header('Location: panel_cliente .php');
        exit();
    } else {
        // No es cliente ni admin
        session_destroy();
        header('Location: login.php');
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Cliente</title>
    <link rel="stylesheet" href="styles/cssp_cliente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   
</head>
<body>
    <div class="panel-container">
        <div class="panel-header">
            <h1 class="panel-title">Panel del Cliente</h1>
            <p class="panel-subtitle">Bienvenido/a a tu área personal</p>
            <div class="deco-line"></div>
        </div>

        <div class="buttons-grid">
            <a href="mis_pedidos.php" class="panel-btn">
                <i class="fas fa-clipboard-list"></i>
                Mis Pedidos
            </a>

            <a href="productos.php" class="panel-btn">
                <i class="fas fa-cart-plus"></i>
                Realizar Pedido
            </a>

            <a href="editar_perfil.php" class="panel-btn">
                <i class="fas fa-user-edit"></i>
                Editar Perfil
            </a>

            <a href="direcciones.php" class="panel-btn">
                <i class="fas fa-map-marker-alt"></i>
                Mis Direcciones
            </a>

            <a href="favoritos.php" class="panel-btn">
                <i class="fas fa-heart"></i>
                Favoritos
            </a>

            <a href="soporte.php" class="panel-btn">
                <i class="fas fa-headset"></i>
                Soporte
            </a>
        </div>
        <div class="user-info">
            <p>Has iniciado sesión como: <strong><?= htmlspecialchars($usuario['nombre'] . ' ' . ($usuario['apellido1'] ?? '')) ?></strong></p>
            <p>Email: <strong><?= htmlspecialchars($usuario['email']) ?></strong></p>
            <a href="login.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>
    </div>
</body>
</html>