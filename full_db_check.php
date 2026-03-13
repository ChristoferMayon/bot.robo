<?php
require_once 'config.php';
try {
    // Listar tabelas para conferir nomes
    $stmt = $pdo->query("SHOW TABLES");
    echo "Tabelas no banco:\n";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "\n";
    }

    // Listar usuários
    $stmtUser = $pdo->query("SELECT * FROM usuarios");
    echo "\nUsuários:\n";
    while ($row = $stmtUser->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }

    // Listar chaves
    $stmtKey = $pdo->query("SELECT * FROM api_keys");
    echo "\nChaves de API:\n";
    while ($row = $stmtKey->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
