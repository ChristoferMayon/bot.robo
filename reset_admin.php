<?php
// reset_admin.php - Recuperação de Acesso Administrativo
require_once 'config.php';

$newPassword = 'unlock@2020'; // Senha padrão
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$username = 'admin';

try {
    // 1. Limpa todos os usuários existentes
    $pdo->exec("DELETE FROM usuarios");
    
    // 2. Reseta o auto-incremento (opcional, dependendo do DB, mas vamos forçar o ID)
    // 3. Insere o administrador com ID 2 especificamente
    $insert = $pdo->prepare("INSERT INTO usuarios (id, username, password) VALUES (2, ?, ?)");
    $insert->execute([$username, $hashedPassword]);
    
    $msg = "<b>SISTEMA RESETADO COM SUCESSO!</b><br><br>
            Apenas um usuário agora existe:<br>
            Usuário: <b>$username</b><br>
            Senha: <b style='color:green;'>$newPassword</b><br><br>
            O ID do administrador foi definido como <b>2</b> para compatibilidade total.";
} catch (PDOException $e) {
    $msg = "Erro ao processar o reset: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Reset Admin | Botzap Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card { background: #1e293b; border: 1px solid #334155; padding: 30px; border-radius: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h3 class="mb-4">🔑 Recuperação de Acesso</h3>
        <div class="alert alert-dark">
            <?php echo $msg; ?>
        </div>
        <a href="login.php" class="btn btn-primary mt-3 w-100">IR PARA LOGIN</a>
        <p class="text-muted mt-4 small">Lembre-se de deletar este arquivo após o uso por segurança.</p>
    </div>
</body>
</html>
