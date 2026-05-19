<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'vendedor') {
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

// Obtener productos del vendedor
$stmt = $conn->prepare("SELECT * FROM productos WHERE vendedor_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats = [
    'total_productos' => count($productos),
    'stock_total' => array_sum(array_column($productos, 'stock')),
    'precio_total' => array_sum(array_column($productos, 'precio'))
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G.O.O.D | Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
        }
        .stock-bajo {
            color: #dc3545;
            font-weight: bold;
        }
        .stock-normal {
            color: #28a745;
        }
        .modal-img {
            max-width: 100%;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-custom">
    <div class="container">
        <a class="logo-small" href="dashboard_vendedor.php">G.O.O.D Vendedor</a>
        <div>
            <span class="me-3"><i class="fas fa-store"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <a href="procesos/cerrar_sesion.php" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 style="color: #8B6914;"><i class="fas fa-box"></i> Mis Productos</h1>
        <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#modalProducto" onclick="limpiarFormulario()">
            <i class="fas fa-plus"></i> Nuevo Producto
        </button>
    </div>
    
    <!-- Tarjetas de estadísticas -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-box" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['total_productos']; ?></div>
                <div class="stat-label">TOTAL PRODUCTOS</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-warehouse" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number"><?php echo $stats['stock_total']; ?></div>
                <div class="stat-label">STOCK TOTAL</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-dollar-sign" style="font-size: 2rem; color: #A67C1E;"></i>
                <div class="stat-number">$<?php echo number_format($stats['precio_total'], 2); ?></div>
                <div class="stat-label">VALOR INVENTARIO</div>
            </div>
        </div>
    </div>
    
    <!-- Lista de productos -->
    <div class="glass-card">
        <h3 class="mb-3" style="color: #8B6914;">
            <i class="fas fa-list"></i> Mis Productos
        </h3>
        <?php if(empty($productos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open" style="font-size: 3rem; color: #ccc;"></i>
                <p class="mt-3 text-muted">No tienes productos aún. ¡Crea tu primer producto!</p>
                <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#modalProducto" onclick="limpiarFormulario()">
                    <i class="fas fa-plus"></i> Agregar Producto
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Descripción</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Categoría</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($productos as $producto): ?>
                        <tr>
                            <td>
                                <?php if(!empty($producto['imagen']) && file_exists($producto['imagen'])): ?>
                                    <img src="<?php echo $producto['imagen']; ?>" class="product-img" alt="producto">
                                <?php else: ?>
                                    <i class="fas fa-box" style="font-size: 2rem; color: #A67C1E;"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $producto['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($producto['nombre']); ?></strong></td>
                            <td><?php echo substr(htmlspecialchars($producto['descripcion'] ?? ''), 0, 50); ?></td>
                            <td class="text-gold">$<?php echo number_format($producto['precio'], 2); ?></td>
                            <td>
                                <?php if($producto['stock'] <= 5): ?>
                                    <span class="stock-bajo"><?php echo $producto['stock']; ?> unidades</span>
                                <?php else: ?>
                                    <span class="stock-normal"><?php echo $producto['stock']; ?> unidades</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge-gold"><?php echo $producto['categoria'] ?? 'General'; ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-gold" onclick="editarProducto(<?php echo $producto['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarProducto(<?php echo $producto['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Agregar/Editar Producto -->
<div class="modal fade" id="modalProducto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-box"></i> Nuevo Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formProducto" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id" id="producto_id">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Nombre del Producto *</label>
                                <input type="text" class="form-control" name="nombre" id="producto_nombre" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="descripcion" id="producto_descripcion" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Precio *</label>
                                    <input type="number" step="0.01" class="form-control" name="precio" id="producto_precio" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Stock *</label>
                                    <input type="number" class="form-control" name="stock" id="producto_stock" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" name="categoria" id="producto_categoria">
                                    <option value="">Seleccione...</option>
                                    <option value="tecnologia">Tecnología</option>
                                    <option value="indumentaria">Indumentaria</option>
                                    <option value="alimentos">Alimentos</option>
                                    <option value="hogar">Hogar</option>
                                    <option value="deportes">Deportes</option>
                                    <option value="electrodomesticos">Electrodomésticos</option>
                                    <option value="juguetes">Juguetes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Imagen del Producto</label>
                                <input type="file" class="form-control" name="imagen" id="producto_imagen" accept="image/*">
                                <div id="imagen_preview" class="mt-2 text-center"></div>
                                <small class="text-muted">Formatos: JPG, PNG. Máximo 2MB</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-gold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-gold">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let editando = false;

function limpiarFormulario() {
    editando = false;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-box"></i> Nuevo Producto';
    document.getElementById('formProducto').reset();
    document.getElementById('producto_id').value = '';
    document.getElementById('imagen_preview').innerHTML = '';
}

function editarProducto(id) {
    editando = true;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Producto';
    
    fetch(`procesos/obtener_producto.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                document.getElementById('producto_id').value = data.producto.id;
                document.getElementById('producto_nombre').value = data.producto.nombre;
                document.getElementById('producto_descripcion').value = data.producto.descripcion;
                document.getElementById('producto_precio').value = data.producto.precio;
                document.getElementById('producto_stock').value = data.producto.stock;
                document.getElementById('producto_categoria').value = data.producto.categoria;
                
                if(data.producto.imagen) {
                    document.getElementById('imagen_preview').innerHTML = `<img src="${data.producto.imagen}" style="max-width: 100%; border-radius: 10px;">`;
                }
                
                new bootstrap.Modal(document.getElementById('modalProducto')).show();
            }
        });
}

function eliminarProducto(id) {
    if(confirm('¿Eliminar este producto permanentemente?')) {
        fetch('procesos/eliminar_producto.php', {
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
}

document.getElementById('formProducto').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const accion = editando ? 'editar' : 'crear';
    formData.append('accion', accion);
    
    const response = await fetch('procesos/procesar_productos.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    if(result.success) {
        location.reload();
    } else {
        alert('Error: ' + result.message);
    }
});

// Vista previa de imagen
document.getElementById('producto_imagen').addEventListener('change', function(e) {
    const preview = document.getElementById('imagen_preview');
    preview.innerHTML = '';
    if(this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = document.createElement('img');
            img.src = event.target.result;
            img.style.maxWidth = '100%';
            img.style.borderRadius = '10px';
            preview.appendChild(img);
        }
        reader.readAsDataURL(this.files[0]);
    }
});
</script>
</body>
</html>