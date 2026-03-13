<?php
// trigger_webhook_session.php
require 'config.php';

$stmt = $pdo->query("SELECT chave FROM api_keys LIMIT 1");
$key = $stmt->fetchColumn();

if (!$key) {
    die("Nenhuma chave API encontrada.\n");
}

$payload = json_encode(['session' => 'MInha']);
$ch = curl_init('http://localhost:3000/api/create-session');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $key
]);

$res = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "--- Reinicializando Sessão 'MInha' via API ---\n";
echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Response: $res\n";
echo "--------------------------------------------\n";
?>
