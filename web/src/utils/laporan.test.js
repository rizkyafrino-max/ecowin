import { describe, expect, it } from 'vitest';
import { infoPerangkat, susunLaporan } from './laporan';

describe('susunLaporan', () => {
  it('merapikan teks dan menambahkan info otomatis', () => {
    expect(susunLaporan('  Halo ', 'Layar: Web')).toBe('Halo\n\n— Info otomatis —\nLayar: Web');
  });

  it('memotong teks panjang sehingga selalu muat di batas 2000 karakter server', () => {
    const hasil = susunLaporan(`  ${'x'.repeat(5000)}  `, infoPerangkat('Profil'));
    expect(hasil.length).toBeLessThanOrEqual(2000);
    expect(hasil.endsWith(infoPerangkat('Profil'))).toBe(true);
  });

  it('info perangkat memuat layar asal tanpa data pribadi', () => {
    const info = infoPerangkat('Profil');
    expect(info).toMatch(/^Layar: Profil\nEcoWin Web • /);
    expect(info).not.toMatch(/@|token/i);
  });
});
