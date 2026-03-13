<?php
// config.php
session_start();

// Configuração do banco de dados (Suporta Railway e Localhost)
$host = getenv('MYSQLHOST') ?: 'localhost';
$dbname = getenv('MYSQLDATABASE') ?: 'botzap';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// Configuração da API do Bot (Interna no Railway ou Externa no Localhost)
if (getenv('RAILWAY_ENVIRONMENT') || getenv('MYSQLHOST')) {
    define('BOT_API_URL', 'http://localhost:3000');
} else {
    define('BOT_API_URL', getenv('BOT_API_URL') ?: 'http://localhost:3000');
}

// Security Check
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>
