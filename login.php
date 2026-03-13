<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username)) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Check password - handle both empty (legacy) and hashed passwords
            $authorized = false;
            if (empty($user['password']) && empty($password)) {
                $authorized = true;
            } else if (password_verify($password, $user['password'])) {
                $authorized = true;
            }

            if ($authorized) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php");
                exit;
            } else {
                $error = 'Credenciais inválidas.';
            }
        } else {
            $error = 'Usuário não encontrado.';
        }
    } else {
        $error = 'Por favor, preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Botzap Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #0f172a;
        }
        body {
            background-color: var(--bg);
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }
        .login-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
            filter: drop-shadow(0 0 15px rgba(79, 70, 229, 0.4));
        }
        h2 {
            color: #fff;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 8px;
        }
        p.subtitle {
            color: #94a3b8;
            font-size: 0.875rem;
            margin-bottom: 30px;
        }
        .form-label {
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }
        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            padding: 12px 16px;
            transition: all 0.3s;
        }
        .form-control:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2);
            color: #fff;
        }
        .btn-login {
            background: var(--primary);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-weight: 700;
            padding: 14px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            color: #f87171;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }
        /* Background animation elements */
        .bg-blob {
            position: absolute;
            width: 300px;
            height: 300px;
            background: var(--primary);
            filter: blur(100px);
            opacity: 0.2;
            border-radius: 50%;
            z-index: 1;
        }
        .blob-1 { top: -100px; right: -100px; }
        .blob-2 { bottom: -100px; left: -100px; }
    </style>
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo-area">
                <i class="fas fa-microchip logo-icon"></i>
                <h2>Botzap Pro</h2>
                <p class="subtitle">Acesse sua área administrativa</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="form-label">Nome de Usuário</label>
                    <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label">Senha</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-login">
                    Entrar no Painel <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
