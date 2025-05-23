
<?php
require_once 'check.php'; // Verifica acceso y sesión

// Solo permitir empleados y admin
if ($_SESSION['user_type'] !== 'empleado' && $_SESSION['user_type'] !== 'admin') {
    header('Location: acceso_denegado.php');
    exit();
}

// Obtener datos del empleado
require_once 'conexion.php';
$stmt = $pdo->prepare("
    SELECT p.nombre, p.apellido1, c.cargo AS nombre_cargo 
    FROM personal p
    JOIN cargo c ON p.cargo = c.id_cargo
    WHERE p.id_personal = ?
");
$stmt->execute([$_SESSION['user_id']]);
$empleado = $stmt->fetch();

// Determinar si es admin basado en el nombre del cargo
$es_admin = (strtolower($empleado['nombre_cargo']) == 'admin');
$_SESSION['user_type'] = $es_admin ? 'admin' : 'empleado';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
    <link rel="stylesheet" href="styles/css_gestionP.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="panel-container">
        <div class="panel-header">
            <h1 class="panel-title">Gestión de Productos</h1>
            <p class="panel-subtitle">Administra el catálogo de productos</p>
            <div class="deco-line"></div>
        </div>

        <div class="buttons-grid">
            <a href="agregar_producto.php" class="panel-btn">
                <i class="fas fa-plus-circle"></i>
                Agregar Producto
            </a>
            <?php if ($es_admin): ?>
            <a href="modificar_producto.php" class="panel-btn">
                <i class="fas fa-edit"></i>
                Modificar Producto
            </a>

            <a href="eliminar_producto.php" class="panel-btn">
                <i class="fas fa-trash-alt"></i>
                Eliminar Producto
            </a>
            <?php endif; ?>      
            <a href="ver_productos.php" class="panel-btn">
                <i class="fas fa-list-ul"></i>
                Ver Productos
            </a>
        </div>

        <div class="user-info">
            <p>Has iniciado sesión como: <strong><?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido1']) ?></strong></p>
            <p>Cargo: <strong><?= htmlspecialchars($empleado['nombre_cargo']) ?></strong></p>
        </div>

        <a href="panel_empleado.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </a>
    </div>
</body>
</html>