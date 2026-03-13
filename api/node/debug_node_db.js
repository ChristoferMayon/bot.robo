const mysql = require('mysql2/promise');
require('dotenv').config();

async function debug() {
    const dbConfig = {
        host: process.env.DB_HOST || 'localhost',
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASSWORD || '',
        database: process.env.DB_DATABASE || 'botzap'
    };
    
    console.log('--- Configuração Node.js ---');
    console.log(dbConfig);
    
    try {
        const connection = await mysql.createConnection(dbConfig);
        console.log('\n--- Dados da Tabela api_keys ---');
        const [rows] = await connection.execute('SELECT id, user_id, chave FROM api_keys');
        console.log(rows);
        
        console.log('\n--- Dados da Tabela user_configs ---');
        const [configs] = await connection.execute('SELECT id, user_id, webhook_url FROM user_configs');
        console.log(configs);
        
        await connection.end();
    } catch (err) {
        console.error('ERRO:', err);
    }
}

debug();
