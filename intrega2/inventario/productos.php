
<?php
session_start();
require_once 'conexion.php';

// Verificación de acceso
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Control de roles mejorado
if ($_SESSION['user_type'] === 'empleado') {
    $stmt = $pdo->prepare("SELECT cargo FROM personal WHERE id_personal = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $empleado = $stmt->fetch();
    
    // Verificar nombre del cargo en lugar de ID
    $stmt_cargo = $pdo->prepare("SELECT cargo FROM cargo WHERE id_cargo = ?");
    $stmt_cargo->execute([$empleado['cargo']]);
    $cargo = $stmt_cargo->fetch();
    
    if (strtoupper($cargo['cargo']) !== 'ADMINISTRADOR') {
        header('Location: panel_empleado.php');
        exit();
    }
} elseif ($_SESSION['user_type'] !== 'cliente') {
    header('Location: login.php');
    exit();
}

// Obtener productos con JOIN
try {
    $stmt = $pdo->query("
        SELECT 
            p.referencia AS referencia,
            p.nombre_producto AS nombre,
            f.familia AS familia,
            p.precio,
            p.cantidad
        FROM productos p
        JOIN familia_prod f  ON p.familia_producto = f.id_familia
    ");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generar rutas de imágenes seguras
    foreach ($productos as &$producto) {
        $num_ref = preg_replace('/[^0-9]/', '', $producto['referencia']);
        $producto['imagen'] = (file_exists("img/inv$num_ref.png"))
                            ? "img/inv$num_ref.png"
                            : "img/inv1.png";
    }
    
} catch (PDOException $e) {
    die("Error al cargar productos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos</title>
    <link rel="stylesheet" href="styles/cssp.css">
</head>
<body>
    <div class="productos-container">
        <div class="productos-grid">
            <?php foreach ($productos as $producto): ?>
                <div class="producto-card">
                    <img src="<?= $producto['imagen'] ?>" 
                         alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                         class="producto-img">
                    <div class="producto-info">
                        <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                        <p><strong>Referencia:</strong> <?= $producto['referencia'] ?></p>
                        <p><strong>Categoría:</strong> <?= $producto['familia'] ?></p>
                        <p class="precio"><?= number_format($producto['precio'], 2) ?> €</p>
                        <p class="stock">Disponibles: <?= $producto['cantidad'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
          <!-- Botón de Cerrar Sesión -->
          <div style="position: fixed; top: 20px; right: 20px;">
            <a href="logout.php" style="background: var(--deep-sky); color: white; padding: 10px 20px; border-radius: 25px; text-decoration: none;">
                Cerrar Sesión
            </a>
        </div>
    </div>
</body>
</html>