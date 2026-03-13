<?php
require_once 'config.php';
try {
    // 1. Adicionar user_id na tabela api_keys se não existir
    $stmt = $pdo->query("DESCRIBE api_keys");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('user_id', $cols)) {
        $pdo->exec("ALTER TABLE api_keys ADD COLUMN user_id INT(11) AFTER id");
        echo "Coluna user_id adicionada em api_keys.\n";
    }

    // 2. Criar tabela de configurações do usuário (Webhooks) se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_configs (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNIQUE,
        webhook_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Tabela user_configs verificada/criada.\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
