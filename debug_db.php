<?php
require 'config.php';
echo "--- Estrutura da Tabela 'usuarios' ---\n";
$stmt = $pdo->query("DESCRIBE usuarios");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Estrutura da Tabela 'api_keys' ---\n";
$stmt = $pdo->query("DESCRIBE api_keys");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- Estrutura da Tabela 'user_configs' ---\n";
$stmt = $pdo->query("DESCRIBE user_configs");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
