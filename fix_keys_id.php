<?php
require 'config.php';
try {
    // 1. Atualiza todas as chaves órfãs (user_id 1) para o novo Admin (user_id 2)
    $stmt = $pdo->prepare("UPDATE api_keys SET user_id = 2 WHERE user_id = 1");
    $stmt->execute();
    $affected = $stmt->rowCount();
    
    // 2. Se não houver nenhuma chave, cria uma nova para o Admin
    $count = $pdo->query("SELECT COUNT(*) FROM api_keys WHERE user_id = 2")->fetchColumn();
    if ($count == 0) {
        $key = 'botzp_' . bin2hex(random_bytes(16));
        $pdo->prepare("INSERT INTO api_keys (user_id, nome, chave) VALUES (2, 'Painel Admin', ?)")->execute([$key]);
        echo "Nova chave criada para o Admin (ID 2).\n";
    } else {
        echo "Chaves vinculadas ao Admin (ID 2): $affected alteradas.\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
