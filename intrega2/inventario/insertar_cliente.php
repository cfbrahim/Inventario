<?php
session_start();
include 'conexion.php';

$error = '';
$valores = [
    'nombre' => '',
    'apellido1' => '',
    'apellido2' => '',
    'telefono' => '',
    'email' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Recoger y sanitizar datos
        $valores = [
            'nombre' => htmlspecialchars(trim($_POST['nombre'])),
            'apellido1' => htmlspecialchars(trim($_POST['apellido1'])),
            'apellido2' => htmlspecialchars(trim($_POST['apellido2'] ?? '')),
            'telefono' => preg_replace('/[^0-9]/', '', $_POST['telefono']),
            'email' => filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
            'password' => $_POST['password'],
            'confirm_password' => $_POST['confirm_password']
        ];

        // Validaciones
        if (empty($valores['nombre']) || empty($valores['apellido1']) || 
            empty($valores['email']) || empty($valores['password'])) {
            throw new Exception("Todos los campos obligatorios deben estar completos");
        }

        if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Formato de email inválido");
        }

        if (strlen($valores['password']) < 8) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres");
        }

        if ($valores['password'] !== $valores['confirm_password']) {
            throw new Exception("Las contraseñas no coinciden");
        }

        // Verificar email único
        $stmt = $pdo->prepare("SELECT email FROM cliente WHERE email = ?");
        $stmt->execute([$valores['email']]);
        if ($stmt->fetch()) {
            throw new Exception("El email ya está registrado");
        }

        // Insertar en la base de datos
        $sql = "INSERT INTO cliente
                (nombre, apellido1, apellido2, telefono, email, contraseña)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $valores['nombre'],
            $valores['apellido1'],
            $valores['apellido2'],
            $valores['telefono'],
            $valores['email'],
            password_hash($valores['password'], PASSWORD_DEFAULT)
        ]);

        $_SESSION['registro_exitoso'] = true;
        header("Location: login.php");
        exit();

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
    <title>Registro de Cliente</title>
    <link rel="stylesheet" href="styles/css.css">
</head>
<body>
    <div class="registro-container">
        <div class="form-box">
            <h2>Registro de Cliente</h2>
            <br></br>
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="text" name="nombre" placeholder="Nombre *" 
                           value="<?php echo $valores['nombre']; ?>" required>
                </div>
                
                <div class="form-group">
                    <input type="text" name="apellido1" placeholder="Primer apellido *" 
                           value="<?php echo $valores['apellido1']; ?>" required>
                </div>
                
                <div class="form-group">
                    <input type="text" name="apellido2" placeholder="Segundo apellido" 
                           value="<?php echo $valores['apellido2']; ?>">
                </div>
                
                <div class="form-group">
                    <input type="email" name="email" placeholder="Correo electrónico *" 
                           value="<?php echo $valores['email']; ?>" required>
                </div>
                
                <div class="form-group">
                    <input type="tel" name="telefono" placeholder="Teléfono *" 
                           value="<?php echo $valores['telefono']; ?>" required
                           pattern="[0-9]{9,15}" title="Mínimo 9 dígitos">
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" placeholder="Contraseña *" 
                           minlength="8" required>
                </div>
                
                <div class="form-group">
                    <input type="password" name="confirm_password" 
                           placeholder="Confirmar contraseña *" required>
                </div>
                
                <button type="submit">Registrarse</button>
            </form>
            
            <p class="login-link">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        </div>
    </div>
</body>
</html>