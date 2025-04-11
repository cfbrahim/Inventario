<?php
require_once 'check.php'; // Verifica acceso y sesión

// Solo permitir empleados y admin
if ($_SESSION['user_type'] !== 'empleado' && $_SESSION['user_type'] !== 'admin') {
    header('Location: acceso_denegado.php');
    exit();
}

// Obtener datos del empleado con JOIN
require_once 'conexion.php';
$stmt = $pdo->prepare("
    SELECT p.nombre, p.apellido1, c.cargo AS nombre_cargo 
    FROM personal p
    JOIN cargo c ON p.cargo = c.id_cargo
    WHERE p.id_personal = ?
");
$stmt->execute([$_SESSION['user_id']]);
$empleado = $stmt->fetch();

// Determinar si es admin basado en el nombre del cargo
$es_admin = (strtolower($empleado['nombre_cargo']) == 'admin');
$_SESSION['user_type'] = $es_admin ? 'admin' : 'empleado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de <?= htmlspecialchars($empleado['nombre_cargo']) ?></title>
    <link rel="stylesheet" href="tu_estilo.css">
    <style>
        :root {
            --color-carton-claro: #e6d5b8;
            --color-fondo: #FAF0E6;
            --color-primario: #D2B48C;
            --color-secundario: #BC8F8F;
            --color-texto: #654321;
            --sombra: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .panel-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
            background: var(--color-fondo);
        }

        .panel-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .panel-title {
            color: var(--color-texto);
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .panel-subtitle {
            color: var(--color-secundario);
            font-style: italic;
        }

        .deco-line {
            width: 80px;
            height: 3px;
            background: var(--color-secundario);
            margin: 0 auto;
        }

        .buttons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            width: 100%;
            max-width: 1000px;
        }

        .panel-btn {
            position: relative;
            padding: 1.5rem;
            border: 2px solid var(--color-primario);
            border-radius: 12px;
            background: white;
            color: var(--color-texto);
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--sombra);
            text-align: center;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }

        .panel-btn:hover {
            background: var(--color-carton-claro);
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
            border-color: var(--color-secundario);
        }

        .panel-btn i {
            font-size: 2rem;
            margin-bottom: 0.8rem;
            color: var(--color-secundario);
        }

        .user-info {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            border: 1px solid var(--color-primario);
            text-align: center;
        }

        @media (max-width: 768px) {
            .buttons-grid {
                grid-template-columns: 1fr;
            }
            
            .panel-title {
                font-size: 1.8rem;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="panel-container">
        <div class="panel-header">
            <h1 class="panel-title">Panel de <?= htmlspecialchars($empleado['nombre_cargo']) ?></h1>
            <p class="panel-subtitle">Bienvenido/a al sistema de gestión</p>
            <div class="deco-line"></div>
        </div>

        <div class="buttons-grid">
          

            <a href="gestion_productos.php" class="panel-btn">
                <i class="fas fa-clipboard-list"></i>
                Gestión de Productos
            </a>

            <a href="inventario.php" class="panel-btn">
                <i class="fas fa-boxes"></i>
                Control de Inventario
            </a>

            <?php if ($es_admin): ?>
                <a href="gestion_clientes.php" class="panel-btn">
                <i class="fas fa-users"></i>
                Gestión de Clientes
                </a>

                <a href="reportes.php" class="panel-btn">
                    <i class="fas fa-chart-bar"></i>
                    Generar Reportes
                </a>
                
                <a href="configuracion.php" class="panel-btn">
                    <i class="fas fa-cog"></i>
                    Configuración
                </a>
            <?php endif; ?>

            <a href="calendario.php" class="panel-btn">
                <i class="fas fa-calendar-alt"></i>
                Calendario de Entregas
            </a>
        </div>

        <div class="user-info">
            <p>Has iniciado sesión como: <strong><?= htmlspecialchars($empleado['nombre'] . ' ' . $empleado['apellido1']) ?></strong></p>
            <p>Cargo: <strong><?= htmlspecialchars($empleado['nombre_cargo']) ?></strong></p>
            <a href="login_personal.php" style="color: var(--color-secundario);">Cerrar sesión</a>
        </div>
    </div>
</body>
</html>