<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'repartidor') {
    header('Location: login.html');
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'good';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener entregas asignadas
$stmt = $conn->prepare("
    SELECT p.*, u.nombre_apellido as comprador, u.direccion 
    FROM pedidos p 
    JOIN usuarios u ON p.comprador_id = u.id 
    WHERE p.repartidor_id = ? AND p.estado NOT IN ('entregado', 'cancelado')
    ORDER BY p.fecha_pedido ASC
");
$stmt->execute([$_SESSION['usuario_id']]);
$entregas_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener entregas completadas
$stmt = $conn->prepare("
    SELECT p.*, u.nombre_apellido as comprador 
    FROM pedidos p 
    JOIN usuarios u ON p.comprador_id = u.id 
    WHERE p.repartidor_id = ? AND p.estado = 'entregado'
    ORDER BY p.fecha_pedido DESC LIMIT 10
");
$stmt->execute([$_SESSION['usuario_id']]);
$entregas_completadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats = [
    'pendientes' => count($entregas_pendientes),
    'completadas' => count($entregas_completadas)
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G.O.O.D | Panel Repartidor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-custom">
    <div class="container">
        <a class="logo-small" href="dashboard_repartidor.php">G.O.O.D Delivery</a>
        <div>
            <span class="me-3"><i class="fas fa-truck"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <a href="procesos/cerrar_sesion.php" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 style="color: #8B6914;"><i class="fas fa-map-marked-alt"></i> Mis Entregas</h1>
        <p class="text-muted"><?php echo date('d/m/Y H:i'); ?></p>
    </div>
    
    <!-- Tarjetas de estadísticas -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="stat-card">
                <i class="fas fa-clock" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['pendientes']; ?></div>
                <div class="stat-label">ENTREGAS PENDIENTES</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card">
                <i class="fas fa-check-circle" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['completadas']; ?></div>
                <div class="stat-label">ENTREGAS COMPLETADAS</div>
            </div>
        </div>
    </div>
    
    <!-- Entregas pendientes -->
    <div class="glass-card mb-4">
        <h3 class="mb-3" style="color: #8B6914;">
            <i class="fas fa-hourglass-half"></i> Entregas Pendientes
            <?php if($stats['pendientes'] > 0): ?>
                <span class="badge-warning ms-2"><?php echo $stats['pendientes']; ?></span>
            <?php endif; ?>
        </h3>
        <?php if(empty($entregas_pendientes)): ?>
            <div class="text-center py-4">
                <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745;"></i>
                <p class="mt-3 text-muted">¡No tienes entregas pendientes!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>ID Pedido</th>
                            <th>Cliente</th>
                            <th>Dirección</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($entregas_pendientes as $entrega): ?>
                        <tr>
                            <td>#<?php echo $entrega['id']; ?></td>
                            <td><?php echo htmlspecialchars($entrega['comprador']); ?></td>
                            <td><?php echo htmlspecialchars($entrega['direccion_entrega'] ?? $entrega['direccion']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($entrega['fecha_pedido'])); ?></td>
                            <td class="text-gold">$<?php echo number_format($entrega['total'], 2); ?></td>
                            <td><span class="badge-warning"><?php echo strtoupper($entrega['estado']); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-gold" onclick="entregarPedido(<?php echo $entrega['id']; ?>)">
                                    <i class="fas fa-check"></i> Entregar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Historial de entregas -->
    <?php if(!empty($entregas_completadas)): ?>
    <div class="glass-card">
        <h3 class="mb-3" style="color: #8B6914;">
            <i class="fas fa-history"></i> Historial de Entregas
        </h3>
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($entregas_completadas as $entrega): ?>
                    <tr>
                        <td>#<?php echo $entrega['id']; ?></td>
                        <td><?php echo htmlspecialchars($entrega['comprador']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($entrega['fecha_pedido'])); ?></td>
                        <td class="text-gold">$<?php echo number_format($entrega['total'], 2); ?></td>
                        <td><span class="badge-success">ENTREGADO</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function entregarPedido(pedidoId) {
    if (confirm('¿Confirmar que este pedido ha sido entregado?')) {
        fetch('procesos/actualizar_estado_pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: pedidoId, estado: 'entregado' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>