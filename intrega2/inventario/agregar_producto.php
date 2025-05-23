<?php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'conexion.php';

// Función para sanitizar inputs
function sanitizeInput($input, bool $toInt = false) {
    if ($input === null || !isset($input)) {
        return $toInt ? 0 : '';
    }
    
    $input = trim((string)$input);
    return $toInt ? (int)$input : htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Inicialización con valores por defecto
$valores = [
    'nombre_producto' => '',
    'familia_producto_id' => '',
    'nueva_familia' => '',
    'precio' => '0.00',
    'Unidad' => '',
    'cantidad' => '0',
    'descripcion' => ''
];

// Obtener familias existentes
$familias = $pdo->query("SELECT id_familia, familia FROM familia_prod ORDER BY familia")->fetchAll(PDO::FETCH_ASSOC);

// Generar referencia única
function generarReferenciaUnica(PDO $pdo): string {
    $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numeros = '0123456789';
    
    for ($i = 0; $i < 50; $i++) {
        $ref = $letras[random_int(0, 25)] . $numeros[random_int(0, 9)] . $numeros[random_int(0, 9)];
        
        $stmt = $pdo->prepare("SELECT 1 FROM productos WHERE referencia = ? LIMIT 1");
        $stmt->execute([$ref]);
        
        if (!$stmt->fetch()) {
            return $ref;
        }
    }
    
    return substr(strtoupper(uniqid('P', false)), 0, 3);
}

$nueva_referencia = generarReferenciaUnica($pdo);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Sanitizar inputs
        $valores = [
            'nombre_producto' => sanitizeInput($_POST['nombre_producto'] ?? ''),
            'familia_producto_id' => sanitizeInput($_POST['familia_producto_id'] ?? ''),
            'nueva_familia' => sanitizeInput($_POST['nueva_familia'] ?? ''),
            'precio' => (float)sanitizeInput($_POST['precio'] ?? '0', true),
            'Unidad' => strtoupper(sanitizeInput($_POST['Unidad'] ?? '')),
            'cantidad' => (int)sanitizeInput($_POST['cantidad'] ?? '0', true),
            'descripcion' => sanitizeInput($_POST['descripcion'] ?? '')
        ];

        // Validaciones
        if (strlen($valores['nombre_producto']) < 2) {
            throw new Exception("El nombre debe tener al menos 2 caracteres");
        }

        if ($valores['precio'] <= 0) {
            throw new Exception("El precio debe ser mayor a 0");
        }

        if (!in_array($valores['Unidad'], ['L', 'KG', 'U'])) {
            throw new Exception("Unidad no válida. Use L, KG o U");
        }

        if ($valores['cantidad'] < 0) {
            throw new Exception("La cantidad no puede ser negativa");
        }

        // Manejo de familia
        $familia_final = null;
        
        if (!empty($valores['nueva_familia'])) {
            // Validar nueva familia
            if (strlen($valores['nueva_familia']) < 3) {
                throw new Exception("El nombre de la familia debe tener al menos 3 caracteres");
            }
            
            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT id_familia FROM familia_prod WHERE familia = ?");
            $stmt->execute([$valores['nueva_familia']]);
            
            if ($existe = $stmt->fetch()) {
                $familia_final = $existe['id_familia'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO familia_prod (familia) VALUES (?)");
                $stmt->execute([$valores['nueva_familia']]);
                $familia_final = $pdo->lastInsertId();
            }
        } else {
            // Validar familia seleccionada
            if (empty($valores['familia_producto_id'])) {
                throw new Exception("Debe seleccionar una familia existente o ingresar una nueva");
            }
            
            $stmt = $pdo->prepare("SELECT 1 FROM familia_prod WHERE id_familia = ?");
            $stmt->execute([$valores['familia_producto_id']]);
            
            if (!$stmt->fetch()) {
                throw new Exception("La familia seleccionada no existe");
            }
            
            $familia_final = (int)$valores['familia_producto_id'];
        }

        // Insertar producto
        $pdo->beginTransaction();
        
        try {
            $sql = "INSERT INTO productos (
                referencia, nombre_producto, familia_producto, 
                precio, Unidad, cantidad, descripcion, last_update
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nueva_referencia,
                $valores['nombre_producto'],
                $familia_final,
                $valores['precio'],
                $valores['Unidad'],
                $valores['cantidad'],
                $valores['descripcion']
            ]);

            $pdo->commit();
            
            $_SESSION['mensaje_exito'] = "Producto agregado correctamente (Ref: $nueva_referencia)";
            header("Location: gestion_productos.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }

    } catch (PDOException $e) {
        $error = $e->getCode() == 23000 ? 
            "Error de referencia duplicada. Intente nuevamente." : 
            "Error de base de datos: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto</title>
    <link rel="stylesheet" href="styles/css_agregar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
   
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            
            <h1 class="form-title"><i class="fas fa-plus-circle title-icon"></i>Agregar Producto</h1>
          
            <div class="deco-line"></div>
         <!--   <p>Referencia generada: <strong><?php echo $nueva_referencia; ?></strong></p> -->
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="productoForm">
            <!-- Campo Nombre -->
            <div class="form-group">
                <label for="nombre_producto" class="form-label">Nombre del producto *</label>
                <input type="text" id="nombre_producto" name="nombre_producto" class="form-control" required 
                       value="<?php echo htmlspecialchars($valores['nombre_producto']); ?>">
            </div>
            
            <!-- Campo Familia -->
            <div class="form-group">
                <label for="familia_select" class="form-label">Familia del producto *</label>
                <select id="familia_select" name="familia_producto_id" class="form-control" required>
                    <option value="">Seleccione una familia *</option>
                    <?php foreach ($familias as $familia): ?>
                        <option value="<?php echo $familia['id_familia']; ?>"
                            <?php if ($familia['id_familia'] == $valores['familia_producto_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($familia['familia']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Botón Nueva Familia -->
            <div class="form-group">
                <button type="button" id="toggle-nueva-familia" class="btn-submit">
                    <i class="fas fa-plus"></i> Añadir nueva familia
                </button>
            </div>
            
            <!-- Campo Nueva Familia (oculto inicialmente) -->
            <div id="nueva-familia-container" class="form-group">
                <label for="nueva_familia" class="form-label">Nombre de la nueva familia</label>
                <input type="text" id="nueva_familia" name="nueva_familia" class="form-control"
                       value="<?php echo htmlspecialchars($valores['nueva_familia']); ?>">
            </div>
            
            <!-- Campos adicionales -->
            <div class="form-group">
                <label for="precio" class="form-label">Precio *</label>
                <input type="number" id="precio" name="precio" step="0.01" class="form-control" required 
                       value="<?php echo htmlspecialchars($valores['precio']); ?>">
            </div>
            
            <div class="form-group">
                <label for="Unidad" class="form-label">Unidad *</label>
                <select id="Unidad" name="Unidad" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <option value="L" <?php if ($valores['Unidad'] == 'L') echo 'selected'; ?>>Litros</option>
                    <option value="KG" <?php if ($valores['Unidad'] == 'KG') echo 'selected'; ?>>Kilogramos</option>
                    <option value="U" <?php if ($valores['Unidad'] == 'U') echo 'selected'; ?>>Unidades</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="cantidad" class="form-label">Cantidad *</label>
                <input type="number" id="cantidad" name="cantidad" class="form-control" required 
                       value="<?php echo htmlspecialchars($valores['cantidad']); ?>">
            </div>
            
            <div class="form-group">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea id="descripcion" name="descripcion" class="form-control"><?php echo htmlspecialchars($valores['descripcion']); ?></textarea>
            </div>

            <a href="gestion_productos.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Productos
            </a><br></br>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Guardar Producto
            </button>
        </form>
    </div>

    <script>
        document.getElementById('toggle-nueva-familia').addEventListener('click', function() {
            const container = document.getElementById('nueva-familia-container');
            const select = document.getElementById('familia_select');
            
            if (container.style.display === 'block') {
                container.style.display = 'none';
                select.required = true;
                this.innerHTML = '<i class="fas fa-plus"></i> Añadir nueva familia';
            } else {
                container.style.display = 'block';
                select.required = false;
                select.value = '';
                this.innerHTML = '<i class="fas fa-times"></i> Cancelar';
            }
        });

        // Mostrar el campo de nueva familia si ya tiene un valor
        <?php if (!empty($valores['nueva_familia'])): ?>
            document.getElementById('nueva-familia-container').style.display = 'block';
            document.getElementById('familia_select').required = false;
            document.getElementById('toggle-nueva-familia').innerHTML = '<i class="fas fa-times"></i> Cancelar';
        <?php endif; ?>
    </script>
</body>
</html>