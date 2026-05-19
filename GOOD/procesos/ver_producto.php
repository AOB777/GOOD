<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'comprador') {
    header('Location: login.html');
    exit();
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: dashboard_comprador.php');
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'good';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT p.*, u.nombre_apellido as vendedor, u.nombre, u.apellido, u.domicilio 
                            FROM productos p 
                            JOIN usuarios u ON p.vendedor_id = u.id 
                            WHERE p.id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        header('Location: dashboard_comprador.php');
        exit();
    }
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($producto['nombre']); ?> | G.O.O.D</title>
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
            <a href="carrito.php" class="btn btn-outline-gold me-2">
                <i class="fas fa-shopping-cart"></i> Carrito
            </a>
            <a href="procesos/cerrar_sesion.php" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6">
            <div class="glass-card text-center">
                <?php if(!empty($producto['imagen']) && file_exists($producto['imagen'])): ?>
                    <img src="<?php echo $producto['imagen']; ?>" class="img-fluid rounded" style="max-height: 400px;">
                <?php else: ?>
                    <i class="fas fa-box" style="font-size: 8rem; color: #A67C1E;"></i>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="glass-card">
                <h1 style="color: #8B6914;"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
                <p class="text-muted">
                    <i class="fas fa-store"></i> Vendedor: <?php echo htmlspecialchars($producto['vendedor'] ?? $producto['nombre'] . ' ' . $producto['apellido']); ?>
                </p>
                <div class="price" style="font-size: 2rem;">$<?php echo number_format($producto['precio'], 2); ?></div>
                
                <div class="mt-4">
                    <h5>Descripción</h5>
                    <p><?php echo nl2br(htmlspecialchars($producto['descripcion'] ?? '')); ?></p>
                </div>
                
                <div class="mt-4">
                    <p><strong>Categoría:</strong> <?php echo ucfirst($producto['categoria'] ?? 'General'); ?></p>
                    <p><strong>Stock disponible:</strong> <?php echo $producto['stock']; ?> unidades</p>
                </div>
                
                <div class="mt-4">
                    <label class="form-label">Cantidad:</label>
                    <input type="number" id="cantidad" class="form-control" style="width: 100px;" value="1" min="1" max="<?php echo $producto['stock']; ?>">
                    <button class="btn-gold w-100 mt-3" onclick="agregarAlCarrito()">
                        <i class="fas fa-cart-plus"></i> Agregar al Carrito
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function agregarAlCarrito() {
    const cantidad = document.getElementById('cantidad').value;
    fetch('procesos/agregar_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: <?php echo $producto['id']; ?>, cantidad: cantidad })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Producto agregado al carrito');
            window.location.href = 'carrito.php';
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>
</body>
</html>