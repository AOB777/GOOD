<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'comprador') {
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

// Obtener categorías para filtro
$stmt = $conn->query("SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND categoria != ''");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Filtros
$categoria_filtro = $_GET['categoria'] ?? '';
$busqueda = $_GET['buscar'] ?? '';
$orden = $_GET['orden'] ?? 'reciente';

$sql = "SELECT p.*, u.nombre_apellido as vendedor, u.nombre, u.apellido 
        FROM productos p 
        JOIN usuarios u ON p.vendedor_id = u.id 
        WHERE p.stock > 0";

$params = [];

if (!empty($categoria_filtro)) {
    $sql .= " AND p.categoria = ?";
    $params[] = $categoria_filtro;
}

if (!empty($busqueda)) {
    $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

switch($orden) {
    case 'precio_asc':
        $sql .= " ORDER BY p.precio ASC";
        break;
    case 'precio_desc':
        $sql .= " ORDER BY p.precio DESC";
        break;
    case 'nombre':
        $sql .= " ORDER BY p.nombre ASC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G.O.O.D | Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .product-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .product-img {
            height: 200px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }
        .price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #8B6914;
        }
        .cart-badge {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        .cart-badge .btn {
            border-radius: 50px;
            padding: 15px 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
    </style>
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

<div class="container py-4">
    <div class="text-center mb-4">
        <h1 style="color: #8B6914;"><i class="fas fa-store"></i> Tienda de Lujo</h1>
        <p class="text-muted">Descubre nuestros productos exclusivos</p>
    </div>
    
    <!-- Filtros y búsqueda -->
    <div class="glass-card mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Buscar productos</label>
                <input type="text" name="buscar" class="form-control" placeholder="Nombre o descripción..." value="<?php echo htmlspecialchars($busqueda); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach($categorias as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $categoria_filtro == $cat ? 'selected' : ''; ?>>
                            <?php echo ucfirst($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ordenar por</label>
                <select name="orden" class="form-select">
                    <option value="reciente" <?php echo $orden == 'reciente' ? 'selected' : ''; ?>>Más reciente</option>
                    <option value="precio_asc" <?php echo $orden == 'precio_asc' ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                    <option value="precio_desc" <?php echo $orden == 'precio_desc' ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                    <option value="nombre" <?php echo $orden == 'nombre' ? 'selected' : ''; ?>>Nombre A-Z</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-gold w-100">Filtrar</button>
            </div>
        </form>
    </div>
    
    <!-- Productos -->
    <div class="row g-4">
        <?php if(empty($productos)): ?>
            <div class="col-12">
                <div class="glass-card text-center py-5">
                    <i class="fas fa-box-open" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="mt-3 text-muted">No se encontraron productos</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach($productos as $producto): ?>
            <div class="col-md-4 col-lg-3">
                <div class="glass-card product-card h-100" onclick="verProducto(<?php echo $producto['id']; ?>)">
                    <div class="text-center">
                        <?php if(!empty($producto['imagen']) && file_exists($producto['imagen'])): ?>
                            <img src="<?php echo $producto['imagen']; ?>" class="product-img img-fluid" style="height: 180px; width: 100%; object-fit: cover; border-radius: 15px;">
                        <?php else: ?>
                            <div style="height: 180px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 15px;">
                                <i class="fas fa-box" style="font-size: 4rem; color: #A67C1E;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <h5 style="color: #8B6914;"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                        <p class="small text-muted">
                            <i class="fas fa-store"></i> <?php echo htmlspecialchars($producto['vendedor'] ?? $producto['nombre'] . ' ' . $producto['apellido']); ?>
                        </p>
                        <p class="small"><?php echo substr(htmlspecialchars($producto['descripcion'] ?? ''), 0, 60); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price">$<?php echo number_format($producto['precio'], 2); ?></span>
                            <button class="btn-gold btn-sm" onclick="event.stopPropagation(); agregarAlCarrito(<?php echo $producto['id']; ?>)">
                                <i class="fas fa-cart-plus"></i> Comprar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Botón flotante del carrito -->
<div class="cart-badge">
    <a href="carrito.php" class="btn-gold">
        <i class="fas fa-shopping-cart"></i> Ver Carrito
    </a>
</div>

<script>
function verProducto(id) {
    window.location.href = `ver_producto.php?id=${id}`;
}

function agregarAlCarrito(id) {
    fetch('procesos/agregar_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, cantidad: 1 })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Producto agregado al carrito');
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>
</body>
</html>