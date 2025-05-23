<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'conexion.php';

$error = '';
$exito = '';
$producto = null;
$busqueda = ''; // Inicializamos la variable
$familias = $pdo->query("SELECT id_familia, familia FROM familia_prod ORDER BY familia")->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos del empleado
$stmt = $pdo->prepare("
    SELECT p.nombre, p.apellido1, c.cargo AS nombre_cargo 
    FROM personal p
    JOIN cargo c ON p.cargo = c.id_cargo
    WHERE p.id_personal = ?
");
$stmt->execute([$_SESSION['user_id']]);
$empleado = $stmt->fetch();

// Procesar búsqueda
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['buscar'])) {
    try {
        $busqueda = trim($_GET['buscar_producto']);
        
        if (empty($busqueda)) {
            throw new Exception("Por favor ingrese un término de búsqueda");
        }

        // Buscar por nombre o referencia
        $stmt = $pdo->prepare("SELECT *, IFNULL(familia_producto, 0) AS familia_producto FROM productos WHERE nombre_producto LIKE ? OR referencia LIKE ? LIMIT 1");
        $stmt->execute(["%$busqueda%", "%$busqueda%"]);
        $producto = $stmt->fetch();
        
        if (!$producto) {
            $error = "No se encontró un producto con ese nombre o referencia.";
        }
    } catch (PDOException $e) {
        $error = "Error al buscar productos: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Procesar modificación
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modificar'])) {
    try {
        $valores = [
            'referencia' => $_POST['referencia'],
            'nombre_producto' => htmlspecialchars(trim($_POST['nombre_producto'])),
            'familia_producto_id' => (int)$_POST['familia_producto_id'],
            'precio' => (float)$_POST['precio'],
            'Unidad' => strtoupper(htmlspecialchars(trim($_POST['Unidad']))),
            'cantidad' => (int)$_POST['cantidad'],
            'descripcion' => htmlspecialchars(trim($_POST['descripcion']))
        ];

        // Validaciones
        if (empty($valores['nombre_producto']) || empty($valores['precio']) || 
            empty($valores['Unidad']) || empty($valores['cantidad'])) {
            throw new Exception("Todos los campos obligatorios deben estar completos");
        }

        if ($valores['precio'] <= 0) {
            throw new Exception("El precio debe ser mayor que cero");
        }

        $unidades_permitidas = ['L', 'KG', 'U'];
        if (!in_array($valores['Unidad'], $unidades_permitidas)) {
            throw new Exception("Unidad no válida. Use L, KG o U");
        }

        // Actualizar en la base de datos
        $sql = "UPDATE productos SET
                nombre_producto = ?,
                familia_producto = ?,
                precio = ?,
                Unidad = ?,
                cantidad = ?,
                descripcion = ?,
                last_update = CURRENT_TIMESTAMP
                WHERE referencia = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $valores['nombre_producto'],
            $valores['familia_producto_id'],
            $valores['precio'],
            $valores['Unidad'],
            $valores['cantidad'],
            $valores['descripcion'],
            $valores['referencia']
        ]);

        $exito = "Producto actualizado correctamente";
        $producto = $valores; // Actualizar datos mostrados

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener productos para autocompletado
$productos_autocompletar = $pdo->query("SELECT referencia, nombre_producto FROM productos ORDER BY nombre_producto")->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Producto</title>
    <link rel="stylesheet" href="styles/css_modificar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1 class="form-title"><i class="fas fa-edit"></i> Modificar Producto</h1>
            <div class="deco-line"></div>
        </div>

        <?php if($error): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if($exito): ?>
            <div class="success-message">
                <?php echo $exito; ?>
            </div>
        <?php endif; ?>

        <div class="search-container">
            <form method="GET" action="modificar_producto.php" id="search-form">
                <div class="form-group">
                    <label for="buscar_producto" class="form-label">Buscar producto</label>
                    <div class="search-wrapper">
                        <div class="search-input">
                            <input type="text" id="buscar_producto" name="buscar_producto" class="form-control" 
                                   placeholder="Escribe el nombre o referencia del producto" autocomplete="off"
                                   value="<?php echo isset($_GET['buscar_producto']) ? htmlspecialchars($_GET['buscar_producto']) : ''; ?>">
                            <div class="search-results" id="search-results"></div>
                        </div>
                        <button type="submit" name="buscar" class="btn-submit" style="flex: 0 0 auto;">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if($producto): ?>
    <div class="product-display">
        <h3>Producto seleccionado</h3>
        <p><strong>Referencia:</strong> <?php echo htmlspecialchars($producto['referencia'] ?? ''); ?></p>
        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($producto['nombre_producto'] ?? ''); ?></p>
        <p><strong>Familia:</strong> <?php 
            $familia_id = $producto['familia_producto'] ?? null;
            $familia_nombre = 'Desconocida';
            
            if ($familia_id) {
                $familia_encontrada = array_values(array_filter($familias, function($f) use ($familia_id) {
                    return $f['id_familia'] == $familia_id;
                }));
                
                if (!empty($familia_encontrada)) {
                    $familia_nombre = htmlspecialchars($familia_encontrada[0]['familia']);
                }
            }
            echo $familia_nombre; 
        ?></p>
        <p><strong>Precio:</strong> <?php echo isset($producto['precio']) ? number_format($producto['precio'], 2) : '0.00'; ?> €</p>
    </div>

            <form method="POST">
                <input type="hidden" name="referencia" value="<?php echo htmlspecialchars($producto['referencia']); ?>">
                
                <div class="form-group">
                    <label for="mod_nombre_producto" class="form-label">Nombre del producto *</label>
                    <input type="text" id="mod_nombre_producto" name="nombre_producto" class="form-control" 
                           value="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="mod_familia_producto" class="form-label">Familia del producto *</label>
                    <select id="mod_familia_producto" name="familia_producto_id" class="form-control" required>
                        <option value="">Seleccione una familia</option>
                        <?php foreach ($familias as $familia): ?>
                            <option value="<?= $familia['id_familia'] ?>" 
                                <?= ($producto['familia_producto'] == $familia['id_familia']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($familia['familia']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="mod_precio" class="form-label">Precio *</label>
                    <input type="number" id="mod_precio" name="precio" step="0.01" class="form-control" 
                           value="<?php echo htmlspecialchars($producto['precio']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="mod_Unidad" class="form-label">Unidad de medida *</label>
                    <select id="mod_Unidad" name="Unidad" class="form-control" required>
                        <option value="">Seleccione una unidad</option>
                        <option value="L" <?= $producto['Unidad'] === 'L' ? 'selected' : '' ?>>Litros (L)</option>
                        <option value="KG" <?= $producto['Unidad'] === 'KG' ? 'selected' : '' ?>>Kilogramos (KG)</option>
                        <option value="U" <?= $producto['Unidad'] === 'U' ? 'selected' : '' ?>>Unidades (U)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="mod_cantidad" class="form-label">Cantidad *</label>
                    <input type="number" id="mod_cantidad" name="cantidad" class="form-control" 
                           value="<?php echo htmlspecialchars($producto['cantidad']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="mod_descripcion" class="form-label">Descripción</label>
                    <textarea id="mod_descripcion" name="descripcion" class="form-control"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                </div>
                
                <button type="submit" name="modificar" class="btn-submit">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>
        <?php endif; ?>
        
        <a href="gestion_productos.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Productos
        </a>

        <div class="user-info">
            <p>Has iniciado sesión como: <strong><?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido1']); ?></strong></p>
            <p>Cargo: <strong><?php echo htmlspecialchars($empleado['nombre_cargo']); ?></strong></p>
            <a href="logout.php" style="color: var(--color-secundario);">Cerrar sesión</a>
        </div>
    </div>

    <script>
        // Datos para autocompletado desde PHP
        const productos = <?php echo json_encode($productos_autocompletar); ?>;
        
        // Elementos del DOM
        const buscarInput = document.getElementById('buscar_producto');
        const searchResults = document.getElementById('search-results');
        const searchForm = document.getElementById('search-form');

        // Mostrar resultados de búsqueda
        function mostrarResultados(resultados) {
            searchResults.innerHTML = '';
            
            if (resultados.length === 0) {
                searchResults.style.display = 'none';
                return;
            }
            
            resultados.slice(0, 5).forEach(producto => {
                const item = document.createElement('div');
                item.className = 'search-result-item';
                item.innerHTML = `
                    <div>${producto.nombre_producto}</div>
                    <small>Ref: ${producto.referencia}</small>
                `;
                
                item.addEventListener('click', () => {
                    buscarInput.value = producto.nombre_producto;
                    searchResults.style.display = 'none';
                    
                    // Crear campo oculto para la referencia
                    let hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'referencia';
                    hiddenInput.value = producto.referencia;
                    
                    // Eliminar cualquier campo oculto previo
                    const existingHidden = document.querySelector('input[name="referencia"]');
                    if (existingHidden) {
                        existingHidden.remove();
                    }
                    
                    searchForm.appendChild(hiddenInput);
                    
                    // Enviar el formulario automáticamente
                    searchForm.submit();
                });
                
                searchResults.appendChild(item);
            });
            
            searchResults.style.display = 'block';
        }

        // Filtrar productos según la búsqueda
        function filtrarProductos(termino) {
            termino = termino.toLowerCase();
            return productos.filter(producto => 
                producto.nombre_producto.toLowerCase().includes(termino) || 
                producto.referencia.toLowerCase().includes(termino)
            );
        }

        // Evento al escribir en el buscador
        buscarInput.addEventListener('input', function() {
            const termino = this.value.trim();
            
            if (termino.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            const resultados = filtrarProductos(termino);
            mostrarResultados(resultados);
        });

        // Ocultar resultados al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!buscarInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // Manejar el envío del formulario
        searchForm.addEventListener('submit', function(e) {
            const termino = buscarInput.value.trim();
            if (termino.length < 2) {
                e.preventDefault();
                alert('Por favor, ingrese al menos 2 caracteres para buscar');
            }
        });
    </script>
</body>
</html>