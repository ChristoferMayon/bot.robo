<?php
require_once 'config.php';

// Limpa a tabela atual
$pdo->exec("DELETE FROM imagens_salvas");

$uploadDir = 'uploads/';
$files = scandir($uploadDir);
$count = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    $path = $uploadDir . $file;
    if (is_file($path)) {
        $stmtInsert = $pdo->prepare("INSERT INTO imagens_salvas (caminho) VALUES (?)");
        $stmtInsert->execute([$path]);
        echo "Registrado: $file\n";
        $count++;
    }
}

echo "Total de fotos na galeria: $count\n";
?>
