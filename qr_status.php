<?php
require_once 'config.php';
checkAuth();

// Função para fazer requisição interna ao Node.js
function callNode($path) {
    $url = "http://localhost:3000" . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Se for requisição AJAX do status
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo callNode('/status');
    exit;
}

// Se for requisição AJAX do QR
if (isset($_GET['qr_ajax'])) {
    header('Content-Type: application/json');
    echo callNode('/qr');
    exit;
}

// Ação de Logout
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    $ch = curl_init("http://localhost:3000/logout");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_exec($ch);
    curl_close($ch);
    header('Location: qr_status.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conectar WhatsApp - Botzap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .qr-container { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; margin-top: 50px; }
        #qr-image { max-width: 300px; margin: 20px auto; display: block; }
        .status-badge { font-size: 1.1rem; padding: 10px 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="qr-container">
                <h2 class="mb-4"><i class="fab fa-whatsapp text-success"></i> Conexão WhatsApp</h2>
                
                <div id="status-area" class="mb-4">
                    <span id="status-badge" class="badge bg-secondary status-badge">Verificando...</span>
                </div>

                <div id="qr-area" style="display:none;">
                    <p class="text-muted">Escaneie o QR Code abaixo com seu WhatsApp:</p>
                    <img id="qr-image" src="" alt="QR Code">
                    <p class="small text-danger"><i class="fas fa-sync fa-spin"></i> Atualizando automaticamente...</p>
                </div>

                <div id="connected-area" style="display:none;">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h4>WhatsApp Conectado!</h4>
                        <p>O robô já está pronto para realizar os disparos.</p>
                    </div>
                </div>

                <div id="disconnected-area" style="display:none;">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Servidor desconectado. Aguardando QR Code...
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Voltar ao Painel</a>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Isso removerá a conexão atual. Tem certeza?')">
                            <i class="fas fa-sign-out-alt"></i> Desconectar/Limpar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function updateStatus() {
        $.getJSON('qr_status.php?ajax=1', function(data) {
            const status = data.status;
            const badge = $('#status-badge');
            
            $('#qr-area, #connected-area, #disconnected-area').hide();

            if (status === 'connected') {
                badge.text('CONECTADO').removeClass().addClass('badge bg-success status-badge');
                $('#connected-area').show();
            } else if (status === 'qr_ready') {
                badge.text('AGUARDANDO LEITURA').removeClass().addClass('badge bg-warning status-badge');
                $('#qr-area').show();
                fetchQR();
            } else {
                badge.text('DESCONECTADO').removeClass().addClass('badge bg-danger status-badge');
                $('#disconnected-area').show();
            }
        });
    }

    function fetchQR() {
        $.getJSON('qr_status.php?qr_ajax=1', function(data) {
            if (data.success && data.base64) {
                $('#qr-image').attr('src', data.base64);
            }
        });
    }

    // Atualiza a cada 3 segundos
    setInterval(updateStatus, 3000);
    updateStatus();
</script>

</body>
</html>
