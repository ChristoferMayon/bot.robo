const express = require('express');
const cors = require('cors');
const qrcode = require('qrcode');
const { default: makeWASocket, useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion } = require('@whiskeysockets/baileys');
const pino = require('pino');
const axios = require('axios');
const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');
require('dotenv').config();

const app = express();

// --- CORREÇÃO DE CORS PARA EVITAR ERR_FAILED ---
app.use(cors({
    origin: true, 
    methods: ['GET', 'POST', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
    credentials: true
}));

app.use(express.json({ limit: '50mb' }));

// Middleware de Log
app.use((req, res, next) => {
    console.log(`[HTTP] ${req.method} ${req.url} - ${req.headers.authorization ? '(Auth)' : '(No Auth)'}`);
    next();
});

// --- Configurações de Ambiente ---
const PORT = process.env.PORT || 3000;
const SESSIONS_DIR = path.join(__dirname, 'sessions');
if (!fs.existsSync(SESSIONS_DIR)) fs.mkdirSync(SESSIONS_DIR, { recursive: true });

// --- Conexão Banco de Dados ---
const dbConfig = {
    host: process.env.MYSQLHOST || process.env.DB_HOST || 'localhost',
    user: process.env.MYSQLUSER || process.env.DB_USER || 'root',
    password: process.env.MYSQLPASSWORD || process.env.DB_PASSWORD || '',
    database: process.env.MYSQLDATABASE || process.env.DB_DATABASE || 'botzap',
    port: process.env.MYSQLPORT || 3306
};
const pool = mysql.createPool(dbConfig);

const sessions = new Map(); 
const TRACK_LINKS_FILE = path.join(__dirname, 'trackLinks.json');
let trackLinksMap = new Map();

// --- Middleware de Autenticação Bearer ---
async function authenticate(req, res, next) {
    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return res.status(401).json({ error: 'Não autorizado. Use: Authorization: Bearer SUA_CHAVE' });
    }
    const token = authHeader.split(' ')[1];

    try {
        const [rows] = await pool.execute('SELECT user_id FROM api_keys WHERE chave = ?', [token]);
        if (rows.length === 0) {
            return res.status(401).json({ error: 'API Key inválida ou revogada.' });
        }
        req.userId = rows[0].user_id;
        next();
    } catch (err) {
        console.error('[Auth] Erro ao validar token:', err);
        res.status(500).json({ error: 'Erro interno ao validar autenticação.' });
    }
}

// --- Funções de Suporte (Rastreio e Localização) ---
async function getMediaBuffer(mediaPath) {
    if (!mediaPath) return null;
    try {
        if (mediaPath.startsWith('http')) {
            const response = await axios.get(mediaPath, { responseType: 'arraybuffer' });
            return Buffer.from(response.data);
        } else if (fs.existsSync(mediaPath)) {
            return fs.readFileSync(mediaPath);
        }
    } catch (err) {
        console.error('[Media] Erro ao obter mídia:', err.message);
    }
    return null;
}

function loadTrackLinks() {
    try {
        if (fs.existsSync(TRACK_LINKS_FILE)) {
            const data = fs.readFileSync(TRACK_LINKS_FILE, 'utf-8');
            const obj = JSON.parse(data);
            trackLinksMap = new Map();
            for (const [key, val] of Object.entries(obj)) {
                if (typeof val === 'string') {
                    trackLinksMap.set(key, { link: val, lang: 'pt' });
                } else {
                    trackLinksMap.set(key, val);
                }
            }
        }
    } catch (err) {
        console.error('[Persistence] Erro ao carregar trackLinks:', err);
    }
}
function saveTrackLinks() {
    try {
        const obj = Object.fromEntries(trackLinksMap);
        fs.writeFileSync(TRACK_LINKS_FILE, JSON.stringify(obj, null, 2));
    } catch (err) {
        console.error('[Persistence] Erro ao salvar trackLinks:', err);
    }
}

function updateTrackLinksForPhone(cleanNumber, trackLink, language) {
    let userLang = language || 'pt';
    let isPaused = false;
    for (const [k, v] of trackLinksMap.entries()) {
        if (k.startsWith(cleanNumber) || v.phone === cleanNumber) {
            if (v.paused) isPaused = true;
        }
    }
    let updated = false;
    for (const [k, v] of trackLinksMap.entries()) {
        if (k.startsWith(cleanNumber) || v.phone === cleanNumber) {
            v.link = trackLink;
            v.lang = userLang;
            v.paused = isPaused;
            trackLinksMap.set(k, v);
            updated = true;
        }
    }
    const standardJid = `${cleanNumber}@s.whatsapp.net`;
    if (!trackLinksMap.has(standardJid)) {
        trackLinksMap.set(standardJid, { link: trackLink, lang: userLang, phone: cleanNumber, paused: isPaused });
        updated = true;
    } else {
        const v = trackLinksMap.get(standardJid);
        v.link = trackLink;
        v.lang = userLang;
        v.paused = isPaused;
        trackLinksMap.set(standardJid, v);
    }
    if (updated) saveTrackLinks();
}
loadTrackLinks();

