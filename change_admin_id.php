<?php
require 'config.php';

echo "<h2>🔧 Alteração de ID de Administrador (ID 1 -> ID 2)</h2>";

try {
    $pdo->beginTransaction();

    // 1. Verifica se o ID 1 já está ocupado
    $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE id = 1");
    $stmt->execute();
    $existingUser = $stmt->fetchColumn();

    if ($existingUser && $existingUser !== 'admin') {
        echo "⚠️ O ID 1 está ocupado por outro usuário ($existingUser). Deletando para dar lugar ao admin...<br>";
        $pdo->exec("DELETE FROM usuarios WHERE id = 1");
    }

    // 2. Altera o ID do admin de 1 para 2
    $stmtUpdate = $pdo->prepare("UPDATE usuarios SET id = 2 WHERE id = 1 AND username = 'admin'");
    $stmtUpdate->execute();
    
    if ($stmtUpdate->rowCount() > 0) {
        echo "✅ Sucesso: O usuário <b>admin</b> agora tem o <b>ID 2</b>.<br>";
    } else {
        // Verifica se já é 1
        $checkId1 = $pdo->query("SELECT id FROM usuarios WHERE username = 'admin' AND id = 1")->fetch();
        if ($checkId1) {
            echo "ℹ️ O usuário admin já possui o ID 1.<br>";
        } else {
            echo "❌ Erro: Não foi possível encontrar o usuário admin com ID 2 para atualizar.<br>";
        }
    }

    // 3. Sincroniza as chaves de API para garantir que pertençam ao ID 2
    $pdo->exec("UPDATE api_keys SET user_id = 2 WHERE user_id = 1");
    echo "✅ Chaves de API sincronizadas com o novo ID 2.<br>";

    $pdo->commit();
    echo "<br><h3 style='color:green;'>Procedimento concluído! Por favor, faça login novamente.</h3>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<b style='color:red;'>Erro: " . $e->getMessage() . "</b>";
}
?>
