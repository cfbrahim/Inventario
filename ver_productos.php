<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require 'conexion.php';

if (!isset($pdo)) {
    die("Error: No se pudo establecer la conexión a la base de datos");
}

$query = "SELECT referencia, nombre_producto, Unidad, cantidad, img_prod FROM productos";
$stmt = $pdo->query($query);

if ($stmt === false) {
    die("Error en la consulta: " . implode(" ", $pdo->errorInfo()));
}

$stmt_empleado = $pdo->prepare("
    SELECT p.nombre, p.apellido1, c.cargo AS nombre_cargo 
    FROM personal p
    JOIN cargo c ON p.cargo = c.id_cargo
    WHERE p.id_personal = ?
");
$stmt_empleado->execute([$_SESSION['user_id']]);
$empleado = $stmt_empleado->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Productos</title>
    <link rel="stylesheet" href="styles/css_ver.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-boxes"></i> Listado de Productos</h2>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Ref</th>
                    <th>Nombre</th>
                    <th>Unidad</th>
                    <th>Cantidad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($stmt->rowCount() > 0) {
                    while($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>
                                <td>";
                        
                        // Mostrar imagen o icono por defecto
                        $ruta_imagen = !empty($fila['img_prod']) ? trim($fila['img_prod']) : '';
                        
                        if ($ruta_imagen) {
                            // Verificar si la ruta es relativa (empieza con img/)
                            if (strpos($ruta_imagen, 'img/') === 0) {
                                echo "<img src='".htmlspecialchars($ruta_imagen)."' 
                                      style='width:50px;height:50px;object-fit:cover;' 
                                      alt='".htmlspecialchars($fila['nombre_producto'])."'>";
                            } 
                            // Si es una ruta absoluta, verificar que exista
                            elseif (file_exists($ruta_imagen)) {
                                echo "<img src='".htmlspecialchars($ruta_imagen)."' 
                                      style='width:50px;height:50px;object-fit:cover;' 
                                      alt='".htmlspecialchars($fila['nombre_producto'])."'>";
                            } else {
                                echo "<div class='icono-producto'><i class='fas fa-box-open fa-lg'></i></div>";
                            }
                        } else {
                            echo "<div class='icono-producto'><i class='fas fa-box-open fa-lg'></i></div>";
                        }
                        
                        echo "</td>
                                <td>".htmlspecialchars($fila['referencia'])."</td>
                                <td>".htmlspecialchars($fila['nombre_producto'])."</td>
                                <td>".htmlspecialchars($fila['Unidad'])."</td>
                                <td class='cantidad'>".htmlspecialchars($fila['cantidad'])."</td>
                                <td class='acciones'>
                                    <a href='agregar_producto.php?referencia=".urlencode($fila['referencia'])."' class='btn' title='Agregar'>
                                        <i class='fas fa-plus'></i>
                                    </a>
                                    <a href='modificar_producto.php?referencia=".urlencode($fila['referencia'])."' class='btn' title='Editar'>
                                        <i class='fas fa-edit'></i>
                                    </a>
                                    <a href='eliminar_producto.php?referencia=".urlencode($fila['referencia'])."' class='btn' title='Eliminar'>
                                        <i class='fas fa-trash'></i>
                                    </a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='no-products'>No hay productos registrados</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <a href="gestion_productos.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver a Gestión de Productos
        </a>

        <div class="user-info">
            <p>Has iniciado sesión como: <strong><?php echo htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido1']); ?></strong></p>
            <p>Cargo: <strong><?php echo htmlspecialchars($empleado['nombre_cargo']); ?></strong></p>
            <a href="logout.php" style="color: var(--color-secundario);">Cerrar sesión</a>
        </div>
    </div>
</body>
</html>