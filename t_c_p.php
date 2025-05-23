<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'conexion.php';

// Determinar si es cliente o personal
$esCliente = false;
$esPersonal = false;

// Verificar en tabla cliente
$stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();

if ($cliente) {
    $esCliente = true;
} else {
    // Verificar en tabla personal
    $stmt = $pdo->prepare("SELECT * FROM personal WHERE id_personal = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $personal = $stmt->fetch();
    
    if ($personal) {
        $esPersonal = true;
        // Si es personal pero no debería acceder aquí
        header('Location: panel_personal.php');
        exit();
    } else {
        // Usuario no encontrado en ninguna tabla
        session_destroy();
        header('Location: login.php');
        exit();
    }
}

// Obtener más datos del cliente si es necesario
if ($esCliente) {
    $stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cliente = $stmt->fetch();
    
    // Verificar si el cliente está activo
    if (!$cliente || $cliente['estado'] != 'activo') {
        session_destroy();
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primario: #4a6baf;
            --color-secundario: #3a56a0;
            --color-fondo: #f8f9fa;
            --color-texto: #333;
            --color-exito: #28a745;
            --color-error: #dc3545;
            --color-advertencia: #ffc107;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--color-fondo);
            color: var(--color-texto);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--color-primario);
        }

        .header h1 {
            color: var(--color-primario);
            margin: 0;
        }

        .user-info {
            text-align: right;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--color-primario);
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 10px;
        }

        .btn:hover {
            background: var(--color-secundario);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-logout {
            background: var(--color-error);
        }

        .btn-logout:hover {
            background: #c82333;
        }

        .panel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .panel-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .panel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .panel-card h2 {
            color: var(--color-primario);
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .panel-card ul {
            list-style: none;
            padding: 0;
        }

        .panel-card li {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }

        .panel-card li:last-child {
            border-bottom: none;
        }

        .panel-card a {
            color: var(--color-primario);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .panel-card a:hover {
            color: var(--color-secundario);
            text-decoration: underline;
        }

        .welcome-message {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                text-align: center;
                margin-top: 15px;
            }
            
            .btn {
                margin: 5px;
                display: block;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Panel de Cliente</h1>
            <div class="user-info">
                <p>Bienvenido, <?= htmlspecialchars($cliente['nombre'] ?? 'Usuario') ?></p>
                <a href="logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>

        <div class="welcome-message">
            <h2>Bienvenido a tu área personal</h2>
            <p>Desde aquí puedes gestionar tus pedidos, datos personales y preferencias.</p>
        </div>

        <div class="panel-grid">
            <div class="panel-card">
                <h2><i class="fas fa-shopping-cart"></i> Mis Pedidos</h2>
                <ul>
                    <li><a href="mis_pedidos.php">Ver historial de pedidos</a></li>
                    <li><a href="pedidos_pendientes.php">Pedidos pendientes</a></li>
                    <li><a href="devoluciones.php">Devoluciones y reclamaciones</a></li>
                </ul>
            </div>

            <div class="panel-card">
                <h2><i class="fas fa-user"></i> Mis Datos</h2>
                <ul>
                    <li><a href="mis_datos.php">Ver y editar mis datos</a></li>
                    <li><a href="cambiar_password.php">Cambiar contraseña</a></li>
                    <li><a href="direcciones.php">Gestionar direcciones</a></li>
                </ul>
            </div>

            <div class="panel-card">
                <h2><i class="fas fa-heart"></i> Favoritos</h2>
                <ul>
                    <li><a href="mis_favoritos.php">Productos favoritos</a></li>
                    <li><a href="listas_compra.php">Listas de compra</a></li>
                    <li><a href="recomendaciones.php">Recomendaciones</a></li>
                </ul>
            </div>

            <div class="panel-card">
                <h2><i class="fas fa-cog"></i> Configuración</h2>
                <ul>
                    <li><a href="preferencias.php">Preferencias de comunicación</a></li>
                    <li><a href="privacidad.php">Privacidad y seguridad</a></li>
                    <li><a href="soporte.php">Ayuda y soporte</a></li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
