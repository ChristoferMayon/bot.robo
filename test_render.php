<?php
session_start();
$_SESSION['loggedin'] = true;
$_SESSION['username'] = 'admin';
ob_start();
include 'index.php';
$html = ob_get_clean();
file_put_contents('render_output.html', $html);
echo "Renderizado com sucesso.";
?>
