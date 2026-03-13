<?php
require 'config.php';

try {
    echo "<h3>Corrigindo Esquema da Tabela [mensagens_enviadas]</h3>";
    
    // 1. Verifica as colunas atuais
    $stmt = $pdo->query("DESCRIBE mensagens_enviadas");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('user_id', $columns)) {
        echo "Adicionando coluna 'user_id'...<br>";
        $pdo->exec("ALTER TABLE mensagens_enviadas ADD COLUMN user_id INT(11) NULL AFTER id");
        echo "<b>Sucesso: Coluna 'user_id' adicionada!</b><br>";
    } else {
        echo "A coluna 'user_id' já existe.<br>";
    }
    
} catch (Exception $e) {
    echo "<b style='color:red;'>Erro: " . $e->getMessage() . "</b>";
}
?>
