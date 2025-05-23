<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'conexion.php';
    
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
        $error = "Credenciales incorrectas";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Personal</title>
    <link rel="stylesheet" href="styles/csspers.css">
</head>
<body>
    <div class="registro-container">
        <div class="form-box">
            <div class="header-section">
                <h2>Acceso Personal</h2>
                <div class="deco-shape"></div>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Correo electrónico" required>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
                
                <div class="button-group">
                    <button class="btn-registro" type="submit" >
                        <span>Conectar</span>
                        <div class="btn-shape"></div>
                    </button>
                 <!--   <a href="nuevo_empleado.php" class="btn-nuevo">
                    <p>Bienvenid@s Compis</p><span>Nuevo Trabajador</span>
                        <div class="btn-shape"></div>-->
                        <p style="margin-top: 1rem; text-align: center;">
                            ¡ Bienvenid@s Compis !  
            </p>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>