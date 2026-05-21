<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'vendedor') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'good';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener imagen para eliminar
    $stmt = $conn->prepare("SELECT imagen FROM productos WHERE id = ? AND vendedor_id = ?");
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    $producto = $stmt->fetch();
    
    if ($producto && $producto['imagen'] && file_exists('../' . $producto['imagen'])) {
        unlink('../' . $producto['imagen']);
    }
    
    $stmt = $conn->prepare("DELETE FROM productos WHERE id = ? AND vendedor_id = ?");
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Producto eliminado']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>