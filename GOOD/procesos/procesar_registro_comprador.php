<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de BD
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

// Recibir datos
$nombre_apellido = trim($_POST['nombre_apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$direccion = trim($_POST['direccion'] ?? '');
$dni = trim($_POST['dni'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

// Validaciones
$errores = [];

if (empty($nombre_apellido)) $errores[] = 'El nombre es obligatorio';
if (empty($email)) $errores[] = 'El email es obligatorio';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido';
if (empty($password)) $errores[] = 'La contraseña es obligatoria';
if ($password !== $confirm_password) $errores[] = 'Las contraseñas no coinciden';
if (strlen($password) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres';
if (empty($direccion)) $errores[] = 'La dirección es obligatoria';
if (empty($dni)) $errores[] = 'El DNI es obligatorio';

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
    exit();
}

try {
    // Verificar email único
    $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Este email ya está registrado']);
        exit();
    }
    
    // Generar token único de verificación
    $token = bin2hex(random_bytes(32));
    
    // Hashear contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar usuario (activo = 0 hasta verificar email)
    $sql = "INSERT INTO usuarios (email, password, rol, activo, nombre_apellido, direccion, dni, telefono, token_verificacion, verificado, fecha_registro) 
            VALUES (?, ?, 'comprador', 0, ?, ?, ?, ?, ?, 0, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email, $hashedPassword, $nombre_apellido, $direccion, $dni, $telefono, $token]);
    
    // Enviar correo de verificación
    require_once 'enviar_correo.php';
    $resultado_correo = enviarCorreoVerificacion($email, $nombre_apellido, $token);
    
    if ($resultado_correo['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Registro exitoso. Te hemos enviado un correo de verificación. Revisa tu bandeja de entrada (o spam).'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Registro exitoso, pero no se pudo enviar el correo. Contacta al administrador.',
            'debug' => $resultado_correo['message']
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>