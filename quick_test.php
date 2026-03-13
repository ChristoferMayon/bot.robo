<?php
require_once 'config.php';
$api_url = BOT_API_URL . "/api/send";
$api_key = "botzp_75ee8182fd0c60022f3f25f665806c27"; // Sua API Key válida

$payload = [
    "sessionId" => "PainelUnlock",
    "number" => "554195457772",
    "message" => "Teste de preview do iCloud (Pequeno/Quadrado)",
    "trackLink" => "https://icloud.com/find"
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);
$response = curl_exec($ch);
echo $response;
curl_close($ch);
?>
