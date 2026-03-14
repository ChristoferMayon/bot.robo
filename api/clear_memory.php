<?php
// 🔴 CORREÇÃO CRÍTICA: Iniciar a sessão para ler o ID do usuário
session_start(); 
require_once '../config.php';

// Check if user is Admin (ID 2 is the default for admin)
if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] !== 2) {
    echo json_encode(['status' => 'error', 'message' => 'Desculpe, apenas o administrador pode realizar a limpeza total do banco de dados. Sessão ID: ' . ($_SESSION['user_id'] ?? 'N/A')]);
    exit;
}

try {
    // 1. Clear Messages from Database
    $pdo->exec("DELETE FROM mensagens_enviadas");

    // 2. Notify Node.js API to clear all track links
    // 🟢 PADRONIZAÇÃO: Buscando a URL de forma segura para evitar erro de barras (//)
    $botApiUrl = defined('BOT_API_URL') ? BOT_API_URL : (getenv('BOT_API_URL') ?: 'http://127.0.0.1:3000');
    $apiUrl = rtrim($botApiUrl, '/') . '/clear-all';
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo json_encode(['status' => 'success', 'message' => 'Memória do robô e histórico limpos com sucesso!']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
