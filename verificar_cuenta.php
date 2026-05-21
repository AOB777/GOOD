<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'good';

$mensaje = '';
$tipo_mensaje = 'danger';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $token = $_GET['token'] ?? '';
    
    if (empty($token)) {
        $mensaje = 'Token de verificación no proporcionado';
    } else {
        $stmt = $conn->prepare("SELECT id, email, nombre_apellido, verificado FROM usuarios WHERE token_verificacion = ?");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            $mensaje = 'Token de verificación inválido o expirado';
        } elseif ($usuario['verificado'] == 1) {
            $mensaje = 'Esta cuenta ya ha sido verificada. Puedes iniciar sesión.';
            $tipo_mensaje = 'success';
        } else {
            $update = $conn->prepare("UPDATE usuarios SET activo = 1, verificado = 1, fecha_verificacion = NOW() WHERE id = ?");
            $update->execute([$usuario['id']]);
            
            $mensaje = '¡Cuenta verificada exitosamente! Ahora puedes iniciar sesión.';
            $tipo_mensaje = 'success';
        }
    }
} catch(PDOException $e) {
    $mensaje = 'Error en la base de datos: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Cuenta - G.O.O.D</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(139,105,20,0.3);
            border-radius: 20px;
            color: white;
        }
        .btn-gold {
            background: linear-gradient(135deg, #8B6914, #A67C1E);
            border: none;
            color: white;
            padding: 10px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-gold:hover { color: white; opacity: 0.9; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-5 text-center">
                <h1 class="mb-4" style="color: #D4AF37;">G.O.O.D</h1>
                
                <?php if($tipo_mensaje == 'success'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
                    </div>
                    <a href="login.html" class="btn-gold mt-3">Iniciar Sesión</a>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $mensaje; ?>
                    </div>
                    <a href="registro_comprador.html" class="btn-gold mt-3">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>