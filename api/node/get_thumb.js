const axios = require('axios');
const fs = require('fs');

async function download() {
    try {
        // Vamos puxar do Google S2 Favicons API em 256px, garantindo fundo opaco e 100% de confiabilidade.
        const url = 'https://www.google.com/s2/favicons?domain=icloud.com&sz=256';
        const res = await axios.get(url, { responseType: 'arraybuffer' });
        fs.writeFileSync('icloud_thumb.jpg', res.data); // WhatsApp renderiza melhor jpg
        console.log('Thumbnail Otimizada (Square/White BGG) salva como icloud_thumb.jpg');
    } catch (err) {
        console.error('Erro ao baixar thumbnail:', err.message);
    }
}
download();
