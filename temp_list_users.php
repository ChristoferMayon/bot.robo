<?php
require 'config.php';
try {
    $stmt = $pdo->query("SELECT id, username FROM usuarios");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
