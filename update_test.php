<?php
require 'config.php';
$stmt = $pdo->query("SELECT chave FROM api_keys LIMIT 1");
$key = $stmt->fetchColumn();

$content = file_get_contents('test_api_pro.php');
$content = preg_replace('/\$api_key = ".*?";/', '$api_key = "' . $key . '";', $content);
$content = preg_replace('/\$target_number = ".*?";/', '$target_number = "554195457772";', $content);

file_put_contents('test_api_pro.php', $content);
echo "Configuração pronta.\n";
?>
