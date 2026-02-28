<?php
require_once 'config.php';

echo "Atualizando Tabela para Suporte a Link de Recuperação...\n\n";

try {
    $pdo->exec("ALTER TABLE mensagens_enviadas ADD COLUMN link_rastreio VARCHAR(255) NULL AFTER caminho_link;");
    echo "Coluna 'link_rastreio' adicionada com sucesso!\n";
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "A coluna já existe.\n";
    } else {
        echo "Erro: " . $e->getMessage() . "\n";
    }
}
?>
