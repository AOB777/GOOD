<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'comprador') {
    header('Location: login.html');
    exit();
}

$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
foreach($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G.O.O.D | Mi Carrito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-custom">
    <div class="container">
        <a class="logo-small" href="dashboard_comprador.php">G.O.O.D Store</a>
        <div>
            <span class="me-3"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <a href="procesos/cerrar_sesion.php" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h1 class="text-center mb-4" style="color: #8B6914;"><i class="fas fa-shopping-cart"></i> Mi Carrito</h1>
    
    <?php if(empty($carrito)): ?>
        <div class="glass-card text-center py-5">
            <i class="fas fa-shopping-cart" style="font-size: 3rem; color: #ccc;"></i>
            <p class="mt-3 text-muted">Tu carrito está vacío</p>
            <a href="dashboard_comprador.php" class="btn-gold">Seguir Comprando</a>
        </div>
    <?php else: ?>
        <div class="glass-card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($carrito as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                            <td class="text-gold">$<?php echo number_format($item['precio'], 2); ?></td>
                            <td>
                                <input type="number" class="form-control" style="width: 80px;" value="<?php echo $item['cantidad']; ?>" 
                                       onchange="actualizarCantidad(<?php echo $item['id']; ?>, this.value)">
                            </td>
                            <td class="text-gold">$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarDelCarrito(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td class="text-gold"><strong>$<?php echo number_format($total, 2); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <a href="dashboard_comprador.php" class="btn-outline-gold">Seguir Comprando</a>
                <button class="btn-gold" onclick="finalizarPedido()">Finalizar Pedido</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function actualizarCantidad(id, cantidad) {
    fetch('procesos/actualizar_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, cantidad: parseInt(cantidad) })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function eliminarDelCarrito(id) {
    fetch('procesos/eliminar_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function finalizarPedido() {
    if(confirm('¿Confirmar tu pedido?')) {
        fetch('procesos/procesar_pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Pedido realizado con éxito');
                window.location.href = 'dashboard_comprador.php';
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>
</body>
</html>