#!/bin/sh

# Inicia o robô de WhatsApp em segundo plano
echo "Iniciando Robô WhatsApp..."
cd api/node && node server.js &

# Inicia o servidor PHP (Nixpacks padrão usa PHP-FPM + Nginx)
# Mas para simplificar em um container híbrido, podemos usar o servidor embutido
# ou configurar o Nginx manualmente.
# O Railway Nixpacks para PHP geralmente configura o Nginx.
# Vamos rodar o PHP na porta que o Railway nos der ($PORT)

echo "Iniciando Painel PHP na porta $PORT..."
php -S 0.0.0.0:$PORT
