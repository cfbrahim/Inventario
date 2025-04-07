<?php
$conexion = new mysqli("localhost", "root", "", "gestion_inventario");

$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$precio = $_POST['precio'];
$stock = $_POST['stock'];

$query = "INSERT INTO productos (nombre, descripcion, precio, stock) VALUES ('$nombre', '$descripcion', $precio, $stock)";
$conexion->query($query);

header("Location: gestion_productos.php");
?>
----------------------------------------
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto</title>
</head>
<body>
    <h2>Agregar Producto</h2>
    <form action="guardar_producto.php" method="POST">
        Nombre: <input type="text" name="nombre" required><br>
        Descripción: <textarea name="description" required></textarea><br>
        Precio: <input type="number" step="0.01" name="precio" required><br>
        Stock: <input type="number" name="stock" required><br>
        <input type="submit" value="Agregar">
    </form>
</body>
</html>