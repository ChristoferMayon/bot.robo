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
    $ch = curl_init('http://localhost:3000/delete-track');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);

    echo json_encode(['status' => 'success', 'message' => 'Ordem excluída e rastreio removido do robô.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
