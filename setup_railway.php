<?php
// setup_railway.php - Instalador Automático de Banco de Dados
require_once 'config.php';

// Nome do arquivo SQL
$sqlFile = 'database.sql';

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    $query = $_POST['query'] ?? '';
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Query vazia.']);
        exit;
    }

    try {
        $pdo->exec($query);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $errCode = $e->errorInfo[1] ?? 0;
        // Ignora erros de "já existente": 1050 (Tabela), 1062 (Entrada), 1060 (Coluna), 1061 (Chave), 1068 (PK Múltipla)
        $ignoredErrors = [1050, 1060, 1061, 1062, 1068];
        
        if ($e->getCode() == '42S01' || in_array($errCode, $ignoredErrors)) {
             echo json_encode(['success' => true, 'info' => 'Estrutura ou dado já existente (Ignorado).']);
        } else {
             echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    exit;
}

// 1. Garantir que as tabelas de sistema existam antes da importação principal
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `api_keys` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `nome` varchar(100) DEFAULT 'Minha Chave',
        `chave` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `chave` (`chave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_configs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `webhook_url` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) { $error = "Erro ao preparar tabelas base: " . $e->getMessage(); }

// 2. Ler e processar o arquivo SQL em comandos individuais
$commands = [];
if (file_exists($sqlFile)) {
    $content = file_get_contents($sqlFile);
    
    // Remover comentários de linha e multibloco
    $content = preg_replace('/--.*\n/', '', $content);
    $content = preg_replace('/\/\*.*?\*\//s', '', $content);
    
    // Separar comandos por ; MAS mantendo integridade de strings
    $tempCommands = preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $content);

    foreach ($tempCommands as $cmd) {
        $cmd = trim($cmd);
        if (!empty($cmd)) {
            $commands[] = $cmd . ';';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Railway Database Setup | Botzap Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0a0c10;
            --card-bg: #161b22;
            --accent: #238636;
            --accent-hover: #2ea043;
            --text-main: #e6edf3;
            --text-dim: #8b949e;
            --border-color: #30363d;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .setup-container {
            width: 100%;
            max-width: 700px;
            padding: 20px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            overflow: hidden;
        }

        .card-header {
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--border-color);
            padding: 25px;
            text-align: center;
        }

        .logo-icon {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 15px;
            filter: drop-shadow(0 0 10px rgba(35, 134, 54, 0.3));
        }

        .card-body {
            padding: 30px;
        }

        .btn-install {
            background: var(--accent);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .btn-install:hover:not(:disabled) {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 160, 67, 0.4);
        }

        .btn-install:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .log-container {
            background: #000;
            border-radius: 8px;
            padding: 15px;
            margin-top: 25px;
            height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.85rem;
            border: 1px solid #333;
            display: none;
        }

        .log-entry {
            margin-bottom: 5px;
            border-left: 2px solid transparent;
            padding-left: 10px;
        }

        .log-success { color: #57ab5a; border-left-color: #57ab5a; }
        .log-error { color: #f85149; border-left-color: #f85149; }
        .log-info { color: #58a6ff; border-left-color: #58a6ff; }

        .progress {
            height: 6px;
            background-color: #30363d;
            border-radius: 3px;
            margin-top: 15px;
            display: none;
        }

        .progress-bar {
            background-color: var(--accent);
            transition: width 0.3s ease;
        }

        .status-text {
            font-size: 0.9rem;
            color: var(--text-dim);
            text-align: center;
            margin-top: 10px;
        }

        .file-info {
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            border: 1px dashed var(--border-color);
        }

        /* Glassmorphism subtle effect */
        .glass-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at top right, rgba(35, 134, 54, 0.1), transparent);
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="setup-container">
    <div class="card position-relative">
        <div class="glass-overlay"></div>
        <div class="card-header">
            <div class="logo-icon"><i class="fas fa-database"></i></div>
            <h4 class="mb-1">Importador Railway</h4>
            <p class="text-muted small mb-0">Prepare seu ambiente na nuvem com um clique</p>
        </div>
        <div class="card-body">
            <?php if (!file_exists($sqlFile)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Arquivo <strong>database.sql</strong> não encontrado na raiz do projeto.
                </div>
            <?php else: ?>
                <div class="file-info text-center">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-dim small">Arquivo</div>
                            <div class="fw-bold">database.sql</div>
                        </div>
                        <div class="col border-start border-end border-secondary">
                            <div class="text-dim small">Comandos</div>
                            <div class="fw-bold"><?php echo count($commands); ?></div>
                        </div>
                        <div class="col">
                            <div class="text-dim small">Tamanho</div>
                            <div class="fw-bold"><?php echo round(filesize($sqlFile) / 1024, 2); ?> KB</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 small" style="background: rgba(88, 166, 255, 0.1); border: 1px solid rgba(88, 166, 255, 0.2); color: #58a6ff;">
                    <i class="fas fa-info-circle me-2"></i>
                    Este script importará as tabelas sequencialmente para evitar timeouts no Railway.
                </div>

                <button id="startBtn" class="btn btn-install">
                    <i class="fas fa-rocket me-2"></i> INICIAR IMPORTAÇÃO
                </button>

                <div class="progress">
                    <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="statusTxt" class="status-text" style="display:none;">Preparando ambiente...</div>

                <div id="logContainer" class="log-container"></div>

                <div id="finishActions" class="mt-4 text-center" style="display:none;">
                    <hr class="border-secondary opacity-25">
                    <h5 class="text-success"><i class="fas fa-check-double me-2"></i> Importação Concluída!</h5>
                    <p class="small text-muted">Seu banco de dados está pronto para o Botzap Pro.</p>
                    <a href="index.php" class="btn btn-outline-light btn-sm px-4">IR PARA O PAINEL</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <p class="text-dim small">© 2025 Botzap Pro - Cloud Setup Wizard</p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const commands = <?php echo json_encode($commands); ?>;
    let index = 0;

    $('#startBtn').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> IMPORTANDO...');
        $('#logContainer, .progress, #statusTxt').fadeIn();
        addLog('Iniciando processo de migração Cloud...', 'info');
        executeNext();
    });

    function addLog(msg, type) {
        const log = $('#logContainer');
        const entry = $('<div class="log-entry"></div>').addClass('log-' + type).text('> ' + msg);
        log.append(entry);
        log.scrollTop(log[0].scrollHeight);
    }

    function executeNext() {
        if (index >= commands.length) {
            $('#startBtn').hide();
            $('#progressBar').addClass('bg-success').css('width', '100%');
            $('#statusTxt').html('<span class="text-success">Migração Finalizada com Sucesso!</span>');
            $('#finishActions').fadeIn();
            addLog('MIGRAÇÃO CONCLUÍDA SEM ERROS FATAIS.', 'success');
            return;
        }

        let query = commands[index];
        let progress = Math.round(((index + 1) / commands.length) * 100);
        
        $('#progressBar').css('width', progress + '%');
        $('#statusTxt').text('Executando comando ' + (index + 1) + ' de ' + commands.length + '...');

        $.ajax({
            url: '?ajax=1',
            type: 'POST',
            data: { query: query },
            success: function(res) {
                if (res.success) {
                    if (res.info) {
                        addLog('Info: ' + res.info, 'info');
                    } else {
                        // Log simplificado para queries curtas
                        let preview = query.substring(0, 40) + '...';
                        addLog('Sucesso: ' + preview, 'success');
                    }
                    index++;
                    setTimeout(executeNext, 50); // Delay curto para o usuário ver o progresso
                } else {
                    addLog('ERRO: ' + res.error, 'error');
                    $('#startBtn').prop('disabled', false).html('<i class="fas fa-redo me-2"></i> REENTAR FALHA');
                }
            },
            error: function() {
                addLog('ERRO CRÍTICO: Falha de conexão com o servidor.', 'error');
                $('#startBtn').prop('disabled', false).html('<i class="fas fa-redo me-2"></i> REENTAR');
            }
        });
    }
</script>
</body>
</html>