let botLangs = {};
const LANGS_FILE = path.join(__dirname, 'languages.json');
try {
    if (fs.existsSync(LANGS_FILE)) {
        botLangs = JSON.parse(fs.readFileSync(LANGS_FILE, 'utf-8'));
    }
} catch (err) {
    console.error('[Localization] Erro ao carregar languages.json:', err);
}

function getMsg(lang, key, params = {}) {
    let msg = botLangs[lang]?.[key] || botLangs['pt']?.[key] || 'Text missing.';
    for (const p in params) {
        const val = params[p] || '';
        msg = msg.split(`{${p}}`).join(val);
    }
    return msg;
}

let icloudThumbBuf = null;
async function carregarThumb() {
    try {
        const res = await fetch("https://i.ibb.co/Cs6D6SG4/icloud-thumb.png");
        const arrayBuffer = await res.arrayBuffer();
        icloudThumbBuf = Buffer.from(arrayBuffer);
    } catch (e) {
        console.error("Erro ao baixar thumbnail:", e);
    }
}
carregarThumb();

// --- Gerenciador de Sessões WhatsApp ---
async function startSession(sessionId, userId = null) {
    if (sessions.has(sessionId)) return;

    console.log(`[Manager] Iniciando sessão: ${sessionId}`);
    const sessionPath = path.join(SESSIONS_DIR, sessionId);
    if (!fs.existsSync(sessionPath)) fs.mkdirSync(sessionPath, { recursive: true });

    const { state, saveCreds } = await useMultiFileAuthState(sessionPath);
    const { version } = await fetchLatestBaileysVersion();

    const sock = makeWASocket({
        version,
        auth: state,
        printQRInTerminal: false,
        logger: pino({ level: 'silent' }),
        browser: ['Botzap Pro', 'Chrome', '1.0.0']
    });

    const sessionData = { id: sessionId, sock, status: 'connecting', qr: '', number: '', userId, retryCount: 0 };
    sessions.set(sessionId, sessionData);

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;
        if (qr) {
            sessionData.status = 'waiting_qr';
            try {
                sessionData.qr = await qrcode.toDataURL(qr);
            } catch (err) {
                console.error(`[Session:${sessionId}] Erro ao gerar QR Base64:`, err);
            }
        }

        if (connection === 'close') {
            const statusCode = (lastDisconnect?.error)?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
            sessionData.status = 'disconnected';
            sessionData.qr = '';

            if (shouldReconnect) {
                if (sessionData.retryCount < 10) {
                    sessionData.retryCount++;
                    setTimeout(() => {
                        sessions.delete(sessionId);
                        startSession(sessionId, userId);
                    }, 5000);
                }
            } else {
                sessions.delete(sessionId);
                try { fs.rmSync(sessionPath, { recursive: true, force: true }); } catch (e) { }
            }
        } else if (connection === 'open') {
            sessionData.status = 'connected';
            sessionData.qr = '';
            sessionData.number = sock.user.id.split(':')[0];
            sessionData.retryCount = 0;
            console.log(`[Session:${sessionId}] Conectada: ${sessionData.number}`);
        }
    });

    sock.ev.on('messages.upsert', async (m) => {
        const msg = m.messages[0];
        if (!msg.message || msg.key.fromMe) return;
        const chatId = msg.key.remoteJid;
        if (chatId.endsWith('@g.us')) return;
        const phone = chatId.split('@')[0];

        try {
            const [rows] = await pool.execute("SELECT status FROM mensagens_enviadas WHERE numero = ? ORDER BY id DESC LIMIT 1", [phone]);
            if (rows.length && rows[0].status?.toLowerCase() === 'pausado') return;
        } catch (err) { console.error('[DB PAUSE ERROR]', err.message); }

        let targetUserId = sessionData.userId || 2;
        if (targetUserId) {
            try {
                const [rows] = await pool.execute('SELECT webhook_url FROM user_configs WHERE user_id = ?', [targetUserId]);
                if (rows.length > 0 && rows[0].webhook_url) {
                    const payload = {
                        session: sessionId,
                        from: phone,
                        message: msg.message.conversation || msg.message.extendedTextMessage?.text || '',
                        timestamp: new Date().toISOString(),
                        pushName: msg.pushName
                    };
                    axios.post(rows[0].webhook_url, payload).catch(() => {});
                }
            } catch (e) {}
        }

        if (!trackLinksMap.has(chatId)) {
            const phoneTail = phone.slice(-8);
            let foundData = null;
            for (const [k, v] of trackLinksMap.entries()) {
                const mapPhone = (v.phone || k.split('@')[0]).toString();
                if (mapPhone.endsWith(phoneTail)) {
                    foundData = v;
                    break;
                }
            }
            if (foundData) {
                trackLinksMap.set(chatId, foundData);
                saveTrackLinks();
            } else return;
        }

        const contactData = trackLinksMap.get(chatId);
        if (contactData?.paused === true || contactData?.paused === 'true') return;

        const pushName = msg.pushName ? ` ${msg.pushName}` : '';
        let inTexto = msg.message.conversation || msg.message.extendedTextMessage?.text || '';
        let normalizeTxt = inTexto.trim().toUpperCase();

        let linkRecuperacao = contactData?.link || 'https://icloud.com/find';
        let userLang = contactData?.lang || 'pt';

        // Lógica de Atendimento (simplificada para o exemplo)
        if (normalizeTxt === '1' || normalizeTxt.includes('📍')) {
            const replyLink = getMsg(userLang, 'option_1', { name: pushName.trim(), link: linkRecuperacao });
            const customPreview = { title: 'iCloud.com', 'canonical-url': linkRecuperacao, jpegThumbnail: icloudThumbBuf };
            await sock.sendMessage(chatId, { text: replyLink, linkPreview: customPreview });
        }
    });
}

