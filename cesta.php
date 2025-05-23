<?php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Procesar actualización/eliminación de productos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        // Actualizar cantidades
        foreach ($_POST['cantidad'] as $referencia => $cantidad) {
            $cantidad = max(1, intval($cantidad));
            if (isset($_SESSION['cesta'][$referencia])) {
                $_SESSION['cesta'][$referencia] = $cantidad;
            }
        }
        $_SESSION['cart_message'] = "Cesta actualizada";
    } elseif (isset($_POST['remove_item'])) {
        // Eliminar producto específico
        $referencia = $_POST['referencia'];
        if (isset($_SESSION['cesta'][$referencia])) {
            unset($_SESSION['cesta'][$referencia]);
            $_SESSION['cart_message'] = "Producto eliminado";
        }
    } elseif (isset($_POST['empty_cart'])) {
        // Vaciar toda la cesta
        unset($_SESSION['cesta']);
        $_SESSION['cart_message'] = "Cesta vaciada";
    }
    
    header("Location: cesta.php");
    exit();
}

// Obtener productos de la cesta con detalles completos
$productos_cesta = [];
$total = 0;

if (!empty($_SESSION['cesta'])) {
    $referencias = array_keys($_SESSION['cesta']);
    $placeholders = implode(',', array_fill(0, count($referencias), '?'));
    
    $stmt = $pdo->prepare("
        SELECT 
            p.referencia, 
            p.nombre_producto AS nombre, 
            p.precio, 
            p.cantidad AS stock,
            p.img_prod AS imagen_ruta
        FROM productos p
        WHERE p.referencia IN ($placeholders)
    ");
    $stmt->execute($referencias);
    $productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($productos_db as $producto) {
        $referencia = $producto['referencia'];
        $cantidad = $_SESSION['cesta'][$referencia];
        
        // Procesar ruta de imagen
        if (!empty($producto['imagen_ruta'])) {
            $ruta_imagen = trim($producto['imagen_ruta']);
            
            if ((strpos($ruta_imagen, 'img/') === 0 && file_exists($ruta_imagen)) || 
                file_exists($ruta_imagen)) {
                $imagen = $ruta_imagen;
            } else {
                $imagen = 'img/default-product.png';
            }
        } else {
            $imagen = 'img/default-product.png';
        }
        
        $subtotal = $producto['precio'] * $cantidad;
        $total += $subtotal;
        
        $productos_cesta[] = [
            'referencia' => $referencia,
            'nombre' => $producto['nombre'],
            'precio' => $producto['precio'],
            'cantidad' => $cantidad,
            'stock' => $producto['stock'],
            'imagen' => $imagen,
            'subtotal' => $subtotal
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cesta</title>
    <link rel="stylesheet" href="styles/cssp.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primario: #9370DB;
            --color-secundario: #8A2BE2;
            --color-fondo: #F5F0FF;
            --color-texto: #4B0082;
            --color-error: #ff6b6b;
        }
        
        body {
            background-color: var(--color-fondo);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--color-texto);
            margin: 0;
            padding: 0;
        }
        
        .cesta-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: 70vh;
        }
        
        .cesta-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .cesta-title {
            color: var(--color-primario);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .decorative-line {
            width: 100px;
            height: 4px;
            background: var(--color-secundario);
            margin: 0 auto 2rem;
            border-radius: 2px;
        }
        
        .cesta-empty {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .empty-title {
            font-size: 1.5rem;
            color: var(--color-texto);
            margin-bottom: 1.5rem;
        }
        
        .empty-icon {
            font-size: 3rem;
            color: var(--color-primario);
            margin-bottom: 1.5rem;
        }
        
        .btn-continue {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: var(--color-primario);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-continue:hover {
            background: var(--color-secundario);
            transform: translateY(-2px);
        }
        
        .alert-message {
            padding: 1rem;
            background: #4CAF50;
            color: white;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .cesta-items {
            margin-bottom: 2rem;
        }
        
        .cesta-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .cesta-item:hover {
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        
        .cesta-item-img-container {
            width: 120px;
            height: 120px;
            margin-right: 1.5rem;
            flex-shrink: 0;
        }
        
        .cesta-item-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 5px;
        }
        
        .cesta-item-info {
            flex-grow: 1;
        }
        
        .cesta-item-name {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--color-texto);
        }
        
        .cesta-item-ref {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .cesta-item-price {
            font-weight: bold;
            color: var(--color-secundario);
            font-size: 1.1rem;
        }
        
        .cesta-item-actions {
            display: flex;
            align-items: center;
        }
        
        .cantidad-control {
            display: flex;
            align-items: center;
            margin-right: 1.5rem;
        }
        
        .cantidad-control label {
            margin-right: 0.5rem;
            font-weight: 600;
        }
        
        .cantidad-control input {
            width: 60px;
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn-remove {
            background: var(--color-error);
            color: white;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .btn-remove:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }
        
        .cesta-summary {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .summary-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--color-secundario);
        }
        
        .cesta-actions {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .action-group {
            display: flex;
            gap: 1rem;
        }
        
        .btn-cesta-action {
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
        }
        
        .btn-empty {
            background: var(--color-error);
            color: white;
        }
        
        .btn-empty:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }
        
        .btn-update {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-update:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }
        
        .btn-checkout {
            background: var(--color-primario);
            color: white;
        }
        
        .btn-checkout:hover {
            background: var(--color-secundario);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .cesta-item {
                flex-direction: column;
                text-align: center;
            }
            
            .cesta-item-img-container {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .cesta-item-actions {
                margin-top: 1rem;
                justify-content: center;
            }
            
            .cesta-actions {
                flex-direction: column;
            }
            
            .action-group {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="cesta-container">
        <?php if (isset($_SESSION['cart_message'])): ?>
            <div class="alert-message">
                <?= $_SESSION['cart_message'] ?>
            </div>
            <?php unset($_SESSION['cart_message']); ?>
        <?php endif; ?>
        
        <div class="cesta-header">
            <h1 class="cesta-title">Mi Cesta</h1>
            <div class="decorative-line"></div>
        </div>
        
        <?php if (empty($productos_cesta)): ?>
            <div class="cesta-empty">
                <div class="empty-icon">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h3 class="empty-title">Tu cesta está vacía</h3>
                <p>Explora nuestros productos y encuentra lo que necesitas</p>
                <br>
                <a href="productos.php" class="btn-continue">
                    <i class="fas fa-arrow-left"></i> Continuar comprando
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="cesta.php">
                <div class="cesta-items">
                    <?php foreach ($productos_cesta as $item): ?>
                        <div class="cesta-item">
                            <div class="cesta-item-img-container">
                                <img src="<?= htmlspecialchars($item['imagen']) ?>" 
                                     alt="<?= htmlspecialchars($item['nombre']) ?>" 
                                     class="cesta-item-img"
                                     onerror="this.src='img/default-product.png'">
                            </div>
                            
                            <div class="cesta-item-info">
                                <h3 class="cesta-item-name"><?= htmlspecialchars($item['nombre']) ?></h3>
                                <p class="cesta-item-ref">Ref: <?= htmlspecialchars($item['referencia']) ?></p>
                                <p class="cesta-item-price"><?= number_format($item['precio'], 2) ?> €</p>
                                <p>Subtotal: <?= number_format($item['subtotal'], 2) ?> €</p>
                            </div>
                            
                            <div class="cesta-item-actions">
                                <div class="cantidad-control">
                                    <label for="cantidad_<?= $item['referencia'] ?>">Cantidad:</label>
                                    <input type="number" 
                                           name="cantidad[<?= $item['referencia'] ?>]" 
                                           id="cantidad_<?= $item['referencia'] ?>" 
                                           value="<?= $item['cantidad'] ?>" 
                                           min="1" 
                                           max="<?= $item['stock'] ?>">
                                </div>
                                
                                <button type="submit" 
                                        name="remove_item" 
                                        class="btn-remove">
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </button>
                                <input type="hidden" name="referencia" value="<?= $item['referencia'] ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cesta-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span><?= number_format($total, 2) ?> €</span>
                    </div>
                    <div class="summary-row">
                        <span>Envío:</span>
                        <span>Gratis</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span><?= number_format($total, 2) ?> €</span>
                    </div>
                </div>
                
                <div class="cesta-actions">
                    <a href="productos.php" class="btn-cesta-action btn-update">
                        <i class="fas fa-arrow-left"></i> Continuar comprando
                    </a>
                    
                    <div class="action-group">
                        <button type="submit" name="empty_cart" class="btn-cesta-action btn-empty">
                            <i class="fas fa-trash"></i> Vaciar cesta
                        </button>
                        
                        <button type="submit" name="update_cart" class="btn-cesta-action btn-update">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                        
                        <a href="pago.php" class="btn-cesta-action btn-checkout">
                            <i class="fas fa-credit-card"></i> Finalizar compra
                        </a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>