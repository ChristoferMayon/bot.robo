<?php
echo "<h3>Diagnóstico de Variáveis de Ambiente</h3>";
echo "<pre>";
echo "MYSQLHOST: " . (getenv('MYSQLHOST') ?: "NÃO ENCONTRADA") . "\n";
echo "MYSQLUSER: " . (getenv('MYSQLUSER') ?: "NÃO ENCONTRADA") . "\n";
echo "MYSQLDATABASE: " . (getenv('MYSQLDATABASE') ?: "NÃO ENCONTRADA") . "\n";
echo "MYSQLPORT: " . (getenv('MYSQLPORT') ?: "NÃO ENCONTRADA") . "\n";
echo "</pre>";

if (!getenv('MYSQLHOST')) {
    echo "<p style='color:red'>⚠️ <b>ERRO:</b> O site não está lendo as variáveis do banco de dados. Você vinculou o MySQL ao Site no painel do Railway?</p>";
} else {
    echo "<p style='color:green'>✅ Variáveis encontradas. Tentando conexão...</p>";
    try {
        $host = getenv('MYSQLHOST');
        $port = getenv('MYSQLPORT');
        $user = getenv('MYSQLUSER');
        $pass = getenv('MYSQLPASSWORD');
        $db   = getenv('MYSQLDATABASE');
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
        echo "<p style='color:green'>🤝 CONECTADO COM SUCESSO AO BANCO!</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Falha na conexão: " . $e->getMessage() . "</p>";
    }
}
?>
