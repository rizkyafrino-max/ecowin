import { describe, expect, it } from 'vitest';
import { bagiTitik, punyaKoordinat } from './biopori';

const titik = [
  { id: 1, nama_lokasi: 'Taman RT 01', latitude: -6.2, longitude: 106.8, bioporiprint: true },
  { id: 2, nama_lokasi: 'Balai', latitude: '-6.21', longitude: '106.81', bioporiprint: false },
  { id: 3, nama_lokasi: 'Belum diukur', latitude: null, longitude: null, bioporiprint: true },
  { id: 4, nama_lokasi: 'Salah', latitude: 120, longitude: 999, bioporiprint: false },
];

describe('peta Biopori', () => {
  it('mengenali koordinat valid (angka/teks) dan menolak null atau di luar rentang', () => {
    expect(punyaKoordinat(titik[0])).toBe(true);
    expect(punyaKoordinat(titik[1])).toBe(true);
    expect(punyaKoordinat(titik[2])).toBe(false);
    expect(punyaKoordinat(titik[3])).toBe(false);
  });

  it('memisahkan titik di peta, tanpa koordinat, dan menghitung BioporiPrint', () => {
    const r = bagiTitik(titik);
    expect(r.dipeta.map((t) => t.id)).toEqual([1, 2]);
    expect(r.tanpaKoordinat.map((t) => t.id)).toEqual([3, 4]);
    expect(r.jumlahPrint).toBe(2);
  });

  it('filter BioporiPrint hanya menampilkan titik print', () => {
    const r = bagiTitik(titik, true);
    expect(r.dipeta.map((t) => t.id)).toEqual([1]);
    expect(r.tanpaKoordinat.map((t) => t.id)).toEqual([3]);
  });

  it('daftar kosong aman', () => {
    expect(bagiTitik([])).toEqual({ dipeta: [], tanpaKoordinat: [], jumlahPrint: 0 });
  });
});
