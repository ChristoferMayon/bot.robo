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

if (!$id || !$numero) {
    echo json_encode(['status' => 'error', 'message' => 'ID e Número são obrigatórios.']);
    exit;
}

try {
    // 1. Delete from Database
    $stmt = $pdo->prepare("DELETE FROM mensagens_enviadas WHERE id = ?");
    $stmt->execute([$id]);

    // 2. Notify Node.js API to clear memory
    $payload = json_encode(['number' => $numero]);
    
    // --- CORREÇÃO: Puxando a URL do Railway ---
    $botApiUrl = getenv('BOT_API_URL') ?: 'http://127.0.0.1:3000';
    $apiUrl = rtrim($botApiUrl, '/') . '/delete-track';
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Opcional: Você pode adicionar um log aqui se quiser monitorar os deletes no futuro
    // file_put_contents(__DIR__ . '/debug_delete.log', date('[Y-m-d H:i:s]') . " Delete ID: $id | Number: $numero | HTTP: $httpCode | Res: $response\n", FILE_APPEND);

    echo json_encode(['status' => 'success', 'message' => 'Ordem excluída e rastreio removido do robô.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
