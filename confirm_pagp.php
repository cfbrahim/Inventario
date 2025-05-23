<?php
session_start();

if (!isset($_SESSION['pedido_exitoso'])) {
    header('Location: productos.php');
    exit();
}

$pedido = $_SESSION['pedido_exitoso'];
unset($_SESSION['pedido_exitoso']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Pago Confirmado!</title>
    <link rel="stylesheet" href="styles/cssp.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primario: #9370DB;
            --color-secundario: #8A2BE2;
            --color-fondo: #F5F0FF;
            --color-texto: #4B0082;
            --color-exito: #4CAF50;
        }
        
        body {
            background-color: var(--color-fondo);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .confirmacion-container {
            text-align: center;
            max-width: 600px;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .icono-exito {
            font-size: 5rem;
            color: var(--color-exito);
            margin-bottom: 1.5rem;
            animation: bounce 1s;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-30px);}
            60% {transform: translateY(-15px);}
        }
        
        h1 {
            color: var(--color-primario);
            margin-bottom: 1.5rem;
        }
        
        .resumen-pedido {
            background: var(--color-fondo);
            padding: 1.5rem;
            border-radius: 10px;
            margin: 2rem 0;
            text-align: left;
        }
        
        .resumen-pedido p {
            margin: 0.5rem 0;
        }
        
        .resumen-pedido strong {
            color: var(--color-secundario);
        }
        
        .btn-volver {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: var(--color-primario);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
        }
        
        .btn-volver:hover {
            background: var(--color-secundario);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="confirmacion-container">
        <div class="icono-exito">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1>¡Pago realizado con éxito!</h1>
        
        <p>Gracias por tu compra. Tu pedido ha sido procesado correctamente.</p>
        
        <div class="resumen-pedido">
            <p><strong>Número de pedido:</strong> #<?= htmlspecialchars($pedido['id_pedido']) ?></p>
            <p><strong>Total pagado:</strong> <?= number_format($pedido['total'], 2) ?> €</p>
            <p><strong>Método de pago:</strong> 
                <?= match($pedido['metodo_pago']) {
                    'tarjeta' => 'Tarjeta de crédito/débito',
                    'paypal' => 'PayPal',
                    'transferencia' => 'Transferencia bancaria',
                    default => $pedido['metodo_pago']
                } ?>
            </p>
        </div>
        
        <p>Recibirás un email de confirmación con los detalles de tu pedido.</p>
        <p><i class="fas fa-truck" style="color: var(--color-primario);"></i> Tu pedido llegará en 2-3 días laborables.</p>
        
        <a href="productos.php" class="btn-volver">
            <i class="fas fa-arrow-left"></i> Volver a la tienda
        </a>
    </div>
</body>
</html>
