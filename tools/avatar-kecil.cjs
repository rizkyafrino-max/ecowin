// Membuat salinan avatar bawaan berukuran 256x256 (JPEG) untuk web dan Android dari gambar utama di backend/public/images.
// Pakai: node tools/avatar-kecil.cjs   (butuh puppeteer-core dari tools/render-model3d/node_modules dan Chrome)
const fs = require('fs');
const path = require('path');
const puppeteer = require('./render-model3d/node_modules/puppeteer-core');

const akar = path.resolve(__dirname, '..');
const pasangan = [
  ['backend/public/images/avatar-laki.jpg', ['web/public/avatar-laki.jpg', 'android-app/app/src/main/res/drawable-nodpi/avatar_laki.jpg']],
  ['backend/public/images/avatar-perempuan.jpg', ['web/public/avatar-perempuan.jpg', 'android-app/app/src/main/res/drawable-nodpi/avatar_perempuan.jpg']],
];

(async () => {
  const b = await puppeteer.launch({ executablePath: process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files'] });
  const p = await b.newPage();
  await p.goto('about:blank');
  for (const [sumber, tujuan] of pasangan) {
    const data = 'data:image/jpeg;base64,' + fs.readFileSync(path.join(akar, sumber)).toString('base64');
    const hasil = await p.evaluate(async (src) => {
      const img = new Image();
      img.src = src;
      await img.decode();
      const c = document.createElement('canvas');
      c.width = c.height = 256;
      const g = c.getContext('2d');
      g.imageSmoothingQuality = 'high';
      g.drawImage(img, 0, 0, 256, 256);
      return c.toDataURL('image/jpeg', 0.9);
    }, data);
    const bin = Buffer.from(hasil.split(',')[1], 'base64');
    for (const t of tujuan) {
      fs.mkdirSync(path.dirname(path.join(akar, t)), { recursive: true });
      fs.writeFileSync(path.join(akar, t), bin);
      console.log('ok', t, (bin.length / 1024).toFixed(1) + ' KB');
    }
  }
  await b.close();
})();
