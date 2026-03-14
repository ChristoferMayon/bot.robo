<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Não autorizado.']);
    exit;
}

$id = $_POST['id'] ?? null;
$numero = $_POST['numero'] ?? null;
$newStatus = $_POST['status'] ?? 'ativo'; // 'pausado' or 'ativo'

if (!$id || !$numero) {
    echo json_encode(['status' => 'error', 'message' => 'ID e Número são obrigatórios.']);
    exit;
}

try {
    // 1. Update Database
    $stmt = $pdo->prepare("UPDATE mensagens_enviadas SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);

    // 2. Notify Node.js API
    $isPaused = ($newStatus === 'pausado');
    $payload = json_encode(['number' => $numero, 'pause' => $isPaused]);
    
    // Pega a URL do Railway nas variáveis de ambiente. Se não achar, usa o localhost para testes no PC.
    $botApiUrl = getenv('BOT_API_URL') ?: 'http://127.0.0.1:3000';
    
    // Monta a URL garantindo que não terá erro de barras duplicadas
    $apiUrl = rtrim($botApiUrl, '/') . '/toggle-pause';
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Logging para arquivo local para depuração
    $logMsg = date('[Y-m-d H:i:s]') . " TogglePause ID: $id | Number: $numero | Status: $newStatus | HTTP: $httpCode | Error: $error | Res: $response\n";
    file_put_contents(__DIR__ . '/debug_toggle.log', $logMsg, FILE_APPEND);

    $msg = $isPaused ? "Robô pausado para este número." : "Robô retomado para este número.";
    echo json_encode(['status' => 'success', 'message' => $msg, 'debug' => $response]);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug_toggle.log', date('[Y-m-d H:i:s]') . " Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
