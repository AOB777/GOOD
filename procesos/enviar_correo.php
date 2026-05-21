<?php
// Cargar PHPMailer
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoVerificacion($email, $nombre, $token) {
    // === CONFIGURACIÓN - CAMBIA ESTOS VALORES ===
$tu_email = 'braianmercado342@gmail.com';        // CAMBIA: Tu email de Gmail
$tu_password = 'rpbo djgc nvwo qqbv';        // CAMBIA: La contraseña de aplicación
    
    // === URL BASE CORRECTA DE TU PROYECTO ===
    $url_base = 'http://localhost/PPZII/Proyecto/GOOD/';
    $enlace = $url_base . 'verificar_cuenta.php?token=' . $token;
    
    $mail = new PHPMailer(true);
    
    try {
        // Configurar SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $tu_email;
        $mail->Password   = $tu_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom($tu_email, 'G.O.O.D Luxury Delivery');
        $mail->addAddress($email, $nombre);
        
        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Verifica tu cuenta - G.O.O.D';
        
        // Cuerpo HTML del correo
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #8B6914, #A67C1E); color: white; padding: 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 30px; }
                .content p { line-height: 1.6; color: #333; }
                .button { background: #8B6914; color: white !important; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
                .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                .enlace { background: #f5f5f5; padding: 10px; word-break: break-all; font-family: monospace; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>G.O.O.D</h1>
                    <p>Luxury Delivery Service</p>
                </div>
                <div class="content">
                    <h2>¡Bienvenido, ' . htmlspecialchars($nombre) . '!</h2>
                    <p>Gracias por registrarte en <strong>G.O.O.D</strong>.</p>
                    <p>Para verificar tu cuenta, haz clic en el botón:</p>
                    <p style="text-align: center;">
                        <a href="' . $enlace . '" class="button">✓ VERIFICAR MI CUENTA</a>
                    </p>
                    <p>O copia este enlace en tu navegador:</p>
                    <div class="enlace">' . $enlace . '</div>
                    <p><strong>⚠️ Este enlace expirará en 24 horas.</strong></p>
                    <p>Si no solicitaste este registro, ignora este mensaje.</p>
                </div>
                <div class="footer">
                    <p>&copy; 2025 G.O.O.D - Luxury Delivery Service</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        // Texto plano
        $mail->AltBody = "Bienvenido $nombre,\n\n";
        $mail->AltBody .= "Gracias por registrarte en G.O.O.D.\n\n";
        $mail->AltBody .= "Para verificar tu cuenta, copia este enlace en tu navegador:\n$enlace\n\n";
        $mail->AltBody .= "Este enlace expirará en 24 horas.\n\n";
        $mail->AltBody .= "Saludos,\nEquipo G.O.O.D";
        
        $mail->send();
        return ['success' => true, 'message' => 'Correo enviado correctamente'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al enviar correo: ' . $mail->ErrorInfo];
    }
}
?>