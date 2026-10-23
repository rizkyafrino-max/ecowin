const TIPE_BOLEH = ['image/jpeg', 'image/png', 'image/webp'];

/**
 * Memperkecil foto di perangkat sebelum diunggah (maksimal sisi terpanjang 1024 px, JPEG): unggahan jadi ringan dan
 * orientasi EXIF sudah benar. Server tetap memeriksa isi berkas dan menggambar ulangnya (tanpa metadata).
 */
export async function perkecilFoto(file, maks = 1024) {
  if (!file || !TIPE_BOLEH.includes(file.type)) throw new Error('Pilih foto berformat JPEG, PNG, atau WebP.');
  if (file.size > 20 * 1024 * 1024) throw new Error('Foto terlalu besar. Pilih foto yang lebih kecil.');

  let bitmap;
  try {
    bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
  } catch {
    throw new Error('Foto tidak dapat dibaca. Coba foto lain.');
  }

  const skala = Math.min(1, maks / Math.max(bitmap.width, bitmap.height));
  const lebar = Math.max(1, Math.round(bitmap.width * skala));
  const tinggi = Math.max(1, Math.round(bitmap.height * skala));
  const kanvas = document.createElement('canvas');
  kanvas.width = lebar;
  kanvas.height = tinggi;
  const g = kanvas.getContext('2d');
  g.fillStyle = '#fff'; // PNG/WebP transparan menjadi latar putih
  g.fillRect(0, 0, lebar, tinggi);
  g.drawImage(bitmap, 0, 0, lebar, tinggi);
  bitmap.close?.();

  const blob = await new Promise((ok) => kanvas.toBlob(ok, 'image/jpeg', 0.88));
  if (!blob) throw new Error('Foto gagal diproses. Coba foto lain.');
  return blob;
}
