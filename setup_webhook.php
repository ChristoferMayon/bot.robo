<?php
require 'config.php';

// 1. Mostrar usuários
echo "--- Usuários ---\n";
$stmt = $pdo->query("SELECT id, username FROM usuarios");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

if (empty($users)) {
    echo "Nenhum usuário encontrado.\n";
    exit;
}

$user_id = $users[0]['id'];
$webhook_url = "http://localhost:8080/webhook_listener.php";

// 2. Configurar Webhook
echo "\n--- Configurando Webhook para User ID $user_id ---\n";
$stmt = $pdo->prepare("INSERT INTO user_configs (user_id, webhook_url) VALUES (?, ?) ON DUPLICATE KEY UPDATE webhook_url = ?");
$stmt->execute([$user_id, $webhook_url, $webhook_url]);
echo "Webhook configurado com sucesso: $webhook_url\n";

// 3. Mostrar Chaves API
echo "\n--- Chaves API ---\n";
$stmt = $pdo->prepare("SELECT id, chave FROM api_keys WHERE user_id = ?");
$stmt->execute([$user_id]);
$keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($keys);
?>
