<?php
require 'config.php';
checkAuth(); // Garante que apenas logados vejam (Christofer admin)

if ((int)$_SESSION['user_id'] !== 2) {
    die("Acesso negado.");
}

try {
    echo "<h2>Diagnóstico de Chaves de API (Railway)</h2>";
    echo "Sua Sessão ID: " . $_SESSION['user_id'] . "<br><br>";
    
    $stmt = $pdo->query("SELECT id, user_id, nome, chave FROM api_keys");
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($keys)) {
        echo "<b style='color:red;'>ALERTA: Nenhuma chave de API encontrada na tabela api_keys!</b>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Nome</th><th>Chave (Início)</th></tr>";
        foreach ($keys as $k) {
            $masked = substr($k['chave'], 0, 10) . "********";
            echo "<tr><td>{$k['id']}</td><td>{$k['user_id']}</td><td>{$k['nome']}</td><td>{$masked}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
