import { describe, expect, it } from 'vitest';
import { perkecilFoto } from './foto';

describe('perkecilFoto', () => {
  it('menolak berkas yang bukan JPEG/PNG/WebP', async () => {
    await expect(perkecilFoto(new File(['<svg/>'], 'a.svg', { type: 'image/svg+xml' }))).rejects.toThrow(/JPEG, PNG, atau WebP/);
    await expect(perkecilFoto(new File(['GIF89a'], 'a.gif', { type: 'image/gif' }))).rejects.toThrow(/JPEG, PNG, atau WebP/);
    await expect(perkecilFoto(new File(['x'], 'a.pdf', { type: 'application/pdf' }))).rejects.toThrow(/JPEG, PNG, atau WebP/);
    await expect(perkecilFoto(null)).rejects.toThrow(/JPEG, PNG, atau WebP/);
  });

  it('menolak berkas yang terlalu besar sebelum dibaca', async () => {
    const besar = new File([new Uint8Array(21 * 1024 * 1024)], 'besar.jpg', { type: 'image/jpeg' });
    await expect(perkecilFoto(besar)).rejects.toThrow(/terlalu besar/);
  });
});
