const mysql = require('mysql2/promise');
require('dotenv').config();

async function fix() {
    const dbConfig = {
        host: process.env.DB_HOST || 'localhost',
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASSWORD || '',
        database: process.env.DB_DATABASE || 'botzap'
    };
    
    try {
        const connection = await mysql.createConnection(dbConfig);
        console.log('--- Corrigindo Vínculo de Usuário via Node.js ---');
        
        // 1. Tentar encontrar o usuário 'admin' ou ID 2
        const [users] = await connection.execute('SELECT id FROM usuarios WHERE id = 2 OR username = "admin" LIMIT 1');
        const userId = users.length > 0 ? users[0].id : null;
        
        if (!userId) {
            console.error('ERRO: Usuário não encontrado.');
            await connection.end();
            return;
        }
        
        console.log(`Usando User ID: ${userId}`);
        
        // 2. Atualizar todas as chaves de API para este usuário (apenas para o teste)
        const [result] = await connection.execute('UPDATE api_keys SET user_id = ?', [userId]);
        console.log(`Resultado api_keys: ${result.affectedRows} linhas atualizadas.`);
        
        // 3. Garantir Webhook configurado
        const webhookUrl = 'http://localhost:8080/webhook_listener.php';
        await connection.execute('INSERT INTO user_configs (user_id, webhook_url) VALUES (?, ?) ON DUPLICATE KEY UPDATE webhook_url = ?', [userId, webhookUrl, webhookUrl]);
        console.log(`Webhook configurado para: ${webhookUrl}`);
        
        await connection.end();
        console.log('Concluído.');
    } catch (err) {
        console.error('ERRO:', err);
    }
}

fix();
