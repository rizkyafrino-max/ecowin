const MAX_SIDE = 1600;
const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];
export const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;

/**
 * Validasi + kompres foto di sisi klien (hanya kenyamanan: server tetap memvalidasi ulang).
 * Hasil selalu JPEG maksimal 1600px agar upload dari kamera HP cepat.
 */
export async function prepareImage(file) {
  if (!file) throw new Error('Pilih foto terlebih dahulu.');
  if (!ALLOWED.includes(file.type)) throw new Error('Format foto harus JPG, PNG, atau WebP.');

  try {
    const bitmap = await createImageBitmap(file);
    const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close?.();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.82));
    if (blob) return new File([blob], `biopori-${Date.now()}.jpg`, { type: 'image/jpeg' });
  } catch {
    /* gagal dikompres: pakai berkas asli bila masih dalam batas ukuran */
  }

  if (file.size > MAX_UPLOAD_BYTES) throw new Error('Ukuran foto maksimal 5 MB.');
  return file;
}
