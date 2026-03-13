<?php
require 'config.php';

echo "<h2>🔧 Diagnóstico e Correção de Banco de Dados</h2>";
echo "Host: " . (getenv('MYSQLHOST') ?: 'localhost') . "<br>";
echo "Banco: " . (getenv('MYSQLDATABASE') ?: 'botzap') . "<br><hr>";

try {
    // 1. Verificar colunas existentes
    $stmt = $pdo->query("DESCRIBE mensagens_enviadas");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<b>Colunas atuais:</b> " . implode(', ', $columns) . "<br><br>";

    if (!in_array('user_id', $columns)) {
        echo "🟡 Coluna 'user_id' NÃO encontrada. Tentando criar...<br>";
        
        // Tentativa de ALTER TABLE
        $sql = "ALTER TABLE mensagens_enviadas ADD COLUMN user_id INT(11) NULL AFTER id";
        $pdo->exec($sql);
        
        echo "<b style='color:green;'>✅ SUCESSO! Coluna 'user_id' adicionada com êxito.</b><br>";
    } else {
        echo "<b style='color:green;'>✅ A coluna 'user_id' JÁ EXISTE no banco de dados.</b><br>";
    }

    echo "<br><hr>";
    echo "<b>Verificação Final:</b><br>";
    $stmt2 = $pdo->query("DESCRIBE mensagens_enviadas");
    $cols2 = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    echo "Estado Final: " . implode(', ', $cols2) . "<br>";

    if (in_array('user_id', $cols2)) {
        echo "<h3 style='color:green;'>Tudo certo! Pode tentar enviar novamente agora.</h3>";
    } else {
        echo "<h3 style='color:red;'>ALERTA: A coluna ainda não aparece. Verifique as permissões do DB.</h3>";
    }

} catch (Exception $e) {
    echo "<div style='color:red; padding: 20px; border: 2px solid red;'>";
    echo "<h3>❌ ERRO CRÍTICO</h3>";
    echo "Mensagem: " . $e->getMessage();
    echo "</div>";
}
?>
