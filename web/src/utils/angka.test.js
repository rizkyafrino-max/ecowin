import { describe, expect, it } from 'vitest';
import { autoKoma, keAngka, keTitik, soloAngka } from './angka';

describe('autoKoma', () => {
  it('"0" diikuti angka langsung berkoma', () => {
    expect(autoKoma('0')).toBe('0');
    expect(autoKoma('05')).toBe('0,5');
    expect(autoKoma('00')).toBe('0,0');
    expect(autoKoma('0123')).toBe('0,12'); // maksimal 2 angka di belakang koma
  });

  it('diketik berurutan menghasilkan 0,5 dan 0,55', () => {
    let v = '';
    for (const ch of '055') v = autoKoma(v + ch);
    expect(v).toBe('0,55');
  });

  it('angka biasa tidak diubah', () => {
    expect(autoKoma('1')).toBe('1');
    expect(autoKoma('10')).toBe('10');
    expect(autoKoma('250')).toBe('250');
  });

  it('titik menjadi koma dan hanya satu koma', () => {
    expect(autoKoma('1.5')).toBe('1,5');
    expect(autoKoma('1,5,5')).toBe('1,55');
    expect(autoKoma('0,5')).toBe('0,5');
  });

  it('menolak huruf dan simbol', () => {
    expect(autoKoma('a1b,2c')).toBe('1,2');
    expect(autoKoma('-5')).toBe('5');
    expect(autoKoma(null)).toBe('');
  });

  it('menghapus nol di depan bila sudah ada koma', () => {
    expect(autoKoma('00,5')).toBe('0,5');
  });

  it('penghapusan karakter terakhir tetap wajar', () => {
    expect(autoKoma('0,')).toBe('0,');
    expect(autoKoma('0')).toBe('0');
  });
});

describe('konversi', () => {
  it('keAngka dan keTitik', () => {
    expect(keAngka('0,5')).toBe(0.5);
    expect(keAngka('12')).toBe(12);
    expect(Number.isNaN(keAngka('abc'))).toBe(true);
    expect(keTitik('0,5')).toBe('0.5');
  });

  it('soloAngka untuk rupiah', () => {
    expect(soloAngka('05000')).toBe('5000');
    expect(soloAngka('0')).toBe('0');
    expect(soloAngka('1.000,5')).toBe('10005');
    expect(soloAngka('abc')).toBe('');
  });
});
