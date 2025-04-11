<?php
// Conexión a la base de datos
include 'conexion.php';

// Consulta SQL corregida (según tu estructura de BD)
$query = "SELECT referencia, nombre_producto, img_prod AS imagen, cantidad FROM productos";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-carton-claro: #e6d5b8;
            --color-fondo: #FAF0E6;
            --color-primario: #D2B48C;
            --color-secundario: #BC8F8F;
            --color-texto: #654321;
            --sombra: 0 4px 6px rgba(0, 0, 0, 0.1);
            --borde-radius: 8px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--color-fondo);
            color: var(--color-texto);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: var(--borde-radius);
            box-shadow: var(--sombra);
        }

        h2 {
            color: var(--color-texto);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--color-primario);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: var(--borde-radius);
            overflow: hidden;
            box-shadow: var(--sombra);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--color-carton-claro);
        }

        th {
            background-color: var(--color-primario);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        tr:hover {
            background-color: rgba(210, 180, 140, 0.1);
        }

        .imagen-circular {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--color-carton-claro);
            box-shadow: var(--sombra);
        }

        .acciones {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 12px;
            border-radius: var(--borde-radius);
            text-decoration: none;
            font-size: 0.85em;
            color: white;
            background-color: var(--color-primario);
            border: 1px solid var(--color-primario);
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn:hover {
            background-color: var(--color-secundario);
            transform: translateY(-2px);
        }

        .btn i {
            margin-right: 5px;
        }

        .no-products {
            text-align: center;
            padding: 20px;
            color: var(--color-secundario);
            font-style: italic;
        }

        /* Estilo para la columna de cantidad */
        .cantidad {
            font-weight: bold;
            color: var(--color-texto);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gestión de Productos</h2>

        <table>
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Referencia</th>
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
               
                        // Construcción de ruta de imagen

                        if (!empty($fila['imagen']) && file_exists($ruta_fisica)) {
                            $imagen_src = $ruta_imagen;
                        } else {
                            $imagen_src = '/inventario/img_prod/fresa.jpg';
                            error_log("Imagen no encontrada: " . $ruta_fisica);
                        }
                        
                        echo "<tr>
                                <td><img src='".htmlspecialchars($imagen_src)."' class='imagen-circular' alt='Producto'></td>
                                <td>".htmlspecialchars($fila['referencia'])."</td>
                                <td>".htmlspecialchars($fila['nombre_producto'])."</td>
                                <td class='cantidad'>".htmlspecialchars($fila['cantidad'])."</td>
                                <td class='acciones'>
                                    <a href='editar_producto.php?referencia=".urlencode($fila['referencia'])."' class='btn' title='Editar'>
                                        <i class='fas fa-edit'></i>
                                    </a>
                                    <a href='agregar_producto.php?referencia=".urlencode($fila['referencia'])."' class='btn' title='Agregar'>
                                        <i class='fas fa-plus'></i>
                                    </a>
                                    <a href='eliminar_producto.php?referencia=".urlencode($fila['referencia'])."' class='btn' title='Eliminar' onclick='return confirm(\"¿Estás seguro?\")'>
                                        <i class='fas fa-trash'></i>
                                    </a>
                                </td>
                              </tr>";
                  {
                    echo "<tr><td colspan='5' class='no-products'>No hay productos registrados</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conexion->close();
?>