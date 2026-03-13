<?php
require 'config.php';

$keyToCheck = 'botzp_7e39feec579b3b54c99b55c5038ab35c';

echo "--- Auditoria de Webhook ---\n";

// 1. Checar API Key
$stmt = $pdo->prepare("SELECT * FROM api_keys WHERE chave = ?");
$stmt->execute([$keyToCheck]);
$apiKeyData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$apiKeyData) {
    echo "ERRO: API Key não encontrada no banco de dados.\n";
} else {
    echo "API Key ID: " . $apiKeyData['id'] . "\n";
    echo "Vínculo de Usuário (user_id): " . ($apiKeyData['user_id'] ?? 'NULL') . "\n";
    
    if ($apiKeyData['user_id']) {
        // 2. Checar Configuração de Webhook para este Usuário
        $stmtConf = $pdo->prepare("SELECT * FROM user_configs WHERE user_id = ?");
        $stmtConf->execute([$apiKeyData['user_id']]);
        $confData = $stmtConf->fetch(PDO::FETCH_ASSOC);
        
        if (!$confData) {
            echo "AVISO: Nenhuma configuração de Webhook encontrada para este Usuário ID.\n";
        } else {
            echo "Webhook URL: " . ($confData['webhook_url'] ?? 'Vazio') . "\n";
        }
    } else {
        echo "AVISO: Esta chave não está vinculada a nenhum usuário. O Webhook não funcionará.\n";
        
        // Tentar encontrar um usuário e vincular a chave para o teste
        $stmtFirstUser = $pdo->query("SELECT id FROM usuarios LIMIT 1");
        $firstUserId = $stmtFirstUser->fetchColumn();
        if ($firstUserId) {
            echo "VINCULANDO CHAVE AO USUÁRIO $firstUserId PARA TESTE...\n";
            $pdo->prepare("UPDATE api_keys SET user_id = ? WHERE chave = ?")->execute([$firstUserId, $keyToCheck]);
            echo "Vínculo atualizado.\n";
        }
    }
}

echo "--- Fim da Auditoria ---\n";
?>
