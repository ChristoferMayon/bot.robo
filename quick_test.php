<?php
require_once 'config.php';
$api_url = BOT_API_URL . "/api/send";
$payload = [
    "sessionId" => "MinhaAPIKey",
    "number" => "554195457772",
    "message" => "Teste de preview do iCloud (Pequeno/Quadrado)",
    "trackLink" => "https://icloud.com/find"
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
echo $response;
curl_close($ch);
?>
