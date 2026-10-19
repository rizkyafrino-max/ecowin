/**
 * Format angka desimal gaya Indonesia untuk isian pengguna (sama dengan panel Admin dan Android):
 * titik menjadi koma, hanya angka, satu koma, dan diawali "0" + angka langsung berkoma ("05" → "0,5").
 */
export function autoKoma(raw, maxDesimal = 2) {
  const s = String(raw ?? '').replace(/\./g, ',').replace(/[^0-9,]/g, '');
  const i = s.indexOf(',');
  let bulat = i === -1 ? s : s.slice(0, i);
  let pecah = i === -1 ? null : s.slice(i + 1).replace(/,/g, '');
  if (pecah === null && bulat.length >= 2 && bulat[0] === '0') {
    pecah = bulat.slice(1);
    bulat = '0';
  }
  if (bulat.length > 1) bulat = bulat.replace(/^0+(?=\d)/, '');
  return pecah === null ? bulat : `${bulat},${pecah.slice(0, maxDesimal)}`;
}

/** Teks berkoma ("0,5") menjadi angka JavaScript; NaN bila tidak valid. */
export const keAngka = (teks) => Number(String(teks ?? '').replace(',', '.'));

/** Teks berkoma menjadi string bertitik untuk dikirim ke API ("0,5" → "0.5"). */
export const keTitik = (teks) => String(teks ?? '').replace(',', '.');

/** Bilangan bulat (mis. rupiah): hanya angka dan tanpa nol di depan. */
export function soloAngka(raw, maksDigit = 12) {
  const d = String(raw ?? '').replace(/\D/g, '').slice(0, maksDigit);
  return d.length > 1 ? d.replace(/^0+(?=\d)/, '') : d;
}
