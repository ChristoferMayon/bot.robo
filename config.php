<?php
// config.php
session_start();

$host = getenv('MYSQLHOST') ?: '127.0.0.1';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: '3306';

if ($host === '127.0.0.1' && !getenv('MYSQLHOST')) {
    // Se cair aqui, as variáveis do Railway não foram injetadas
    error_log("AVISO: Variáveis MYSQLHOST não encontradas no ambiente.");
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// Security Check if not logged in
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
?>
