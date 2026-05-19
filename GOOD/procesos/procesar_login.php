<?php
header('Content-Type: application/json');
session_start();

// Conexión a BD
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'good';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Complete todos los campos']);
    exit();
}

try {
    $sql = "SELECT id, email, password, rol, activo, nombre_apellido FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Email o contraseña incorrectos']);
        exit();
    }
    
    if (!password_verify($password, $usuario['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email o contraseña incorrectos']);
        exit();
    }
    
    if ($usuario['activo'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Cuenta pendiente de validación']);
        exit();
    }
    
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['email'] = $usuario['email'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['nombre'] = $usuario['nombre_apellido'] ?? $usuario['email'];
    
    // REDIRECCIÓN CORREGIDA - usando rutas relativas correctas
    $redirect = '';
    switch($usuario['rol']) {
        case 'admin':
            $redirect = 'dashboard_admin.php';
            break;
        case 'vendedor':
            $redirect = 'dashboard_vendedor.php';
            break;
        case 'repartidor':
            $redirect = 'dashboard_repartidor.php';
            break;
        default:
            $redirect = 'dashboard_comprador.php';
    }
    
    echo json_encode(['success' => true, 'message' => 'Login exitoso', 'redirect' => $redirect]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>