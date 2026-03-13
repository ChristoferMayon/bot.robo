<?php
require 'config.php';
$apiKey = "botzp_d9f5a062b7f3085afc2dc6ea92d09a2d"; // Chave encontrada no DB
$url = BOT_API_URL . "/api/stats";

echo "Testando URL: $url\n";
echo "Usando Chave: $apiKey\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Resposta: $response\n";
?>
