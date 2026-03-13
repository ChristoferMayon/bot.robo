<?php
require_once 'config.php';
$stmt = $pdo->query('SELECT * FROM imagens_salvas');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
?>
