<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'comprador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$producto_id = $data['id'] ?? 0;
$cantidad = intval($data['cantidad'] ?? 0);

if (!$producto_id || $cantidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

if (isset($_SESSION['carrito'][$producto_id])) {
    $_SESSION['carrito'][$producto_id]['cantidad'] = $cantidad;
    echo json_encode(['success' => true, 'message' => 'Cantidad actualizada']);
} else {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
}
?>