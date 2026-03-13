<?php
require 'config.php';

echo "<h2>🔧 Alteração de ID de Administrador (ID 1 → ID 2)</h2>";

try {
    $pdo->beginTransaction();

    // 1. Verifica se já existe alguém com ID 2
    $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE id = 2");
    $stmt->execute();
    $existingUser = $stmt->fetchColumn();

    if ($existingUser && $existingUser !== 'admin') {
        echo "⚠️ O ID 2 está ocupado por outro usuário ($existingUser). Removendo para dar lugar ao admin...<br>";
        $pdo->exec("DELETE FROM usuarios WHERE id = 2");
    }

    // 2. Atualiza o ID do admin de 1 para 2
    $stmtUpdate = $pdo->prepare("UPDATE usuarios SET id = 2 WHERE id = 1 AND username = 'admin'");
    $stmtUpdate->execute();

    if ($stmtUpdate->rowCount() > 0) {
        echo "✅ Sucesso: O usuário <b>admin</b> agora possui o <b>ID 2</b>.<br>";
    } else {

        // Verifica se o admin já está no ID 2
        $checkAdmin = $pdo->query("SELECT id FROM usuarios WHERE username = 'admin' AND id = 2")->fetch();

        if ($checkAdmin) {
            echo "ℹ️ O usuário <b>admin</b> já possui o ID 2.<br>";
        } else {
            echo "❌ Erro: Não foi possível encontrar o usuário admin com ID 1 para atualizar.<br>";
        }
    }

    // 3. Atualiza as chaves de API
    $pdo->exec("UPDATE api_keys SET user_id = 2 WHERE user_id = 1");
    echo "✅ Chaves de API sincronizadas para o ID 2.<br>";

    $pdo->commit();

    echo "<br><h3 style='color:green;'>Procedimento concluído! Faça login novamente.</h3>";

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<b style='color:red;'>Erro: " . $e->getMessage() . "</b>";
}
?>
