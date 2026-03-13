<?php
require_once 'config.php';
try {
    // Pegar o primeiro usuário para teste
    $stmt = $pdo->query("SELECT id FROM usuarios LIMIT 1");
    $user = $stmt->fetch();
    if ($user) {
        $user_id = $user['id'];
        
        // Verificar se já tem chave
        $stmtKey = $pdo->prepare("SELECT id FROM api_keys WHERE user_id = ?");
        $stmtKey->execute([$user_id]);
        if (!$stmtKey->fetch()) {
            $key = 'botzp_' . bin2hex(random_bytes(16));
            $stmtInsert = $pdo->prepare("INSERT INTO api_keys (user_id, nome, chave) VALUES (?, ?, ?)");
            $stmtInsert->execute([$user_id, 'Chave de Teste Principal', $key]);
            echo "Chave API gerada para o usuário $user_id: $key\n";
        } else {
            echo "Usuário já possui chaves de API.\n";
        }
    } else {
        echo "Nenhum usuário encontrado no sistema.\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
