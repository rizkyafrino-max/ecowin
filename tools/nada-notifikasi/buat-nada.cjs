// Membuat nada notifikasi EcoWin (WAV mono 16-bit) dengan sintesis aditif: tidak ada sampel pihak ketiga, jadi bebas hak cipta.
// Pakai: node buat-nada.cjs <folder-keluaran>
const fs = require('fs');
const path = require('path');

const SR = 32000;
const out = process.argv[2] || '.';

/** Satu denting lonceng: beberapa harmonik tak-bulat dengan peluruhan eksponensial (kesan logam/kaca yang lembut). */
function denting(buf, mulai, hz, { amp = 1, peluruhan = 5, parsial = [[1, 1], [2.756, 0.32], [5.404, 0.12]] } = {}) {
  const awal = Math.floor(mulai * SR);
  const n = Math.floor(SR * (2.2 / peluruhan + 0.05));
  for (let i = 0; i < n && awal + i < buf.length; i++) {
    const t = i / SR;
    const serang = Math.min(1, t / 0.004); // 4 ms agar tidak berklik
    let v = 0;
    for (const [rasio, a] of parsial) v += a * Math.sin(2 * Math.PI * hz * rasio * t) * Math.exp(-peluruhan * rasio * 0.55 * t);
    buf[awal + i] += amp * serang * v * Math.exp(-peluruhan * 0.45 * t);
  }
}

/** Nada marimba/kayu: sinus + harmonik ke-4 yang cepat habis. */
function kayu(buf, mulai, hz, amp = 1) {
  const awal = Math.floor(mulai * SR);
  const n = Math.floor(SR * 0.55);
  for (let i = 0; i < n && awal + i < buf.length; i++) {
    const t = i / SR;
    const serang = Math.min(1, t / 0.003);
    const v = Math.sin(2 * Math.PI * hz * t) * Math.exp(-7 * t) + 0.35 * Math.sin(2 * Math.PI * hz * 4 * t) * Math.exp(-28 * t);
    buf[awal + i] += amp * serang * v;
  }
}

/** Gema tipis (dua pantulan) agar nada terasa "berkilau", bukan kering. */
function gema(buf) {
  const hasil = Float32Array.from(buf);
  for (const [tunda, gain] of [[0.09, 0.28], [0.19, 0.14]]) {
    const d = Math.floor(tunda * SR);
    for (let i = d; i < buf.length; i++) hasil[i] += buf[i - d] * gain;
  }
  return hasil;
}

function simpan(nama, buf) {
  const b = gema(buf);
  let puncak = 0;
  for (const v of b) puncak = Math.max(puncak, Math.abs(v));
  const skala = 0.7 / (puncak || 1); // puncak sekitar -3 dB: jelas tapi tidak mengagetkan
  const n = b.length;
  // fade-out 60 ms terakhir agar berhenti mulus
  const fade = Math.floor(0.06 * SR);
  const pcm = Buffer.alloc(n * 2);
  for (let i = 0; i < n; i++) {
    let v = b[i] * skala;
    if (i > n - fade) v *= (n - i) / fade;
    pcm.writeInt16LE(Math.max(-32767, Math.min(32767, Math.round(v * 32767))), i * 2);
  }
  const h = Buffer.alloc(44);
  h.write('RIFF', 0); h.writeUInt32LE(36 + pcm.length, 4); h.write('WAVE', 8); h.write('fmt ', 12);
  h.writeUInt32LE(16, 16); h.writeUInt16LE(1, 20); h.writeUInt16LE(1, 22); h.writeUInt32LE(SR, 24);
  h.writeUInt32LE(SR * 2, 28); h.writeUInt16LE(2, 32); h.writeUInt16LE(16, 34); h.write('data', 36); h.writeUInt32LE(pcm.length, 40);
  fs.writeFileSync(path.join(out, `${nama}.wav`), Buffer.concat([h, pcm]));
  console.log(nama, (n / SR).toFixed(2) + ' dtk', ((44 + pcm.length) / 1024).toFixed(0) + ' KB');
}

// 1) EcoWin: tiga denting naik (G5-C6-E6) seperti "ta-ting-ting" dompet digital, lalu kilau panjang.
{
  const b = new Float32Array(Math.floor(SR * 1.3));
  denting(b, 0.0, 783.99, { amp: 0.8, peluruhan: 7 });
  denting(b, 0.11, 1046.5, { amp: 0.9, peluruhan: 6 });
  denting(b, 0.22, 1318.5, { amp: 1.0, peluruhan: 4.2 });
  simpan('nada_ecowin', b);
}
// 2) Daun: dua nada kayu lembut turun (C6 -> G5), tenang dan hangat.
{
  const b = new Float32Array(Math.floor(SR * 0.95));
  kayu(b, 0.0, 1046.5, 0.9);
  kayu(b, 0.2, 783.99, 1.0);
  simpan('nada_daun', b);
}
// 3) Koin: dua "ting" tinggi berurutan cepat (B6 -> E7), singkat dan ceria.
{
  const b = new Float32Array(Math.floor(SR * 0.9));
  denting(b, 0.0, 1975.5, { amp: 0.8, peluruhan: 9, parsial: [[1, 1], [2.4, 0.25]] });
  denting(b, 0.085, 2637, { amp: 1.0, peluruhan: 6, parsial: [[1, 1], [2.4, 0.25]] });
  simpan('nada_koin', b);
}
