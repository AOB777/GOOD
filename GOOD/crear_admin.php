<?php
// crear_admin.php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'good';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Contraseña: admin123
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Verificar si ya existe
    $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->execute(['admin@good.com']);
    
    if ($check->fetch()) {
        // Actualizar contraseña
        $sql = "UPDATE usuarios SET password = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$password_hash, 'admin@good.com']);
        echo "✅ Usuario admin actualizado!<br>";
    } else {
        // Insertar nuevo
        $sql = "INSERT INTO usuarios (email, password, rol, activo, nombre, apellido) VALUES (?, ?, 'admin', 1, 'Administrador', 'Sistema')";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['admin@good.com', $password_hash]);
        echo "✅ Usuario admin creado!<br>";
    }
    
    echo "<br>📋 Credenciales:<br>";
    echo "Email: admin@good.com<br>";
    echo "Contraseña: admin123<br>";
    
    // Mostrar todos los usuarios
    echo "<br>📊 Usuarios registrados:<br>";
    $stmt = $conn->query("SELECT id, email, rol, activo FROM usuarios");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['email'] . " | Rol: " . $row['rol'] . " | Activo: " . ($row['activo'] ? 'Sí' : 'No') . "<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>