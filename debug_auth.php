<?php
require_once 'config.php';
header('Content-Type: application/json');
try {
    $stmt = $pdo->query("SELECT * FROM api_keys");
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt2 = $pdo->query("SELECT id, usuario FROM usuarios");
    $users = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'session_user_id' => $_SESSION['user_id'] ?? 'NÃO LOGADO',
        'api_keys' => $keys,
        'usuarios' => $users
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
