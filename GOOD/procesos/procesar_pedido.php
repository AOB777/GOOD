<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'comprador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$carrito = $_SESSION['carrito'] ?? [];

if (empty($carrito)) {
    echo json_encode(['success' => false, 'message' => 'Carrito vacío']);
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'good';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->beginTransaction();
    
    // Calcular total
    $total = 0;
    foreach($carrito as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
    
    // Obtener dirección del comprador
    $stmt = $conn->prepare("SELECT domicilio, direccion FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
    $direccion = $usuario['domicilio'] ?? $usuario['direccion'] ?? 'Sin dirección registrada';
    
    // Crear pedido
    $stmt = $conn->prepare("INSERT INTO pedidos (comprador_id, total, direccion_entrega, fecha_pedido) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['usuario_id'], $total, $direccion]);
    $pedido_id = $conn->lastInsertId();
    
    // Crear detalles del pedido y actualizar stock
    foreach($carrito as $item) {
        // Insertar detalle
        $stmt = $conn->prepare("INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
        $stmt->execute([$pedido_id, $item['id'], $item['cantidad'], $item['precio']]);
        
        // Actualizar stock
        $stmt = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$item['cantidad'], $item['id']]);
    }
    
    $conn->commit();
    
    // Limpiar carrito
    unset($_SESSION['carrito']);
    
    echo json_encode(['success' => true, 'message' => 'Pedido realizado con éxito', 'pedido_id' => $pedido_id]);
} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>