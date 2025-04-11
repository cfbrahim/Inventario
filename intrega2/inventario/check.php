<?php
session_start();

// Redirigir si no está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

// Obtener ruta actual
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page == 'login.php') {
    return; // Salir sin hacer comprobaciones
}

// Definir permisos
$allowed_pages = [
    'admin' => ['*'], // Acceso total
    'empleado' => [
        'login_personal.php',
        'panel_empleado.php'
    ],
    'cliente' => [
        'insertar_cliente.php',
        'login.php',
        'productos.php'
    ]
];

// Verificar permisos
$user_type = $_SESSION['user_type'] ?? 'invitado';

if ($user_type !== 'admin') {
    if (!in_array($current_page, $allowed_pages[$user_type])) {
        header('Location: acceso_denegado.php');
        exit();
    }
}
?>
