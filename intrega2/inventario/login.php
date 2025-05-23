<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'conexion.php';
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM cliente WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['contraseña'])) {
        $_SESSION['user_id'] = $user['id_cliente'];
        $_SESSION['user_type'] = 'cliente'; 
        header('Location: productos.php');
        exit();
    } else {
        $error = "ID i/o Contraseña incorrectas";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles/css.css">
</head>
<body>
    <div class="login-container">
        <div class="form-box">
            <h2>Iniciar Sesión</h2>
            <br></br>
            <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Correo electrónico" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
                <button type="submit">Entrar</button>
            </form>
            <p style="margin-top: 1rem; text-align: center;">
                ¿No tienes cuenta? <a href="insertar_cliente.php" style="color: var(--color-primario);">Regístrate</a>
            </p>
        </div>
    </div>
</body>
</html>
