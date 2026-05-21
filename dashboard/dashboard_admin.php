<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
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

// Estadísticas
$stats = [];
$stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios");
$stats['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'vendedor'");
$stats['total_vendedores'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'repartidor'");
$stats['total_repartidores'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'comprador'");
$stats['total_compradores'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 0 AND rol IN ('vendedor', 'repartidor')");
$stats['pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM productos");
$stats['total_productos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM pedidos");
$stats['total_pedidos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Usuarios pendientes
$stmt = $conn->prepare("SELECT id, email, nombre, apellido, nombre_apellido, rol, fecha_registro FROM usuarios WHERE activo = 0 AND rol IN ('vendedor', 'repartidor') ORDER BY fecha_registro DESC");
$stmt->execute();
$pendientes_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Últimos usuarios registrados
$stmt = $conn->prepare("SELECT id, email, nombre, apellido, nombre_apellido, rol, fecha_registro FROM usuarios ORDER BY fecha_registro DESC LIMIT 5");
$stmt->execute();
$ultimos_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G.O.O.D | Panel Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<nav class="navbar navbar-custom">
    <div class="container">
        <a class="logo-small" href="dashboard_admin.php">G.O.O.D Admin</a>
        <div>
            <span class="me-3"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <a href="procesos/cerrar_sesion.php" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 style="color: #8B6914;"><i class="fas fa-chart-line"></i> Dashboard</h1>
        <p class="text-muted"><?php echo date('d/m/Y H:i'); ?></p>
    </div>
    
    <!-- Tarjetas de estadísticas -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fas fa-users" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['total_usuarios']; ?></div>
                <div class="stat-label">TOTAL USUARIOS</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fas fa-store" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['total_vendedores']; ?></div>
                <div class="stat-label">VENDEDORES</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fas fa-truck" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['total_repartidores']; ?></div>
                <div class="stat-label">REPARTIDORES</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fas fa-shopping-cart" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['total_pedidos']; ?></div>
                <div class="stat-label">PEDIDOS</div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-user-check" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['total_compradores']; ?></div>
                <div class="stat-label">COMPRADORES</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-box" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['total_productos']; ?></div>
                <div class="stat-label">PRODUCTOS</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-clock" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['pendientes']; ?></div>
                <div class="stat-label">PENDIENTES VALIDACIÓN</div>
            </div>
        </div>
    </div>
    
    <!-- Usuarios pendientes de validación -->
    <?php if(!empty($pendientes_lista)): ?>
    <div class="glass-card mb-4">
        <h3 class="mb-3" style="color: #8B6914;">
            <i class="fas fa-user-check"></i> Usuarios por Validar
            <span class="badge-gold ms-2"><?php echo count($pendientes_lista); ?></span>
        </h3>
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pendientes_lista as $usuario): ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['nombre_apellido'] ?? $usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                        <td><span class="badge-warning"><?php echo strtoupper($usuario['rol']); ?></span></td>
                        <td><?php echo $usuario['fecha_registro']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-gold" onclick="validarUsuario(<?php echo $usuario['id']; ?>)">
                                <i class="fas fa-check"></i> Validar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Últimos usuarios registrados -->
    <div class="glass-card">
        <h3 class="mb-3" style="color: #8B6914;">
            <i class="fas fa-user-plus"></i> Últimos Registros
        </h3>
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ultimos_usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['nombre_apellido'] ?? $usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                        <td><span class="badge-gold"><?php echo strtoupper($usuario['rol']); ?></span></td>
                        <td><?php echo $usuario['fecha_registro']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function validarUsuario(id) {
    if (confirm('¿Validar este usuario?')) {
        fetch('procesos/validar_usuario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
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
</body>
</html>