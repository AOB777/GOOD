<?php
echo "<h1>🔍 Diagnóstico de Ruta</h1>";
echo "<p><strong>Ruta del archivo:</strong> " . __DIR__ . "</p>";
echo "<p><strong>URL actual:</strong> http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";
echo "<h2>Archivos en esta carpeta:</h2>";
echo "<ul>";
$archivos = scandir(__DIR__);
foreach($archivos as $archivo) {
    if($archivo != '.' && $archivo != '..') {
        echo "<li>$archivo</li>";
    }
}
echo "</ul>";
?>