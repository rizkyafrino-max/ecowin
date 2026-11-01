// DIHASILKAN OTOMATIS dari satu templat bersama (lihat catatan di buatKartuPng). Jangan diubah manual.
(function () {

const LEBAR = 1712; // rasio kartu bank 85,6 x 54 mm
const TINGGI = 1080;
const KELUARGA_HURUF = '"Inter Variable", Inter, "Segoe UI", system-ui, Arial, sans-serif';

const muatGambar = (src) => new Promise((ok, gagal) => {
  const g = new Image();
  g.onload = () => ok(g);
  g.onerror = () => gagal(new Error('Gambar kartu gagal dimuat.'));
  g.src = src;
});

function persegiBulat(g, x, y, w, h, r) {
  g.beginPath();
  g.moveTo(x + r, y);
  g.arcTo(x + w, y, x + w, y + h, r);
  g.arcTo(x + w, y + h, x, y + h, r);
  g.arcTo(x, y + h, x, y, r);
  g.arcTo(x, y, x + w, y, r);
  g.closePath();
}

/** Membungkus teks menjadi paling banyak [maksBaris] baris selebar [lebar]; baris terakhir diberi elipsis bila masih panjang. */
function bungkusTeks(g, teks, lebar, maksBaris) {
  const kata = String(teks).split(/\s+/).filter(Boolean);
  const baris = [];
  let kini = '';
  for (const k of kata) {
    const coba = kini ? kini + ' ' + k : k;
    if (g.measureText(coba).width <= lebar || !kini) kini = coba;
    else { baris.push(kini); kini = k; }
  }
  if (kini) baris.push(kini);
  const sisa = baris.slice(0, maksBaris);
  // Bila teks masih tersisa, baris terakhir diberi elipsis.
  if (baris.length > maksBaris) sisa[maksBaris - 1] += ' …';
  // Setiap baris dijamin muat: satu kata yang lebih lebar dari kartu juga dipotong dengan elipsis.
  return sisa.map((b) => potongMuat(g, b.replace(/ …$/, '…'), lebar));
}

function potongMuat(g, teks, lebar) {
  if (g.measureText(teks).width <= lebar) return teks;
  let akhir = teks.replace(/…$/, '');
  while (akhir.length > 1 && g.measureText(akhir + '…').width > lebar) akhir = akhir.slice(0, -1);
  return akhir + '…';
}

function aturSpasiHuruf(g, nilai) {
  if ('letterSpacing' in g) g.letterSpacing = nilai; // tidak semua peramban mendukung; tanpa itu teks tetap terbaca
}

/**
 * Menggambar Kartu Nasabah EcoWin menjadi gambar PNG (1712 x 1080 px), sama dengan tampilan kartu di web, panel, dan Android.
 * data: { nama, nomor, bank, qr (URL/data URI gambar QR), emblem (URL logo) }
 * Penyalinan: web/src/utils/kartuPng.js dan backend/public/js/kartu-png.js dihasilkan dari satu templat; jangan diubah manual.
 */
async function buatKartuPng(data) {
  const kanvas = document.createElement('canvas');
  kanvas.width = LEBAR;
  kanvas.height = TINGGI;
  const g = kanvas.getContext('2d');
  const [emblem, qr] = await Promise.all([muatGambar(data.emblem), muatGambar(data.qr)]);
  if (document.fonts && document.fonts.load) {
    try { await Promise.all([document.fonts.load('800 60px ' + KELUARGA_HURUF), document.fonts.load('600 30px ' + KELUARGA_HURUF)]); } catch (e) { /* pakai huruf cadangan */ }
  }

  const pad = 111; // 6,5% lebar
  g.save();
  persegiBulat(g, 0, 0, LEBAR, TINGGI, 60);
  g.clip();

  // Latar gradasi hijau EcoWin
  const gradasi = g.createLinearGradient(0, 0, LEBAR, TINGGI);
  gradasi.addColorStop(0, '#064E3B');
  gradasi.addColorStop(0.5, '#059669');
  gradasi.addColorStop(1, '#34D399');
  g.fillStyle = gradasi;
  g.fillRect(0, 0, LEBAR, TINGGI);

  // Hiasan: lingkaran bening dan daun samar
  g.fillStyle = 'rgba(255,255,255,0.09)';
  g.beginPath(); g.arc(LEBAR - 60, -50, 330, 0, Math.PI * 2); g.fill();
  g.fillStyle = 'rgba(255,255,255,0.07)';
  g.beginPath(); g.arc(70, TINGGI + 40, 260, 0, Math.PI * 2); g.fill();
  g.save();
  g.translate(LEBAR - 580, TINGGI - 560);
  g.scale(22, 22);
  g.strokeStyle = 'rgba(255,255,255,0.07)'; g.lineWidth = 2; g.lineCap = 'round'; g.lineJoin = 'round';
  g.stroke(new Path2D('M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z'));
  g.stroke(new Path2D('M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12'));
  g.restore();

  // Merek: lambang di kotak putih, nama, dan sub-judul
  const kotak = 171;
  g.fillStyle = '#FFFFFF';
  persegiBulat(g, pad, pad, kotak, kotak, 41); g.fill();
  const tinggiLambang = kotak - 40;
  const lebarLambang = tinggiLambang * (emblem.width / emblem.height);
  g.drawImage(emblem, pad + (kotak - lebarLambang) / 2, pad + 20, lebarLambang, tinggiLambang);
  const xMerek = pad + kotak + 41;
  g.textBaseline = 'alphabetic';
  g.font = '800 72px ' + KELUARGA_HURUF;
  g.fillStyle = '#FFFFFF'; g.fillText('Eco', xMerek, pad + 74);
  g.fillStyle = '#A7F3D0'; g.fillText('Win', xMerek + g.measureText('Eco').width, pad + 74);
  g.font = '700 30px ' + KELUARGA_HURUF; aturSpasiHuruf(g, '3px');
  g.fillStyle = '#A7F3D0'; g.fillText('BANK SAMPAH DIGITAL', xMerek, pad + 132);
  aturSpasiHuruf(g, '0px');

  // Identitas nasabah
  const lebarTeks = 968 - 86 - pad; // sampai celah di kiri kotak QR
  g.font = '700 39px ' + KELUARGA_HURUF; aturSpasiHuruf(g, '6px');
  g.fillStyle = '#A7F3D0'; g.fillText('KARTU NASABAH', pad, 440);
  aturSpasiHuruf(g, '0px');
  g.font = '800 96px ' + KELUARGA_HURUF;
  g.fillStyle = '#FFFFFF';
  const barisNama = bungkusTeks(g, data.nama || '', lebarTeks, 2);
  barisNama.forEach((b, i) => g.fillText(b, pad, 536 + i * 110));
  g.font = '600 62px ui-monospace, "Cascadia Mono", Consolas, "Courier New", monospace'; aturSpasiHuruf(g, '7px');
  g.fillStyle = '#D1FAE5';
  g.fillText(data.nomor || '', pad, 536 + barisNama.length * 110 + 30);
  aturSpasiHuruf(g, '0px');

  // Bank sampah dengan ikon lokasi
  g.save();
  g.translate(pad, TINGGI - pad - 52); g.scale(2.2, 2.2);
  g.strokeStyle = '#A7F3D0'; g.lineWidth = 2; g.lineCap = 'round'; g.lineJoin = 'round';
  g.stroke(new Path2D('M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0'));
  g.beginPath(); g.arc(12, 10, 3, 0, Math.PI * 2); g.stroke();
  g.restore();
  g.font = '500 50px ' + KELUARGA_HURUF;
  g.fillStyle = 'rgba(255,255,255,0.92)';
  const bank = bungkusTeks(g, data.bank || 'EcoWin', lebarTeks - 80, 1)[0];
  g.fillText(bank, pad + 80, TINGGI - pad - 8);

  // QR putih besar di kanan, tajam (tanpa penghalusan)
  const sisi = 633;
  const xQr = LEBAR - pad - sisi;
  const yQr = 193;
  g.fillStyle = '#FFFFFF';
  persegiBulat(g, xQr, yQr, sisi, sisi, 62); g.fill();
  g.imageSmoothingEnabled = false;
  g.drawImage(qr, xQr + 27, yQr + 27, sisi - 54, sisi - 54);
  g.imageSmoothingEnabled = true;
  g.font = '700 34px ' + KELUARGA_HURUF; aturSpasiHuruf(g, '3px');
  g.fillStyle = '#D1FAE5'; g.textAlign = 'center';
  g.fillText('PINDAI OLEH PETUGAS', xQr + sisi / 2, yQr + sisi + 62);
  g.textAlign = 'left'; aturSpasiHuruf(g, '0px');

  g.restore();
  g.strokeStyle = 'rgba(255,255,255,0.25)'; g.lineWidth = 4;
  persegiBulat(g, 2, 2, LEBAR - 4, TINGGI - 4, 58); g.stroke();

  return new Promise((ok, gagal) => kanvas.toBlob((b) => (b ? ok(b) : gagal(new Error('Gambar kartu gagal dibuat.'))), 'image/png'));
}

/** Mengunduh Blob sebagai berkas. */
function unduhBlob(blob, namaBerkas) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = namaBerkas;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 2000);
}

/** Nama berkas aman dari nomor nasabah (hanya huruf, angka, strip). */
function namaBerkasKartu(nomor) {
  return 'Kartu-EcoWin-' + String(nomor || 'nasabah').replace(/[^A-Za-z0-9-]/g, '') + '.png';
}

  window.buatKartuPng = buatKartuPng;
  window.unduhBlob = unduhBlob;
  window.namaBerkasKartu = namaBerkasKartu;
})();
