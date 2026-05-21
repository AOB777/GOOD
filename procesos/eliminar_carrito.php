<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'comprador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$producto_id = $data['id'] ?? 0;

if (!$producto_id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

if (isset($_SESSION['carrito'][$producto_id])) {
    unset($_SESSION['carrito'][$producto_id]);
    echo json_encode(['success' => true, 'message' => 'Producto eliminado']);
} else {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
}
?>