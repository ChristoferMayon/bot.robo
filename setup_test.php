<?php
require 'config.php';
$stmt = $pdo->query("SELECT chave FROM api_keys LIMIT 1");
$key = $stmt->fetchColumn();

$sessions_json = file_get_contents('http://localhost:3000/api/sessions');
$sessions = json_decode($sessions_json, true);
$session_id = !empty($sessions) ? $sessions[0]['id'] : 'default';

$test_script = file_get_contents('test_api_pro.php');
$test_script = str_replace('SUA_CHAVE_AQUI', $key, $test_script);
$test_script = str_replace('teste_api', $session_id, $test_script);

file_put_contents('test_api_pro.php', $test_script);
echo "Script de teste configurado com Key: " . substr($key, 0, 10) . "... e Sessão: $session_id\n";
?>
