<?php
// api/proxy.php - Ponte entre Frontend e o Bot Node.js
session_start();
require_once '../config.php';

// Segurança: Apenas quem está logado pode usar o proxy
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$path = $_GET['path'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// CORREÇÃO PARA O RAILWAY:
// Busca a URL do Node centralizada no config.php
$base_node_url = BOT_API_URL;

// Garante formatação correta da URL (evita barras duplas ou falta de barras)
$base_node_url = rtrim($base_node_url, '/');
if (!str_starts_with($path, '/')) {
    $path = '/' . $path;
}

$node_url = $base_node_url . $path;

$ch = curl_init($node_url);

// Configuração básica do cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// Se for POST/PUT/DELETE, pega o corpo da requisição do PHP
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    $data = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
}

// Passa os headers necessários (incluindo JSON)
$headers = [
    'Content-Type: application/json'
];

// Fallback de segurança: getallheaders() às vezes não existe em produção dependendo do servidor
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

$request_headers = getallheaders();
if (isset($request_headers['Authorization'])) {
    $headers[] = 'Authorization: ' . $request_headers['Authorization'];
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    // Fallback extra seguro para Nginx/Apache
    $headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Prevenção de quebra: Se o cURL falhar (ex: bot offline), avisa em vez de travar o painel
if ($response === false) {
    http_response_code(502); // Bad Gateway
    echo json_encode(['error' => 'Falha ao conectar com o Bot (Node.js).', 'details' => $curl_error, 'url_tentada' => $node_url]);
    exit;
}

http_response_code($http_code);
header('Content-Type: application/json');
echo $response;
?>
