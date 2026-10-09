/** Titik yang punya koordinat valid (bisa digambar di peta). */
export const punyaKoordinat = (t) =>
  Number.isFinite(Number(t.latitude)) && Number.isFinite(Number(t.longitude))
  && t.latitude !== null && t.longitude !== null
  && Math.abs(Number(t.latitude)) <= 90 && Math.abs(Number(t.longitude)) <= 180;

/** Pisahkan titik: yang tampil di peta (opsional hanya BioporiPrint) dan yang belum punya koordinat. */
export function bagiTitik(daftar = [], hanyaPrint = false) {
  const lolos = hanyaPrint ? daftar.filter((t) => t.bioporiprint) : daftar;
  return {
    dipeta: lolos.filter(punyaKoordinat),
    tanpaKoordinat: lolos.filter((t) => !punyaKoordinat(t)),
    jumlahPrint: daftar.filter((t) => t.bioporiprint).length,
  };
}
