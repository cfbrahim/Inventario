<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// 🔥🔥🔥 VERIFICACIÓN DE ADMIN (AÑADIR ESTO AL PRINCIPIO) 🔥🔥🔥
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: acceso_denegado.php'); // O redirige a panel_empleado.php
    exit();
}


include 'conexion.php';

$error = '';
$valores = [
    'nombre' => '',
    'apellido1' => '',
    'apellido2' => '',
    'telefono' => '',
    'email' => '',
    'cargo' => ''
];

function generarIdPersonal() {
    return substr(str_shuffle('ABCDEF0123456789'), 0, 3);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $valores = [
            'nombre' => htmlspecialchars(trim($_POST['nombre'])),
            'apellido1' => htmlspecialchars(trim($_POST['apellido1'])),
            'apellido2' => htmlspecialchars(trim($_POST['apellido2'] ?? '')),
            'telefono' => preg_replace('/[^0-9]/', '', $_POST['telefono']),
            'email' => filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
            'cargo' => $_POST['cargo'],
            'password' => $_POST['password'],
            'confirm_password' => $_POST['confirm_password']
        ];

        // Validaciones
        $required = ['nombre', 'apellido1', 'email', 'telefono', 'password', 'cargo'];
        foreach ($required as $campo) {
            if (empty($valores[$campo])) {
                throw new Exception("Todos los campos obligatorios (*) deben estar completos");
            }
        }

        if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Formato de email inválido");
        }

        if ($valores['password'] !== $valores['confirm_password']) {
            throw new Exception("Las contraseñas no coinciden");
        }

        if (strlen($valores['password']) < 8) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres");
        }

        // Verificar email único
        $stmt = $pdo->prepare("SELECT email FROM personal WHERE email = ?");
        $stmt->execute([$valores['email']]);
        if ($stmt->fetch()) {
            throw new Exception("El email ya está registrado");
        }

        // Verificar cargo válido
        $stmt = $pdo->prepare("SELECT id_cargo FROM cargo WHERE id_cargo = ?");
        $stmt->execute([$valores['cargo']]);
        if (!$stmt->fetch()) {
            throw new Exception("Seleccione un cargo válido");
        }

        // Generar datos
        $id_personal = generarIdPersonal();
        $fecha_contratacion = date('Y-m-d');

        // Insertar
        $sql = "INSERT INTO personal 
                (id_personal, nombre, apellido1, apellido2, telefono, email, contraseña, fecha_contratacion, cargo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_personal,
            $valores['nombre'],
            $valores['apellido1'],
            $valores['apellido2'],
            $valores['telefono'],
            $valores['email'],
            password_hash($valores['password'], PASSWORD_DEFAULT),
            $fecha_contratacion,
            $valores['cargo']
        ]);

        $_SESSION['registro_exitoso'] = true;
        header("Location: login_personal.php");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener cargos
$cargos = $pdo->query("SELECT id_cargo, cargo FROM cargo ORDER BY cargo")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Empleado</title>
    <link rel="stylesheet" href="styles/csspers.css">
</head>
<body>
    <div class="registro-container">
        <div class="form-box">
            <div class="header-section">
                <h2>Registro de Trabajador</h2>
                <div class="deco-line"></div>
            </div>

            <?php if($error): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="text" name="nombre" placeholder="Nombre *" 
                           value="<?= $valores['nombre'] ?>" required>
                </div>
                
                <div class="form-group">
                    <input type="text" name="apellido1" placeholder="Primer apellido *" 
                           value="<?= $valores['apellido1'] ?>" required>
                </div>
                
                <div class="form-group">
                    <input type="text" name="apellido2" placeholder="Segundo apellido" 
                           value="<?= $valores['apellido2'] ?>">
                </div>
                
                <div class="form-group">
                    <input type="email" name="email" placeholder="Correo electrónico *" 
                           value="<?= $valores['email'] ?>" required>
                </div>
                
                <div class="form-group">
                    <input type="tel" name="telefono" placeholder="Teléfono *" 
                           value="<?= $valores['telefono'] ?>" 
                           pattern="[0-9]{9,15}" title="Mínimo 9 dígitos" required>
                </div>
                
                <div class="form-group">
                    <select name="cargo" class="styled-select" required>
                        <option value="">Seleccione un cargo *</option>
                        <?php foreach ($cargos as $cargo): ?>
                            <option value="<?= $cargo['id_cargo'] ?>" 
                                <?= ($valores['cargo'] == $cargo['id_cargo']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cargo['cargo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" placeholder="Contraseña *" 
                           minlength="8" required>
                </div>
                
                <div class="form-group">
                    <input type="password" name="confirm_password" 
                           placeholder="Confirmar contraseña *" required>
                </div>
                
                <button type="submit" class="btn-registro">
                    Registrar Trabajador
                    <div class="btn-border"></div>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
