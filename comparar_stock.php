<?php
// Mostrar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'check.php'; // Verifica acceso y sesión

// Solo permitir admin
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: acceso_denegado.php');
    exit();
}

require_once 'conexion.php';

try {
    // Obtener datos para comparación
    $comparacion = $pdo->query("
        SELECT 
            p.referencia AS ref,
            p.nombre_producto AS nombre,
            p.img_prod AS imagen,
            p.cantidad AS stock_sistema,
            p.Unidad AS unidad,
            ri.cantidad_contada as stock_inventario,
            p.cantidad as stock
        FROM productos p
        LEFT JOIN registro_inventario ri on ri.referencia_producto = p.referencia
        GROUP BY p.referencia 
        ORDER BY p.nombre_producto
    ")->fetchAll(PDO::FETCH_ASSOC);

    if ($comparacion === false) {
        throw new Exception("Error al obtener datos para comparación");
    }

    // Obtener datos del empleado para mostrar en la página
    $stmt = $pdo->prepare("
        SELECT p.nombre, p.apellido1, c.cargo AS nombre_cargo 
        FROM personal p
        JOIN cargo c ON p.cargo = c.id_cargo
        WHERE p.id_personal = ?
    ");
    if (!$stmt->execute([$_SESSION['user_id']])) {
        throw new Exception("Error al obtener datos del empleado");
    }
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empleado) {
        throw new Exception("No se encontraron datos del empleado");
    }
} catch (PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparación de Stocks</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ============ VARIABLES Y RESET ============ */
        :root {
            /* Colores base */
            --color-carton-claro: #e6d5b8;
            --color-fondo: #FAF0E6;
            --color-primario: #D2B48C;
            --color-secundario: #BC8F8F;
            --color-texto: #654321;
            --color-blanco: #FFFFFF;
            
            /* Sombras */
            --sombra-suave: 0 2px 8px rgba(0, 0, 0, 0.08);
            --sombra-intensa: 0 4px 12px rgba(0, 0, 0, 0.12);
            
            /* Transiciones */
            --transicion-rapida: all 0.15s ease;
            --transicion-media: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Bordes */
            --radio-borde: 10px;
            --radio-borde-pequeno: 6px;
            --radio-circular: 50%;
            
            /* Espaciados */
            --espacio-xs: 0.5rem;
            --espacio-sm: 0.75rem;
            --espacio-md: 1rem;
            --espacio-lg: 1.5rem;
            --espacio-xl: 2rem;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--color-fondo);
            color: var(--color-texto);
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.5;
            padding: var(--espacio-md);
        }

        /* ============ TIPOGRAFÍA ============ */
        h1, h2, h3 {
            font-weight: 700;
            line-height: 1.2;
        }

        h1 {
            font-size: 1.8rem;
            margin-bottom: var(--espacio-xs);
            color: var(--color-texto);
            text-align: center;
        }

        .panel-subtitle {
            color: var(--color-secundario);
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: var(--espacio-md);
            text-align: center;
        }

        /* ============ ESTRUCTURA PRINCIPAL ============ */
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: var(--espacio-md);
        }

        .header {
            margin-bottom: var(--espacio-xl);
        }

        .deco-line {
            width: 60px;
            height: 3px;
            background: var(--color-secundario);
            margin: var(--espacio-sm) auto;
            border-radius: 2px;
        }

        /* ============ LISTADO DE PRODUCTOS ============ */
        .comparacion-grid {
            display: flex;
            flex-direction: column;
            gap: var(--espacio-sm);
            margin-top: var(--espacio-lg);
        }

        .comparacion-card {
            display: flex;
            align-items: center;
            background: var(--color-blanco);
            border-radius: var(--radio-borde);
            padding: var(--espacio-md);
            box-shadow: var(--sombra-suave);
            transition: var(--transicion-media);
            border: 1px solid rgba(210, 180, 140, 0.2);
            position: relative;
        }

        .comparacion-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--sombra-intensa);
            border-color: rgba(210, 180, 140, 0.4);
        }

        /* Foto circular */
        .product-image-container {
            width: 70px;
            height: 70px;
            min-width: 70px;
            border-radius: var(--radio-circular);
            overflow: hidden;
            background: var(--color-carton-claro);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: var(--espacio-md);
            border: 2px solid var(--color-primario);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transicion-media);
        }

        .comparacion-card:hover .product-image {
            transform: scale(1.05);
        }

        /* Contenido principal */
        .comparacion-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--color-texto);
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-ref {
            color: var(--color-secundario);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: var(--espacio-xs);
        }

        /* Información de stock */
        .stock-info {
            display: flex;
            gap: var(--espacio-lg);
            margin-top: var(--espacio-xs);
        }

        .stock-group {
            display: flex;
            flex-direction: column;
        }

        .stock-label {
            font-size: 0.8rem;
            color: var(--color-secundario);
            margin-bottom: 2px;
            font-weight: 500;
        }

        .stock-value {
            font-weight: 600;
            font-size: 1rem;
        }

        /* Diferencia */
        .difference {
            font-weight: 600;
            padding: 6px var(--espacio-sm);
            border-radius: var(--radio-borde-pequeno);
            text-align: center;
            font-size: 0.95rem;
            margin-left: auto;
            min-width: 100px;
            white-space: nowrap;
        }
        
        .positive-diff {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.2);
        }
        
        .negative-diff {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .zero-diff {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            border: 1px solid rgba(108, 117, 125, 0.2);
        }

        /* Mensaje cuando no hay datos */
        .empty-message {
            text-align: center;
            padding: var(--espacio-xl) var(--espacio-md);
            background: var(--color-blanco);
            border-radius: var(--radio-borde);
            box-shadow: var(--sombra-suave);
            margin: var(--espacio-xl) 0;
            border: 1px solid rgba(210, 180, 140, 0.2);
            font-size: 1.1rem;
        }

        .empty-message i {
            color: var(--color-secundario);
            font-size: 1.5rem;
            margin-bottom: var(--espacio-sm);
        }

        /* Información de usuario */
        .user-info {
            background: var(--color-blanco);
            border-radius: var(--radio-borde);
            padding: var(--espacio-md);
            margin-top: var(--espacio-xl);
            text-align: center;
            box-shadow: var(--sombra-suave);
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            border: 1px solid rgba(210, 180, 140, 0.2);
        }

        .user-info p {
            margin-bottom: var(--espacio-xs);
            font-size: 0.95rem;
        }

        .user-info strong {
            color: var(--color-secundario);
        }

        .btn-panel {
            background: var(--color-primario);
            color: var(--color-blanco);
            border: none;
            padding: 8px var(--espacio-md);
            border-radius: var(--radio-borde-pequeno);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transicion-media);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            margin-top: var(--espacio-sm);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            font-size: 0.9rem;
        }

        .btn-panel:hover {
            background: var(--color-secundario);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
        }

        .btn-panel i {
            margin-right: 6px;
        }

        /* Mensajes de error */
        .message.error {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: var(--espacio-sm);
            border-radius: var(--radio-borde-pequeno);
            margin-bottom: var(--espacio-md);
            text-align: center;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            font-size: 0.95rem;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .message.error i {
            margin-right: var(--espacio-xs);
        }

        /* ============ RESPONSIVE ============ */
        @media (max-width: 768px) {
            .container {
                padding: var(--espacio-sm);
            }
            
            h1 {
                font-size: 1.6rem;
            }
            
            .comparacion-card {
                flex-wrap: wrap;
                padding: var(--espacio-sm);
            }
            
            .product-image-container {
                width: 60px;
                height: 60px;
                margin-right: var(--espacio-sm);
            }
            
            .comparacion-content {
                min-width: calc(100% - 76px);
            }
            
            .stock-info {
                gap: var(--espacio-md);
                margin-top: var(--espacio-xs);
            }
            
            .difference {
                margin-top: var(--espacio-sm);
                margin-left: 0;
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .comparacion-card {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-image-container {
                margin-right: 0;
                margin-bottom: var(--espacio-sm);
            }
            
            .difference {
                align-self: flex-end;
                margin-top: var(--espacio-sm);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Comparación de Stocks</h1>
            <p class="panel-subtitle">Diferencia entre stock de ventas y stock de inventario</p>
            <div class="deco-line"></div>
        </div>

        <?php if (isset($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($error) && count($comparacion) > 0): ?>
            <div class="comparacion-grid">
                <?php foreach ($comparacion as $producto): 
                    $stock_inventario = $producto['stock_inventario'] ?? 0;
                    $diferencia = ($producto['stock_sistema']) - $stock_inventario;
                ?>
                    <div class="comparacion-card">
                        <div class="product-image-container">
                            <img src="<?= htmlspecialchars($producto['imagen'] ?? 'img/placeholder.png') ?>" 
                                 alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                                 class="product-image">
                        </div>
                        
                        <div class="comparacion-content">
                            <div class="product-info">
                                <div class="product-name"><?= htmlspecialchars($producto['nombre']) ?></div>
                                <span class="product-ref">Ref: <?= htmlspecialchars($producto['ref']) ?></span>
                            </div>
                            
                            <div class="stock-info">
                                <div class="stock-group">
                                    <span class="stock-label">Stock sistema:</span>
                                    <span class="stock-value"><?= htmlspecialchars($producto['stock_sistema']) ?> <?= htmlspecialchars($producto['unidad']) ?></span>
                                </div>
                                
                                <div class="stock-group">
                                    <span class="stock-label">Stock inventario:</span>
                                    <span class="stock-value"><?= $stock_inventario ?> <?= htmlspecialchars($producto['unidad']) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="difference <?= $diferencia > 0 ? 'positive-diff' : ($diferencia < 0 ? 'negative-diff' : 'zero-diff') ?>">
                            Diferencia: <?= $diferencia ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!isset($error)): ?>
            <div class="empty-message">
                <p><i class="fas fa-info-circle"></i> Primero hay que hacer un inventario para poder comparar stocks</p>
            </div>
        <?php endif; ?>

        <div class="user-info">
            <p>Has iniciado sesión como: <strong><?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido1']) ?></strong></p>
            <p>Cargo: <strong><?= htmlspecialchars($empleado['nombre_cargo']) ?></strong></p>
            <a href="panel_empleado.php" class="btn-panel">
                <i class="fas fa-arrow-left"></i> Volver al Panel
            </a>
        </div>
    </div>
</body>
</html>