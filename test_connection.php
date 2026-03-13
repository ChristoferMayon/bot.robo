<?php
require_once 'config.php';
header('Content-Type: text/plain');

echo "--- TESTE DE CONEXÃO PHP -> NODE ---\n";
echo "Configuração BOT_API_URL: " . BOT_API_URL . "\n";

$ch = curl_init(BOT_API_URL . '/api/stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response !== false && $http_code === 200) {
    echo "SUCESSO: O PHP conseguiu se conectar ao Bot Node.js!\n";
    echo "Resposta: " . $response . "\n";
} else {
    echo "ERRO: O PHP não conseguiu se conectar ao Bot.\n";
    echo "HTTP Code: " . $http_code . "\n";
    echo "Erro cURL: " . $curl_error . "\n";
    
    echo "\nTentando via 127.0.0.1:3000...\n";
    $ch2 = curl_init('http://127.0.0.1:3000/api/stats');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
    $response2 = curl_exec($ch2);
    $http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    if ($response2 !== false) {
        echo "SUCESSO via 127.0.0.1!\n";
    } else {
        echo "FALHA via 127.0.0.1 também.\n";
    }
}
?>
