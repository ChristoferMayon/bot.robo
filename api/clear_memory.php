<?php
require_once '../config.php';

// Check if user is Admin (ID 1 is now the default for admin)
if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_id'] !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Desculpe, apenas o administrador pode realizar a limpeza total do banco de dados. Sessão ID: ' . ($_SESSION['user_id'] ?? 'N/A')]);
    exit;
}

try {
    // 1. Clear Messages from Database
    $pdo->exec("DELETE FROM mensagens_enviadas");

    // 2. Notify Node.js API to clear all track links
    $ch = curl_init(BOT_API_URL . '/clear-all');
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
