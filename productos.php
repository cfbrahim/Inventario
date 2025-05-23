<?php
session_start();
require_once 'conexion.php';

// Procesar añadir a cesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $referencia = $_POST['referencia'];
    $cantidad = max(1, intval($_POST['cantidad'])); // Mínimo 1
    
    if (!isset($_SESSION['cesta'])) {
        $_SESSION['cesta'] = [];
    }
    
    if (isset($_SESSION['cesta'][$referencia])) {
        $_SESSION['cesta'][$referencia] += $cantidad;
    } else {
        $_SESSION['cesta'][$referencia] = $cantidad;
    }
    
    // Mostrar mensaje de confirmación
    $_SESSION['cart_message'] = "Producto añadido a la cesta";
    header("Location: ".$_SERVER['PHP_SELF']);
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
            p.cantidad AS stock,
            p.img_prod AS imagen_ruta
        FROM productos p
        JOIN familia_prod f ON p.familia_producto = f.id_familia
    ");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar rutas de imágenes
    foreach ($productos as &$producto) {
        if (!empty($producto['imagen_ruta'])) {
            $ruta_imagen = trim($producto['imagen_ruta']);
            
            if ((strpos($ruta_imagen, 'img/') === 0 && file_exists($ruta_imagen)) || 
                file_exists($ruta_imagen)) {
                $producto['imagen'] = $ruta_imagen;
            } else {
                $producto['imagen'] = 'img/default-product.png';
            }
        } else {
            $producto['imagen'] = 'img/default-product.png';
        }
    }
    unset($producto);
    
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   
</head>
<body>
    <!-- Botones superiores (Cesta y Cerrar Sesión) -->
    <div class="header-buttons">
        <a href="cesta.php" class="btn btn-cesta" style="right: 30px;">
            <i class="fas fa-shopping-cart"></i> Cesta
        </a>
        <a href="login.php" class="btn btn-logout" >
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </div>

    <!-- Botón inferior derecho (Panel Cliente) -->
    <div class="panel-button">
        <a href="panel_cliente.php" class="btn btn-panel">
            <i class="fas fa-arrow-left"></i> Panel Cliente
        </a>
    </div>

    <div class="productos-container">
        <?php if (isset($_SESSION['cart_message'])): ?>
            <div class="alert-message show" id="alertMessage">
                <?= $_SESSION['cart_message'] ?>
            </div>
            <?php unset($_SESSION['cart_message']); ?>
        <?php endif; ?>
        
        <div class="productos-header">
            <h1>Catálogo de Productos</h1>
            <div class="decorative-line"></div>
        </div>
        
        <div class="productos-grid">
            <?php foreach ($productos as $producto): ?>
                <div class="producto-card">
                    <div class="producto-img-container">
                        <img src="<?= htmlspecialchars($producto['imagen']) ?>" 
                             alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                             class="producto-img"
                             onerror="this.src='img/default-product.png'">
                    </div>
                    <div class="producto-info">
                        <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                        <p><strong>Referencia:</strong> <?= htmlspecialchars($producto['referencia']) ?></p>
                        <p><strong>Categoría:</strong> <?= htmlspecialchars($producto['familia']) ?></p>
                        <p class="precio"><?= number_format($producto['precio'], 2) ?> €</p>
                        <p class="stock <?= $producto['stock'] > 0 ? 'disponible' : 'agotado' ?>">
                            <?= $producto['stock'] > 0 ? "Disponibles: {$producto['stock']}" : "Agotado" ?>
                        </p>
                        
                        <form method="POST" class="producto-actions">
                            <input type="hidden" name="referencia" value="<?= $producto['referencia'] ?>">
                            <div class="cantidad-control">
                                <label for="cantidad_<?= $producto['referencia'] ?>">Cant:</label>
                                <input type="number" name="cantidad" id="cantidad_<?= $producto['referencia'] ?>" 
                                       value="1" min="1" max="<?= $producto['stock'] ?>">
                            </div>
                            <button type="submit" name="add_to_cart" class="btn btn-cesta" 
                                    <?= $producto['stock'] <= 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-cart-plus"></i> Añadir
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Ocultar mensaje después de 3 segundos
        setTimeout(() => {
            const alert = document.getElementById('alertMessage');
            if (alert) alert.classList.remove('show');
        }, 3000);
    </script>
</body>
</html>