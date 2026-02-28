const { getUrlInfo } = require('@whiskeysockets/baileys');

async function test() {
    const info = await getUrlInfo('https://www.icloud.com/');
    console.log("TITLE:", info.title);
    console.log("DESC:", info.description);
    if (info.jpegThumbnail) {
        console.log("THUMB SIZE:", info.jpegThumbnail.length);
        const fs = require('fs');
        fs.writeFileSync('thumb_test.jpg', info.jpegThumbnail);
        console.log('Saved to thumb_test.jpg');
    }
}
test();
