<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'vendedor') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$accion = $_POST['accion'] ?? '';

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'good';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $categoria = trim($_POST['categoria'] ?? '');
        
        if (empty($nombre) || $precio <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit();
        }
        
        // Subir imagen
        $imagen = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/productos/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $nombreArchivo = 'producto_' . uniqid() . '.' . $extension;
            $rutaDestino = $uploadDir . $nombreArchivo;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                $imagen = 'uploads/productos/' . $nombreArchivo;
            }
        }
        
        $sql = "INSERT INTO productos (vendedor_id, nombre, descripcion, precio, stock, categoria, imagen, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_SESSION['usuario_id'], $nombre, $descripcion, $precio, $stock, $categoria, $imagen]);
        
        echo json_encode(['success' => true, 'message' => 'Producto creado']);
        
    } elseif ($accion === 'editar') {
        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $categoria = trim($_POST['categoria'] ?? '');
        
        // Verificar que el producto pertenece al vendedor
        $check = $conn->prepare("SELECT id, imagen FROM productos WHERE id = ? AND vendedor_id = ?");
        $check->execute([$id, $_SESSION['usuario_id']]);
        $producto = $check->fetch();
        
        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            exit();
        }
        
        $imagen = $producto['imagen'];
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/productos/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $nombreArchivo = 'producto_' . uniqid() . '.' . $extension;
            $rutaDestino = $uploadDir . $nombreArchivo;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                // Eliminar imagen anterior si existe
                if ($imagen && file_exists('../' . $imagen)) {
                    unlink('../' . $imagen);
                }
                $imagen = 'uploads/productos/' . $nombreArchivo;
            }
        }
        
        $sql = "UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, categoria = ?, imagen = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nombre, $descripcion, $precio, $stock, $categoria, $imagen, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Producto actualizado']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>