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
    
    $ch = curl_init('http://localhost:3000/toggle-pause');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $msg = $isPaused ? "Robô pausado para este número." : "Robô retomado para este número.";
    echo json_encode(['status' => 'success', 'message' => $msg]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
