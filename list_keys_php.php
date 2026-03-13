<?php
require 'config.php';
echo "--- PHP DATABASE VIEW ---\n";
$stmt = $pdo->query("SELECT id, user_id, chave FROM api_keys");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
