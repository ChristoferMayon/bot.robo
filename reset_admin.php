<?php
// reset_admin.php - Recuperação de Acesso Administrativo
require_once 'config.php';

$newPassword = 'unlock@2020'; // Senha padrão
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$username = 'admin';

try {
    // Tenta atualizar se o usuário existir, ou insere se não existir
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $update = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $update->execute([$hashedPassword, $user['id']]);
        $msg = "Sucesso! A senha do usuário <b>$username</b> foi redefinida para: <b style='color:green;'>$newPassword</b>";
    } else {
        $insert = $pdo->prepare("INSERT INTO usuarios (username, password) VALUES (?, ?)");
        $insert->execute([$username, $hashedPassword]);
        $msg = "Sucesso! Usuário <b>$username</b> criado com a senha: <b style='color:green;'>$newPassword</b>";
    }
} catch (PDOException $e) {
    $msg = "Erro ao processar: " . $e->getMessage();
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
