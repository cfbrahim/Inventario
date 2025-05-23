<?php
// Mostrar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'check.php'; // Verifica acceso y sesión

// Solo permitir empleados y admin
if ($_SESSION['user_type'] !== 'empleado' && $_SESSION['user_type'] !== 'admin') {
    header('Location: acceso_denegado.php');
    exit();
}

require_once 'conexion.php';

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $referencia = $_POST['referencia'] ?? null;
        $cantidad = $_POST['cantidad'] ?? null;
        $id_personal = $_SESSION['user_id'] ?? null;
        
        if (!$referencia || !$cantidad || !$id_personal) {
            throw new Exception("Datos del formulario incompletos");
        }
        
        // Validar cantidad
        if (!is_numeric($cantidad) || $cantidad < 0) {
            throw new Exception("La cantidad debe ser un número positivo");
        }
        
        // Generar ID de inventario (letra + 2 números)
        $letra = chr(rand(65, 90)); // Letra mayúscula aleatoria
        $numeros = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        $id_inventario = $letra . $numeros;
        
        // Insertar registro de inventario
        $stmt = $pdo->prepare("
            INSERT INTO registro_inventario 
            (id_inventario, referencia_producto, id_personal, cantidad_contada)
            VALUES (?, ?, ?, ?)
        ");
        if (!$stmt->execute([$id_inventario, $referencia, $id_personal, $cantidad])) {
            throw new Exception("Error al insertar registro de inventario");
        }
        
        $mensaje = "Conteo registrado correctamente! ID: $id_inventario";
    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

try {
    // Obtener lista de productos
    $productos = $pdo->query("
        SELECT 
            referencia AS ref,
            nombre_producto AS nombre,
            img_prod AS imagen,
            cantidad,
            Unidad AS unidad
        FROM productos
        ORDER BY nombre_producto
    ")->fetchAll(PDO::FETCH_ASSOC);

    if ($productos === false) {
        throw new Exception("Error al obtener la lista de productos");
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

<?php
// [El código PHP anterior se mantiene igual]
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Inventario</title>
    <link rel="stylesheet" href="tu_estilo.css">
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
        .product-list {
            display: flex;
            flex-direction: column;
            gap: var(--espacio-sm);
            margin-top: var(--espacio-lg);
        }

        .product-item {
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

        .product-item:hover {
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

        .product-item:hover .product-image {
            transform: scale(1.05);
        }

        /* Contenido principal */
        .product-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0; /* Permite que el texto se trunque */
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

        /* Formulario */
        .inventory-form {
            display: flex;
            align-items: center;
            gap: var(--espacio-md);
            margin-left: auto; /* Empuja el formulario a la derecha */
        }

        .form-group {
            display: flex;
            align-items: center;
            gap: var(--espacio-xs);
        }

        .form-group label {
            font-weight: 600;
            color: var(--color-secundario);
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .form-group input {
            width: 70px;
            padding: 6px var(--espacio-xs);
            border: 2px solid var(--color-primario);
            border-radius: var(--radio-borde-pequeno);
            font-size: 0.95rem;
            text-align: center;
            transition: var(--transicion-rapida);
            font-weight: 500;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--color-secundario);
            box-shadow: 0 0 0 3px rgba(188, 143, 143, 0.15);
        }

        /* Botón Guardar */
        .btn-save {
            background: var(--color-primario);
            color: var(--color-blanco);
            border: none;
            padding: 8px var(--espacio-md);
            border-radius: var(--radio-borde-pequeno);
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transicion-media);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            min-width: 100px;
            white-space: nowrap;
        }

        .btn-save:hover {
            background: var(--color-secundario);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
        }

        .btn-save i {
            margin-right: 6px;
            font-size: 0.9rem;
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

        /* Mensajes */
        .message {
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
        }

        .message i {
            margin-right: var(--espacio-xs);
        }

        .success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.2);
        }

        .error {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        /* ============ RESPONSIVE ============ */
        @media (max-width: 768px) {
            .container {
                padding: var(--espacio-sm);
            }
            
            h1 {
                font-size: 1.6rem;
            }
            
            .product-item {
                flex-wrap: wrap;
                padding: var(--espacio-sm);
            }
            
            .product-image-container {
                width: 60px;
                height: 60px;
                margin-right: var(--espacio-sm);
            }
            
            .product-content {
                min-width: calc(100% - 76px); /* 60px + 16px margin */
            }
            
            .stock-info {
                gap: var(--espacio-md);
                margin-top: var(--espacio-xs);
            }
            
            .inventory-form {
                width: 100%;
                margin-top: var(--espacio-sm);
                justify-content: flex-end;
            }
            
            .form-group {
                margin-left: auto;
            }
            
            .btn-save {
                padding: 8px var(--espacio-sm);
            }
        }

        @media (max-width: 480px) {
            .product-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-image-container {
                margin-right: 0;
                margin-bottom: var(--espacio-sm);
            }
            
            .inventory-form {
                justify-content: space-between;
                margin-top: var(--espacio-sm);
            }
            
            .form-group {
                margin-left: 0;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Control de Inventario</h1>
            <p class="panel-subtitle">Registre el conteo físico para comparar con el sistema</p>
            <div class="deco-line"></div>
        </div>

        <?php if (isset($mensaje)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($error) || (isset($error) && strpos($error, 'Error al obtener') === false)): ?>
            <div class="product-list">
                <?php foreach ($productos as $producto): 
                    // Obtener el último conteo registrado para este producto
                    $stmt = $pdo->prepare("
                        SELECT cantidad_contada 
                        FROM registro_inventario 
                        WHERE referencia_producto = ? 
                        ORDER BY fecha_registro DESC 
                        LIMIT 1
                    ");
                    $stmt->execute([$producto['ref']]);
                    $ultimo_conteo = $stmt->fetch(PDO::FETCH_ASSOC);
                    $cantidad_contada = $ultimo_conteo ? $ultimo_conteo['cantidad_contada'] : null;
                ?>
                    <div class="product-item">
                        <div class="product-image-container">
                            <img src="<?= htmlspecialchars($producto['imagen'] ?? 'img/placeholder.png') ?>" 
                                 alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                                 class="product-image">
                        </div>
                        
                        <div class="product-content">
                            <div class="product-name"><?= htmlspecialchars($producto['nombre']) ?></div>
                            <div class="product-ref">Ref: <?= htmlspecialchars($producto['ref']) ?></div>
                            
                            <div class="stock-info">
                                <div class="stock-group">
                                    <span class="stock-label">Stock sistema</span>
                                    <span class="stock-value"><?= htmlspecialchars($producto['cantidad']) ?> <?= htmlspecialchars($producto['unidad']) ?></span>
                                </div>
                                
                                <?php if ($cantidad_contada !== null): ?>
                                    <div class="stock-group">
                                        <span class="stock-label">Último conteo</span>
                                        <span class="stock-value"><?= $cantidad_contada ?> <?= htmlspecialchars($producto['unidad']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <form method="POST" class="inventory-form">
                            <input type="hidden" name="referencia" value="<?= htmlspecialchars($producto['ref']) ?>">
                            
                            <div class="form-group">
                                <label for="cantidad-<?= htmlspecialchars($producto['ref']) ?>">Cantidad:</label>
                                <input type="number" id="cantidad-<?= htmlspecialchars($producto['ref']) ?>" 
                                       name="cantidad" min="0" required 
                                       value="<?= $cantidad_contada ?? htmlspecialchars($producto['cantidad']) ?>">
                            </div>
                            
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="user-info">
                <p>Has iniciado sesión como: <strong><?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido1']) ?></strong></p>
                <p>Cargo: <strong><?= htmlspecialchars($empleado['nombre_cargo']) ?></strong></p>
                <a href="panel_empleado.php" class="btn-panel">
                    <i class="fas fa-arrow-left"></i> Volver al Panel
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
