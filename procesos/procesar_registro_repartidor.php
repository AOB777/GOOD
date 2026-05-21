<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Recibir datos específicos de REPARTIDOR
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$dni = trim($_POST['dni'] ?? '');
$domicilio = trim($_POST['domicilio'] ?? '');
$patente = trim($_POST['patente'] ?? '');
$seguro = trim($_POST['seguro'] ?? '');
$licencia = trim($_POST['licencia'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

// Subir imagen del vehículo
$vehiculo_imagen = null;
if (isset($_FILES['vehiculo_imagen']) && $_FILES['vehiculo_imagen']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/vehiculos/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $extension = pathinfo($_FILES['vehiculo_imagen']['name'], PATHINFO_EXTENSION);
    $nombreArchivo = 'vehiculo_' . uniqid() . '.' . $extension;
    $rutaDestino = $uploadDir . $nombreArchivo;
    
    if (move_uploaded_file($_FILES['vehiculo_imagen']['tmp_name'], $rutaDestino)) {
        $vehiculo_imagen = 'uploads/vehiculos/' . $nombreArchivo;
    }
}

// Validaciones
$errores = [];

if (empty($nombre)) $errores[] = 'El nombre es obligatorio';
if (empty($apellido)) $errores[] = 'El apellido es obligatorio';
if (empty($email)) $errores[] = 'El email es obligatorio';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido';
if (empty($password)) $errores[] = 'La contraseña es obligatoria';
if ($password !== $confirm_password) $errores[] = 'Las contraseñas no coinciden';
if (strlen($password) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres';
if (empty($dni)) $errores[] = 'El DNI es obligatorio';
if (empty($domicilio)) $errores[] = 'El domicilio es obligatorio';
if (empty($patente)) $errores[] = 'La patente es obligatoria';
if (empty($seguro)) $errores[] = 'El seguro es obligatorio';
if (empty($licencia)) $errores[] = 'La licencia es obligatoria';

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
    
    // Generar token único
    $token = bin2hex(random_bytes(32));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar REPARTIDOR (activo = 0)
    $sql = "INSERT INTO usuarios (email, password, rol, activo, nombre, apellido, dni, domicilio, patente, seguro, licencia, vehiculo_imagen, telefono, token_verificacion, verificado, fecha_registro) 
            VALUES (?, ?, 'repartidor', 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email, $hashedPassword, $nombre, $apellido, $dni, $domicilio, $patente, $seguro, $licencia, $vehiculo_imagen, $telefono, $token]);
    
    // Enviar correo
    require_once 'enviar_correo.php';
    $nombre_completo = $nombre . ' ' . $apellido;
    $resultado_correo = enviarCorreoVerificacion($email, $nombre_completo, $token);
    
    if ($resultado_correo['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Registro exitoso. Te hemos enviado un correo de verificación.'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Registro exitoso, pero no se pudo enviar el correo.'
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>