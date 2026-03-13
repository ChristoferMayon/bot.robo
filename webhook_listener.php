<?php
// webhook_listener.php - Receptor de testes do Webhook
$logFile = 'webhook_log.txt';

// Pega o corpo da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Prepara o log
$logEntry = "[" . date('Y-m-d H:i:s') . "] Webhook Recebido:\n";
$logEntry .= "JSON: " . $input . "\n";
$logEntry .= "-------------------------------------------\n";

// Salva no arquivo
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Responde ao Node.js
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'received' => true]);
?>
