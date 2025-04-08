<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Conexión a la base de datos
include 'conexion.php';

// Manejo de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM personal WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['contraseña'])) {
        $_SESSION['user_id'] = $user['id_personal'];
        $_SESSION['user_type'] = 'empleado';
        header('Location: panel_empleado.php');
        exit();
    } else {
        $_SESSION['error'] = "Credenciales incorrectas";
        header('Location: login.php'); // Redirige de vuelta al login
        exit();
    }
}

// Manejo de inserción de productos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['referencia'])) {
    // Validación de datos
    $errores = [];
    
    $referencia = trim($_POST['referencia']);
    $nombre_producto = trim($_POST['nombre']);
    $familia_producto = trim($_POST['categoria']);
    $precio = (float)$_POST['precio'];
    $unidad = strtoupper(trim($_POST['unidad']));
    $cantidad = (int)$_POST['cantidad'];
    $descripcion = trim($_POST['descripcion']);

    // Validaciones
    if (empty($referencia)) $errores[] = "La referencia es obligatoria";
    if (empty($nombre_producto)) $errores[] = "El nombre es obligatorio";
    if ($precio <= 0) $errores[] = "El precio debe ser mayor que cero";
    
    $unidades_permitidas = ['L', 'KG', 'U'];
    if (!in_array($unidad, $unidades_permitidas)) {
        $errores[] = "Unidad no válida. Use L, KG o U";
    }

    // Si no hay errores, proceder con la inserción
    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO productos (referencia, nombre, categoria, precio, unidad, cantidad, descripcion) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $referencia,
                $nombre_producto,
                $familia_producto,
                $precio,
                $unidad,
                $cantidad,
                $descripcion
            ]);
            
            $_SESSION['mensaje'] = "Producto añadido correctamente";
            header("Location: gestion_productos.php");
            exit();
        } catch (PDOException $e) {
            $errores[] = "Error al insertar el producto: " . $e->getMessage();
            $_SESSION['errores'] = $errores;
            header("Location: agregar_producto.php"); // Redirige de vuelta al formulario
            exit();
        }
    } else {
        $_SESSION['errores'] = $errores;
        $_SESSION['datos_formulario'] = $_POST;
        header("Location: agregar_producto.php"); // Redirige de vuelta al formulario
        exit();
    }
}

// Cerrar conexión (no necesario con PDO si se usa correctamente)
$pdo = null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto</title>
</head>
<body>
    <h2>Agregar Producto</h2>
    <form action="guardar_producto.php" method="POST">
        Referencia: <input type="text" name="referencia" required><br>
        Nombre: <input type="text" name="nombre" required><br>
        Categoria: <input type="text" name="categoria" required><br>
        Precio: <input type="number" step="0.01" name="precio" required><br>
        Unidad: 
        <select name="unidad" required>
            <option value="">Seleccione unidad</option>
            <option value="L">Litros (L)</option>
            <option value="KG">Kilogramos (KG)</option>
            <option value="U">Unidades (U)</option>
        </select><br>
        Cantidad: <input type="number" name="cantidad" required><br>
        Descripción: <textarea name="description" required></textarea><br>
        <input type="submit" value="Agregar">
    </form>
</body>
</html>
