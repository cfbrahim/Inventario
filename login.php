<?php
session_start();
include 'conexion.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validar credenciales
    $stmt = $pdo->prepare("SELECT * FROM cliente WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['contraseña'])) {
        // Credenciales correctas
        $_SESSION['user_id'] = $user['id_cliente'];
        $_SESSION['user_type'] = 'cliente';
        header('Location: panel_cliente.php');
        exit();
    } else {
        // Credenciales incorrectas
        $error = "Correo electrónico o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="styles/css_cliente.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Iniciar Sesión</h1>
                <div class="decorative-line"></div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="tucorreo@inventario.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn-login">Entrar</button>
                <br><br>
                <p class="register-link">¿No tienes cuenta? <a href="insertar_cliente.php">Regístrate</a></p>
            </form>
        </div>
    </div>
</body>
</html>