export const BATAS_TEKS = 1500;
export const MINIMAL_TEKS = 10;

/** Informasi teknis yang ikut terkirim (tanpa data pribadi); pengguna diberi tahu bahwa info teknis dikirim, tanpa rinciannya. */
export function infoPerangkat(layarAsal = 'Web') {
  const peramban = (typeof navigator !== 'undefined' ? navigator.userAgent : '').slice(0, 160);
  const layar = typeof window !== 'undefined' ? `${window.innerWidth}x${window.innerHeight}` : '-';
  return `Layar: ${layarAsal}\nEcoWin Web • ${peramban} • ${layar}`;
}

/** Isi laporan yang dikirim: teks pengguna (dipotong dan dirapikan) diikuti info teknis. Tetap di bawah batas 2000 karakter server. */
export function susunLaporan(teks, info) {
  return `${String(teks).trim().slice(0, BATAS_TEKS)}\n\n— Info otomatis —\n${info}`;
}
