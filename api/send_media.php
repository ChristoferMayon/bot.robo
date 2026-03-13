<?php
require_once '../config.php';

// Check if user is logged in
// No-login mode as per previous request, checkAuth is implicit or bypassable
// if (!isset($_SESSION['user_id'])) { ... }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Apenas requisições POST são permitidas.']);
    exit;
}

try {
    // Params
    $sessionId  = trim($_POST['sessionId'] ?? 'default');
    $numero     = trim($_POST['numero'] ?? '');
    $linkRastreio = trim($_POST['link_rastreio'] ?? '');
    $modelo     = trim($_POST['modelo'] ?? '');
    $cor        = trim($_POST['cor'] ?? '');
    $capacidade = trim($_POST['capacidade'] ?? '');
    $textoBase  = trim($_POST['texto_base'] ?? '');
    $imageType  = trim($_POST['image_type'] ?? 'gallery'); 
    $idioma     = trim($_POST['idioma'] ?? 'pt'); 

    if (empty($numero) || empty($linkRastreio) || empty($modelo) || empty($cor) || empty($capacidade) || empty($textoBase)) {
        throw new Exception("Todos os campos obrigatórios (Número, Link, Modelo, Capacidade, Cor e Texto) devem ser preenchidos.");
    }   

    // Prepare Final Text
    $textoFinal = str_replace(['{modelo}', '{cor}', '{capacidade}', '{numero}'], [$modelo, $cor, $capacidade, $numero], $textoBase);

    $imagePathForDB = ''; 

    // Handle Local Gallery
    if ($imageType === 'gallery') {
        $imagePathForDB = $_POST['image_gallery'] ?? '';
        if (empty($imagePathForDB) || !file_exists('../' . $imagePathForDB)) {
            throw new Exception("Imagem da galeria selecionada não existe ou inválida.");
        }
    } else {
        throw new Exception("Tipo de envio de imagem não reconhecido para o painel simplificado.");
    }
    
    // --- API REQUEST para Node.js (v2 Multi-Device) ---
    // Buscar uma chave de API válida para o usuário logado
    $user_id = $_SESSION['user_id'] ?? 2; // Fallback para admin se não estiver na sessão
    $stmtKey = $pdo->prepare("SELECT chave FROM api_keys WHERE user_id = ? LIMIT 1");
    $stmtKey->execute([$user_id]);
    $userKeyRow = $stmtKey->fetch();
    $apiKey = $userKeyRow ? $userKeyRow['chave'] : '';

    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
    $publicMediaPath = $baseUrl . (str_starts_with($imagePathForDB, '/') ? '' : '/') . $imagePathForDB;

    $payload = json_encode([
        'sessionId' => $sessionId,
        'number' => $numero,
        'message' => $textoFinal,
        'trackLink' => $linkRastreio,
        'language' => $idioma,
        'mediaPath' => $publicMediaPath
    ]);

    $base_node_url = BOT_API_URL;
    $node_endpoint = rtrim($base_node_url, '/') . '/api/send';

    $ch = curl_init($node_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    $headers = [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ];

    if ($apiKey) {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $nodeResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $apiSuccess = false;
    $errorMessageAPI = "Falha de comunicação com Bot Node.js (Porta 3000 offline).";

    if ($httpCode === 200 && $nodeResponse !== false) {
        $resObj = json_decode($nodeResponse, true);
        if ($resObj && isset($resObj['success']) && $resObj['success'] === true) {
             $apiSuccess = true;
        } else {
             $errorMessageAPI = $resObj['error'] ?? 'Erro retornado pela API Node.';
        }
    } else if ($httpCode === 403) {
        $errorMessageAPI = "O dispositivo selecionado ($sessionId) não está conectado.";
    }

    if ($apiSuccess) {
         // Log to database
         $stmtLog = $pdo->prepare("INSERT INTO mensagens_enviadas (user_id, numero, modelo, capacidade, cor, tipo_imagem, caminho_link, link_rastreio, texto_final, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
         $stmtLog->execute([
            $user_id, $numero, $modelo, $capacidade, $cor, 'local', $imagePathForDB, $linkRastreio, $textoFinal, 'ativo'
         ]);
        
         echo json_encode([
            'status' => 'success', 
            'message' => 'Disparo efetuado com sucesso via Aparelho: ' . $sessionId
         ]);
    } else {
         throw new Exception($errorMessageAPI);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
