import { describe, expect, it } from 'vitest';
import { inisial, kg, rupiah, sapaan, tanggal } from './format';

describe('format', () => {
  it('memformat rupiah dan berat', () => {
    expect(rupiah(38250).replace(/\s/g, ' ')).toBe('Rp 38.250');
    expect(kg(22.5)).toBe('22,5 kg');
  });
  it('menangani tanggal kosong atau salah', () => {
    expect(tanggal(null)).toBe('-');
    expect(tanggal('bukan-tanggal')).toBe('-');
  });
  it('sapaan mengikuti jam', () => {
    expect(sapaan(new Date(2026, 0, 1, 8))).toBe('Selamat pagi');
    expect(sapaan(new Date(2026, 0, 1, 20))).toBe('Selamat malam');
  });
  it('inisial maksimal dua huruf', () => {
    expect(inisial('budi santoso wijaya')).toBe('BS');
  });
});