// Inicializar sessões existentes
if (fs.existsSync(SESSIONS_DIR)) {
    fs.readdirSync(SESSIONS_DIR).forEach(dir => {
        if (fs.existsSync(path.join(SESSIONS_DIR, dir, 'creds.json'))) startSession(dir);
    });
}

// --- API Endpoints ---

// 1. Criar Sessão (PRECISA DE AUTH)
app.post('/api/create-session', authenticate, async (req, res) => {
    const { session } = req.body;
    if (!session) return res.status(400).json({ error: 'Nome da sessão é obrigatório.' });
    if (sessions.has(session)) {
        const s = sessions.get(session);
        s.userId = req.userId;
        return res.json({ success: true, message: 'Sessão vinculada.', session });
    }
    await startSession(session, req.userId);
    res.json({ success: true, message: 'Sessão inicializada.', session });
});

// 2. QR Code (SEM AUTH para o navegador carregar)
app.get('/api/qrcode/:session', (req, res) => {
    const s = sessions.get(req.params.session);
    if (!s) return res.status(404).json({ error: 'Sessão não encontrada.' });
    res.json({ session: s.id, qr: s.qr });
});

// 3. Status (SEM AUTH para o painel verificar conexão)
app.get('/api/status/:session', (req, res) => {
    const s = sessions.get(req.params.session);
    if (!s) return res.status(404).json({ error: 'Sessão não encontrada.' });
    res.json({ session: s.id, status: s.status, number: s.number });
});

// 4. Enviar Mensagem (PRECISA DE AUTH)
app.post('/api/send-message', authenticate, async (req, res) => {
    const { session, number, message, mediaPath, trackLink, language, option, name } = req.body;
    const s = sessions.get(session);
    if (!s || s.status !== 'connected') return res.status(403).json({ error: 'Sessão não conectada.' });

    try {
        const cleanNumber = number.replace(/\D/g, '');
        const chatId = `${cleanNumber}@s.whatsapp.net`;
        let userLang = language || 'pt';

        if (trackLink) updateTrackLinksForPhone(cleanNumber, trackLink, userLang);

        if (option !== undefined && [1, 2, 3, 6].includes(option)) {
            const replyText = getMsg(userLang, `option_${option}`, { name: name || '', link: trackLink || 'https://icloud.com' });
            const customPreview = { title: 'iCloud.com', jpegThumbnail: icloudThumbBuf };
            await s.sock.sendMessage(chatId, { text: replyText, linkPreview: customPreview });
        } else {
            const imageBuffer = await getMediaBuffer(mediaPath);
            if (imageBuffer) await s.sock.sendMessage(chatId, { image: imageBuffer, caption: message });
            else await s.sock.sendMessage(chatId, { text: message });
        }

        res.json({ status: 'success', message: 'Mensagem enviada!' });
    } catch (err) { res.status(500).json({ error: err.message }); }
});

// Outras rotas (Delete, Stats etc...)
app.delete('/api/sessions/:id', authenticate, async (req, res) => {
    const s = sessions.get(req.params.id);
    if (s) { try { await s.sock.logout(); } catch (e) {} sessions.delete(req.params.id); }
    const p = path.join(SESSIONS_DIR, req.params.id);
    if (fs.existsSync(p)) fs.rmSync(p, { recursive: true, force: true });
    res.json({ success: true });
});

app.listen(PORT, () => {
    console.log(`[Server] WhatsApp BOT API na porta ${PORT}`);
});
