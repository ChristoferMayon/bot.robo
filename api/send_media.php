<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Não autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Apenas requisições POST são permitidas.']);
    exit;
}

// Params
    $numero     = trim($_POST['numero'] ?? '');
    $linkRastreio = trim($_POST['link_rastreio'] ?? '');
    $modelo     = trim($_POST['modelo'] ?? '');
    $cor        = trim($_POST['cor'] ?? '');
    $capacidade = trim($_POST['capacidade'] ?? '');
    $textoBase  = trim($_POST['texto_base'] ?? '');
    $imageType  = trim($_POST['image_type'] ?? 'url'); // url, upload, gallery
    $idioma     = trim($_POST['idioma'] ?? 'pt'); // pt, en, es

    if (empty($numero) || empty($linkRastreio) || empty($modelo) || empty($cor) || empty($capacidade) || empty($textoBase)) {
        throw new Exception("Todos os campos obrigatórios (Número, Link, Modelo, Capacidade, Cor e Texto) devem ser preenchidos.");
    }   

    // 3. Prepare Final Text
    $textoFinal = str_replace(['{modelo}', '{cor}', '{capacidade}', '{numero}'], [$modelo, $cor, $capacidade, $numero], $textoBase);

$finalImageUrl = ''; // The URL that will be sent to the API
$imagePathForDB = ''; // What we store in the DB (local path or URL)
$newSavedImage = false;

try {
    // 1. Handle URL
    if ($imageType === 'url') {
        $url = $_POST['image_url'] ?? '';
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new Exception("A URL da imagem fornecida é inválida.");
        }
        
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            throw new Exception("A URL deve terminar em .jpg, .jpeg ou .png");
        }
        
        $finalImageUrl = $url;
        $imagePathForDB = $url;
    }
    // 2. Handle Upload
    else if ($imageType === 'upload') {
        if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception("Nenhum arquivo enviado.");
        }
        
        $file = $_FILES['image_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erro durante o upload: " . $file['error']);
        }
        
        // 5MB limit
        if ($file['size'] > 5242880) {
            throw new Exception("O arquivo excede o limite de 5MB.");
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        
        if (!in_array($mime_type, ['image/jpeg', 'image/png'])) {
            throw new Exception("O upload permite apenas arquivos JPG e PNG.");
        }
        
        $ext = ($mime_type === 'image/png') ? 'png' : 'jpg';
        // Renaming Rule: modelo-cor-data.jpg
        $nomeUpload = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '_', $modelo . '_' . $cor)) . '_' . time() . '.' . $ext;
        $uploadDir = '../uploads/';
        
        if (!is_dir($uploadDir)) {
            // Created automatically
            mkdir($uploadDir, 0755, true);
        }
        
        $destination = $uploadDir . $nomeUpload;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Save to DB
            $savePath = 'uploads/' . $nomeUpload;
            $stmt = $pdo->prepare("INSERT INTO imagens_salvas (caminho) VALUES (?)");
            $stmt->execute([$savePath]);
            $newSavedImage = true;
            
            // Build Public URL mapping based on base_url (simulate public)
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $domainName = $_SERVER['HTTP_HOST'];
            // Detect script directory
            $basePath = dirname(dirname($_SERVER['REQUEST_URI'])); 
            $finalImageUrl = $protocol . $domainName . $basePath . '/' . $savePath;
            
            $imagePathForDB = $savePath;
        } else {
            throw new Exception("Falha ao mover arquivo enviado para a pasta /uploads/.");
        }
    }
    // 3. Handle Local Gallery
    else if ($imageType === 'gallery') {
        $caminho = $_POST['image_gallery'] ?? '';
        if (empty($caminho) || !file_exists('../' . $caminho)) {
            throw new Exception("Imagem da galeria selecionada não existe ou inválida.");
        }
        
        // Build Public URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        $basePath = dirname(dirname($_SERVER['REQUEST_URI'])); 
        $finalImageUrl = $protocol . $domainName . $basePath . '/' . $caminho;
        
        $imagePathForDB = $caminho;
    }
    else {
        throw new Exception("Tipo de envio de imagem não reconhecido.");
    }
    
    // ---
    // MOCK API REQUEST => CHANGED TO REAL NODE.JS API POST
    // Here we implement the cURL to specific whatsapp API (localhost:3000/send)
    
    $payload = json_encode([
        'number' => $numero,
        'message' => $textoFinal,
        'trackLink' => $linkRastreio,
        'language' => $idioma,
        'mediaUrl' => ($imageType === 'url') ? $finalImageUrl : null,
        'mediaPath' => ($imageType !== 'url') ? realpath('../' . $imagePathForDB) : null
    ]);

    $ch = curl_init('http://localhost:3000/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    
    $nodeResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $apiSuccess = false;
    $errorMessageAPI = "Falha de comunicação com Bot Node.js Local.";

    if ($httpCode === 200 && $nodeResponse !== false) {
        $resObj = json_decode($nodeResponse, true);
        if ($resObj && isset($resObj['success']) && $resObj['success'] === true) {
             $apiSuccess = true;
        } else {
             $errorMessageAPI = $resObj['message'] ?? 'Erro desconhecido retornado pela API Node.';
        }
    } else if ($httpCode === 403) {
        $errorMessageAPI = "O WhatsApp não está logado no Painel. Por favor, conecte via QR Code primeiro.";
    }

    if ($apiSuccess) {
         // 4. Log to database
         $logType = ($imageType === 'url') ? 'url' : 'local';
         $stmtLog = $pdo->prepare("INSERT INTO mensagens_enviadas (numero, modelo, capacidade, cor, tipo_imagem, caminho_link, link_rastreio, texto_final) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
         $stmtLog->execute([
            $numero, $modelo, $capacidade, $cor, $logType, $imagePathForDB, $linkRastreio, $textoFinal
         ]);
        
         echo json_encode([
            'status' => 'success', 
            'message' => 'Mensagem de Mídia enviada pelo Bot e Logada com Sucesso!',
            'new_image' => $newSavedImage
         ]);
    } else {
         throw new Exception($errorMessageAPI);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
