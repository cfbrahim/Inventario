<?php
session_start();
require_once 'conexion.php';

// Verificar sesión y cesta
if (!isset($_SESSION['user_id']) || !isset($_SESSION['cesta']) || empty($_SESSION['cesta'])) {
    header('Location: login.php');
    exit();
}

// Obtener datos del cliente
$stmt = $pdo->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
$stmt->execute([$_SESSION['user_id']]);
$cliente = $stmt->fetch();

if (!$cliente) {
    $_SESSION['error'] = "No se encontraron datos del cliente";
    header('Location: carrito.php');
    exit();
}

// Obtener productos de la cesta
$productos_cesta = [];
$total = 0;
$items_invalidos = [];

$referencias = array_keys($_SESSION['cesta']);
$placeholders = implode(',', array_fill(0, count($referencias), '?'));
    
$stmt = $pdo->prepare("
    SELECT 
        p.referencia, 
        p.nombre_producto AS nombre, 
        p.precio,
        p.img_prod AS imagen_ruta,
        p.cantidad AS stock
    FROM productos p
    WHERE p.referencia IN ($placeholders)
");
$stmt->execute($referencias);
$productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar stock y preparar productos
foreach ($productos_db as $producto) {
    $referencia = $producto['referencia'];
    $cantidad_solicitada = $_SESSION['cesta'][$referencia];
    
    if ($cantidad_solicitada > $producto['stock']) {
        $items_invalidos[] = [
            'producto' => $producto['nombre'],
            'stock' => $producto['stock'],
            'solicitado' => $cantidad_solicitada
        ];
        continue;
    }
    
    $subtotal = $producto['precio'] * $cantidad_solicitada;
    $total += $subtotal;
    
    $productos_cesta[] = [
        'referencia' => $referencia,
        'nombre' => $producto['nombre'],
        'precio' => $producto['precio'],
        'cantidad' => $cantidad_solicitada,
        'subtotal' => $subtotal,
        'imagen' => !empty($producto['imagen_ruta']) ? $producto['imagen_ruta'] : 'img/default-product.png',
        'stock' => $producto['stock']
    ];
}

// Si hay items sin stock suficiente, redirigir al carrito
if (!empty($items_invalidos)) {
    $_SESSION['items_sin_stock'] = $items_invalidos;
    header('Location: carrito.php');
    exit();
}

// Procesar pago
$error = null;
$pago_procesado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_pago'])) {
    // Validar datos básicos
    $required = ['direccion', 'metodo_pago'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $error = "Todos los campos obligatorios deben estar completos";
            break;
        }
    }
    
    // Validar datos específicos del método de pago
    if (!$error) {
        $metodo_pago = $_POST['metodo_pago'];
        $datos_pago_validos = true;
        
        if ($metodo_pago === 'tarjeta') {
            $campos_tarjeta = ['nombre_tarjeta', 'numero_tarjeta', 'expiracion', 'cvv'];
            foreach ($campos_tarjeta as $campo) {
                if (empty($_POST[$campo])) {
                    $error = "Por favor complete todos los datos de la tarjeta";
                    $datos_pago_validos = false;
                    break;
                }
            }
            
            // Validar formato de tarjeta (solo ejemplo básico)
            if ($datos_pago_validos && !preg_match('/^\d{16}$/', str_replace(' ', '', $_POST['numero_tarjeta']))) {
                $error = "El número de tarjeta no es válido";
                $datos_pago_validos = false;
            }
            
        } elseif ($metodo_pago === 'paypal') {
            if (empty($_POST['email_paypal']) || !filter_var($_POST['email_paypal'], FILTER_VALIDATE_EMAIL)) {
                $error = "Por favor ingrese un email de PayPal válido";
                $datos_pago_validos = false;
            }
        }
        
        // Si todo está validado, procesar el pago
        if ($datos_pago_validos) {
            try {
                $pdo->beginTransaction();
                
                // 1. Crear el pedido
                $stmt = $pdo->prepare("
                    INSERT INTO pedidos 
                    (id_cliente, fecha_pedido, estado, total, direccion_envio, metodo_pago, notas) 
                    VALUES (?, NOW(), 'pendiente', ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $total,
                    $_POST['direccion'],
                    $metodo_pago,
                    $_POST['notas'] ?? ''
                ]);
                $id_pedido = $pdo->lastInsertId();
                
                // 2. Añadir productos al pedido
                $stmt = $pdo->prepare("
                    INSERT INTO detalle_pedido 
                    (id_pedido, referencia, cantidad, precio_unitario) 
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach ($productos_cesta as $producto) {
                    $stmt->execute([
                        $id_pedido,
                        $producto['referencia'],
                        $producto['cantidad'],
                        $producto['precio']
                    ]);
                    
                    // Actualizar stock
                    $pdo->prepare("UPDATE productos SET cantidad = cantidad - ? WHERE referencia = ?")
                       ->execute([$producto['cantidad'], $producto['referencia']]);
                }
                
                // 3. Vaciar cesta y redirigir
                unset($_SESSION['cesta']);
                $pdo->commit();
                
                $_SESSION['pedido_exitoso'] = [
                    'id_pedido' => $id_pedido,
                    'total' => $total,
                    'metodo_pago' => $metodo_pago,
                    'fecha' => date('Y-m-d H:i:s')
                ];
                header('Location: confirm_pagp.php');
                exit();
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Error al procesar el pago. Por favor intente nuevamente.";
                error_log("Error en checkout: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - <?= htmlspecialchars($cliente['nombre'] ?? 'Cliente') ?></title>
    <link rel="stylesheet" href="styles/cssp.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primario: #9370DB;
            --color-secundario: #8A2BE2;
            --color-fondo: #F5F0FF;
            --color-texto: #4B0082;
            --color-error: #ff6b6b;
            --color-exito: #4CAF50;
            --color-advertencia: #FFA500;
        }
        
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }
        
        .checkout-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .checkout-title {
            color: var(--color-primario);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .checkout-subtitle {
            color: var(--color-texto);
            font-size: 1.1rem;
            opacity: 0.8;
        }
        
        .decorative-line {
            width: 80px;
            height: 3px;
            background: var(--color-secundario);
            margin: 1rem auto;
            border-radius: 2px;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        @media (min-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .checkout-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: var(--color-primario);
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--color-fondo);
        }
        
        .resumen-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .resumen-item-img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-right: 1rem;
            border-radius: 5px;
            border: 1px solid #eee;
        }
        
        .resumen-item-info {
            flex-grow: 1;
        }
        
        .resumen-item-nombre {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .resumen-item-detalle {
            font-size: 0.9rem;
            color: #666;
        }
        
        .resumen-item-precio {
            font-weight: bold;
            min-width: 80px;
            text-align: right;
        }
        
        .resumen-totales {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid var(--color-fondo);
        }
        
        .resumen-linea {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }
        
        .resumen-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--color-secundario);
        }
        
        .datos-cliente {
            margin-bottom: 1.5rem;
        }
        
        .datos-cliente p {
            margin-bottom: 0.8rem;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #eee;
        }
        
        .datos-cliente strong {
            color: var(--color-secundario);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--color-texto);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--color-primario);
            outline: none;
            box-shadow: 0 0 0 3px rgba(147, 112, 219, 0.2);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .metodo-pago {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .metodo-pago:hover {
            border-color: var(--color-primario);
        }
        
        .metodo-pago.selected {
            border-color: var(--color-primario);
            background-color: rgba(147, 112, 219, 0.1);
        }
        
        .metodo-pago input[type="radio"] {
            margin-right: 1rem;
            transform: scale(1.2);
        }
        
        .metodo-pago i {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--color-primario);
        }
        
        .metodo-pago-info {
            flex-grow: 1;
        }
        
        .metodo-pago-titulo {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .metodo-pago-desc {
            font-size: 0.9rem;
            color: #666;
        }
        
        .formulario-pago {
            display: none;
            margin-top: 1rem;
            padding: 1.5rem;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #ddd;
            animation: fadeIn 0.3s ease-out;
        }
        
        .formulario-pago.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .grupo-tarjeta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .campo-tarjeta-completo {
            grid-column: span 2;
        }
        
        .logo-metodo {
            height: 25px;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .btn-pagar {
            display: block;
            width: 100%;
            padding: 1rem;
            background: var(--color-primario);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            text-align: center;
        }
        
        .btn-pagar:hover {
            background: var(--color-secundario);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-pagar:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-pagar i {
            margin-right: 8px;
        }
        
        .error-message {
            color: var(--color-error);
            padding: 1rem;
            background: #ffeeee;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
            border-left: 4px solid var(--color-error);
        }
        
        .info-message {
            color: var(--color-texto);
            padding: 1rem;
            background: #f0f5ff;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
            border-left: 4px solid var(--color-primario);
        }
        
        .advertencia-stock {
            font-size: 0.8rem;
            color: var(--color-advertencia);
            margin-top: 0.3rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .checkout-container {
                padding: 0.5rem;
            }
            
            .checkout-section {
                padding: 1rem;
            }
            
            .grupo-tarjeta {
                grid-template-columns: 1fr;
            }
            
            .campo-tarjeta-completo {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-header">
            <h1 class="checkout-title">Finalizar Compra</h1>
            <p class="checkout-subtitle">Revise su pedido y complete los datos de envío</p>
            <div class="decorative-line"></div>
        </div>
        
        <form method="POST" class="checkout-grid" id="formulario-checkout">
            <div class="checkout-section">
                <h2 class="section-title">Resumen del Pedido</h2>
                
                <?php foreach ($productos_cesta as $item): ?>
                    <div class="resumen-item">
                        <img src="<?= htmlspecialchars($item['imagen']) ?>" 
                             alt="<?= htmlspecialchars($item['nombre']) ?>" 
                             class="resumen-item-img"
                             onerror="this.src='img/default-product.png'">
                        
                        <div class="resumen-item-info">
                            <div class="resumen-item-nombre"><?= htmlspecialchars($item['nombre']) ?></div>
                            <div class="resumen-item-detalle">
                                <?= $item['cantidad'] ?> x <?= number_format($item['precio'], 2) ?> €
                                <?php if ($item['cantidad'] > 1): ?>
                                    <span class="advertencia-stock">
                                        (<?= $item['stock'] ?> disponibles)
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="resumen-item-precio">
                            <?= number_format($item['subtotal'], 2) ?> €
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="resumen-totales">
                    <div class="resumen-linea">
                        <span>Subtotal:</span>
                        <span><?= number_format($total, 2) ?> €</span>
                    </div>
                    <div class="resumen-linea">
                        <span>Envío:</span>
                        <span>Gratis</span>
                    </div>
                    <div class="resumen-linea resumen-total">
                        <span>Total:</span>
                        <span><?= number_format($total, 2) ?> €</span>
                    </div>
                </div>
            </div>
            
            <div class="checkout-section">
                <h2 class="section-title">Datos de Envío</h2>
                
                <div class="datos-cliente">
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($cliente['nombre'] . ' ' . ($cliente['apellido1'] ?? '')) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($cliente['email']) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['telefono'] ?? 'No especificado') ?></p>
                    
                    <div class="form-group">
                        <label for="direccion">Dirección de envío *</label>
                        <textarea id="direccion" name="direccion" class="form-control" rows="3" required><?= htmlspecialchars($_POST['direccion'] ?? $cliente['direccion'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notas">Notas adicionales (opcional)</label>
                        <textarea id="notas" name="notas" class="form-control" rows="2" placeholder="Instrucciones especiales para la entrega..."><?= htmlspecialchars($_POST['notas'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <h2 class="section-title" style="margin-top: 2rem;">Método de Pago *</h2>
                
                <label class="metodo-pago <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'tarjeta') ? 'selected' : '' ?>">
                    <input type="radio" name="metodo_pago" value="tarjeta" id="metodo-tarjeta" 
                           <?= (!isset($_POST['metodo_pago']) || (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'tarjeta')) ? 'checked' : '' ?> required>
                    <i class="fas fa-credit-card"></i>
                    <div class="metodo-pago-info">
                        <div class="metodo-pago-titulo">Tarjeta de crédito/débito</div>
                        <div class="metodo-pago-desc">Pago seguro con tarjeta</div>
                    </div>
                </label>
                
                <div id="form-tarjeta" class="formulario-pago <?= (!isset($_POST['metodo_pago']) || (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'tarjeta')) ? 'active' : '' ?>">
                    <div class="form-group">
                        <label for="nombre_tarjeta">Nombre en la tarjeta *</label>
                        <input type="text" id="nombre_tarjeta" name="nombre_tarjeta" class="form-control" 
                               value="<?= htmlspecialchars($_POST['nombre_tarjeta'] ?? '') ?>" 
                               <?= (!isset($_POST['metodo_pago']) || (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'tarjeta')) ? 'required' : '' ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_tarjeta">Número de tarjeta *</label>
                        <input type="text" id="numero_tarjeta" name="numero_tarjeta" class="form-control" 
                               value="<?= htmlspecialchars($_POST['numero_tarjeta'] ?? '') ?>" 
                               placeholder="1234 5678 9012 3456"
                               <?= (!isset($_POST['metodo_pago']) || (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'tarjeta')) ? 'required' : '' ?>>
                        <img src="img/credit-cards.png" alt="Tarjetas aceptadas" class="logo-metodo">
                    </div>
                    
                    <div class="form-group grupo-tarjeta">
                        <div>
                            <label for="expiracion">Fecha expiración (MM/AA) *</label>
                            <input type="text" id="expiracion" name="expiracion" class="form-control" 
                                   placeholder="MM/AA" 
                                   value="<?= htmlspecialchars($_POST['expiracion'] ?? '') ?>"
                                   <?= (!isset($_POST['metodo_pago']) || (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'tarjeta')) ? 'required' : '' ?>>
                        </div>
                        
                        <div>
                            <label for="cvv">CVV *</label>
                            <input type="text" id="cvv" name="cvv" class="form-control" 
                                   placeholder="123" 
                                   value="<?= htmlspecialchars($_POST['cvv'] ?? '') ?>"
                                   <?= (!isset($_POST['metodo_pago']) || (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'tarjeta')) ? 'required' : '' ?>>
                        </div>
                    </div>
                </div>
                
                <label class="metodo-pago <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'paypal') ? 'selected' : '' ?>">
                    <input type="radio" name="metodo_pago" value="paypal" id="metodo-paypal" 
                           <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'paypal') ? 'checked' : '' ?>>
                    <i class="fab fa-paypal"></i>
                    <div class="metodo-pago-info">
                        <div class="metodo-pago-titulo">PayPal</div>
                        <div class="metodo-pago-desc">Paga con tu cuenta PayPal</div>
                    </div>
                </label>
                
                <div id="form-paypal" class="formulario-pago <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'paypal') ? 'active' : '' ?>">
                    <div class="form-group">
                        <label for="email_paypal">Email de PayPal *</label>
                        <input type="email" id="email_paypal" name="email_paypal" class="form-control" 
                               value="<?= htmlspecialchars($_POST['email_paypal'] ?? $cliente['email'] ?? '') ?>"
                               <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'paypal') ? 'required' : '' ?>>
                        <img src="img/paypal-logo.png" alt="PayPal" class="logo-metodo">
                    </div>
                </div>
                
                <label class="metodo-pago <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'transferencia') ? 'selected' : '' ?>">
                    <input type="radio" name="metodo_pago" value="transferencia" id="metodo-transferencia" 
                           <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'transferencia') ? 'checked' : '' ?>>
                    <i class="fas fa-university"></i>
                    <div class="metodo-pago-info">
                        <div class="metodo-pago-titulo">Transferencia bancaria</div>
                        <div class="metodo-pago-desc">Realiza una transferencia</div>
                    </div>
                </label>
                
                <div id="form-transferencia" class="formulario-pago <?= (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'transferencia') ? 'active' : '' ?>">
                    <div class="form-group">
                        <p>Por favor realice una transferencia a:</p>
                        <p><strong>Banco:</strong> TuBanco S.A.</p>
                        <p><strong>IBAN:</strong> ESXX XXXX XXXX XXXX XXXX XXXX</p>
                        <p><strong>Beneficiario:</strong> TuEmpresa SL</p>
                        <p><strong>Concepto:</strong> Pedido #<?= isset($_SESSION['user_id']) ? 'USR'.$_SESSION['user_id'] : '' ?></p>
                        <p>El pedido se procesará al confirmarse el pago.</p>
                    </div>
                </div>
                
                <input type="hidden" name="procesar_pago" value="1">
                <button type="submit" class="btn-pagar" id="btn-pagar">
                    <i class="fas fa-lock"></i> Confirmar y Pagar
                </button>
                
                <div class="info-message" style="margin-top: 1rem;">
                    <i class="fas fa-shield-alt"></i> Tus datos están protegidos. No almacenamos información de pago.
                </div>
            </div>
        </form>
    </div>

    <script>
        // Mostrar formulario de pago según método seleccionado
        document.querySelectorAll('input[name="metodo_pago"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Ocultar todos los formularios
                document.querySelectorAll('.formulario-pago').forEach(form => {
                    form.classList.remove('active');
                    // Deshabilitar campos requeridos
                    form.querySelectorAll('[required]').forEach(campo => {
                        campo.required = false;
                    });
                });
                
                // Mostrar el correspondiente
                const formId = 'form-' + this.value;
                const formActivo = document.getElementById(formId);
                if (formActivo) {
                    formActivo.classList.add('active');
                    // Habilitar campos requeridos
                    formActivo.querySelectorAll('[required]').forEach(campo => {
                        campo.required = true;
                    });
                }
                
                // Resaltar método seleccionado
                document.querySelectorAll('.metodo-pago').forEach(el => {
                    el.classList.remove('selected');
                });
                this.closest('.metodo-pago').classList.add('selected');
            });
        });
        
        // Formatear número de tarjeta (grupos de 4 dígitos)
        document.getElementById('numero_tarjeta')?.addEventListener('input', function(e) {
            let value = this.value.replace(/\s+/g, '').replace(/\D/g, '');
            let formatted = '';
            
            for (let i = 0; i < value.length && i < 16; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += ' ';
                }
                formatted += value[i];
            }
            
            this.value = formatted;
        });
        
        // Formatear fecha expiración (MM/AA)
        document.getElementById('expiracion')?.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            
            if (value.length > 2) {
                this.value = value.substring(0, 2) + '/' + value.substring(2, 4);
            } else {
                this.value = value;
            }
        });
        
        // Validar CVV (3 o 4 dígitos)
        document.getElementById('cvv')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 4);
        });
        
        // Validar formulario antes de enviar
        document.getElementById('formulario-checkout')?.addEventListener('submit', function(e) {
            const btnPagar = document.getElementById('btn-pagar');
            btnPagar.disabled = true;
            btnPagar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando pago...';
            
            // Aquí podrías añadir validaciones adicionales si es necesario
            
            return true;
        });
        
        // Inicializar formularios según método seleccionado
        document.addEventListener('DOMContentLoaded', function() {
            const metodoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
            if (metodoSeleccionado) {
                metodoSeleccionado.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>