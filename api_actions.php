<?php
require_once 'config.php';
checkAuth();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    if ($action === 'generate_key') {
        $nome = trim($_POST['nome'] ?? 'Nova Chave');
        $novaChave = 'botzp_' . bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO api_keys (user_id, nome, chave) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $nome, $novaChave]);
        echo json_encode(['success' => true, 'key' => $novaChave]);
    } 
    elseif ($action === 'revoke_key') {
        $key_id = $_POST['id'] ?? null;
        if (!$key_id) throw new Exception("ID da chave obrigatório.");
        $stmt = $pdo->prepare("DELETE FROM api_keys WHERE id = ? AND user_id = ?");
        $stmt->execute([$key_id, $user_id]);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'save_webhook') {
        $url = trim($_POST['url'] ?? '');
        $stmt = $pdo->prepare("INSERT INTO user_configs (user_id, webhook_url) VALUES (?, ?) ON DUPLICATE KEY UPDATE webhook_url = ?");
        $stmt->execute([$user_id, $url, $url]);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'get_data') {
        $stmtKeys = $pdo->prepare("SELECT id, nome, chave, created_at FROM api_keys WHERE user_id = ?");
        $stmtKeys->execute([$user_id]);
        $keys = $stmtKeys->fetchAll(PDO::FETCH_ASSOC);

        $stmtConf = $pdo->prepare("SELECT webhook_url FROM user_configs WHERE user_id = ?");
        $stmtConf->execute([$user_id]);
        $conf = $stmtConf->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['keys' => $keys, 'config' => $conf]);
    }
    else {
        throw new Exception("Ação inválida.");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
