<?php
header('Content-Type: text/plain');
echo "--- DEBUG ENV ---\n";
echo "getenv('BOT_API_URL'): " . (getenv('BOT_API_URL') ?: 'NÃO DEFINIDO') . "\n";
echo "\$_ENV['BOT_API_URL']: " . ($_ENV['BOT_API_URL'] ?? 'NÃO DEFINIDO') . "\n";
echo "\$_SERVER['BOT_API_URL']: " . ($_SERVER['BOT_API_URL'] ?? 'NÃO DEFINIDO') . "\n";
echo "\$_SERVER['HTTP_HOST']: " . ($_SERVER['HTTP_HOST'] ?? '---') . "\n";
echo "--- TODAS AS VARIÁVEIS (RESUMO) ---\n";
print_r(array_keys($_SERVER));
?>
