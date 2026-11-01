import emblem from '../assets/ecowin-emblem.png';
import { buatKartuPng, namaBerkasKartu, unduhBlob } from './kartuPng';

/** Gabungan nama dan RT/RW bank sampah untuk dicetak di kartu. */
export function ringkasBank(nasabah) {
  const b = nasabah?.bank_sampah;
  return [b?.nama, b?.rt ? `RT ${b.rt}${b.rw ? `/RW ${b.rw}` : ''}` : null].filter(Boolean).join(' · ');
}

/** Membuat gambar PNG Kartu Nasabah (sama dengan tampilan kartu) lalu mengunduhnya. data = respons /me/qr. */
export async function unduhKartuNasabah(data, bank) {
  const blob = await buatKartuPng({
    nama: data.nama,
    nomor: data.nomor_nasabah,
    bank,
    qr: `data:image/png;base64,${data.qr_png_base64}`,
    emblem,
  });
  unduhBlob(blob, namaBerkasKartu(data.nomor_nasabah));
}
