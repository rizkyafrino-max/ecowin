import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { buatKartuPng, namaBerkasKartu, unduhBlob } from './kartuPng';
import { ringkasBank } from './kartu';

// jsdom tidak punya kanvas: dipalsukan agar bisa memeriksa apa saja yang digambar.
let teks;
let panggilan;

function pasangKanvasPalsu() {
  teks = [];
  panggilan = [];
  const ctx = new Proxy({}, {
    get: (t, k) => {
      if (k in t) return t[k];
      if (k === 'measureText') return (s) => ({ width: String(s).length * 20 });
      if (k === 'createLinearGradient') return () => ({ addColorStop() {} });
      if (k === 'fillText') return (s) => teks.push(String(s));
      return (...a) => { panggilan.push([k, ...a]); };
    },
    set: (t, k, v) => { t[k] = v; return true; },
  });
  const kanvas = { width: 0, height: 0, getContext: () => ctx, toBlob: (cb) => cb(new Blob(['png'], { type: 'image/png' })) };
  const asli = document.createElement.bind(document);
  vi.spyOn(document, 'createElement').mockImplementation((tag, o) => (tag === 'canvas' ? kanvas : asli(tag, o)));
  vi.stubGlobal('Path2D', class {});
  vi.stubGlobal('Image', class {
    constructor() { this.width = 212; this.height = 248; }
    set src(v) { this._src = v; queueMicrotask(() => (v.includes('gagal') ? this.onerror?.() : this.onload?.())); }
  });
  return kanvas;
}

beforeEach(() => { vi.restoreAllMocks(); });
afterEach(() => { vi.unstubAllGlobals(); });

describe('buatKartuPng', () => {
  it('menggambar semua isi kartu dan menghasilkan PNG berukuran 1712 x 1080', async () => {
    const kanvas = pasangKanvasPalsu();

    const blob = await buatKartuPng({ nama: 'Siti Rahma', nomor: 'NSB-000001', bank: 'EcoWin RT 01 · RT 01/RW 05', qr: 'data:image/png;base64,AAAA', emblem: '/emblem.png' });

    expect(blob.type).toBe('image/png');
    expect([kanvas.width, kanvas.height]).toEqual([1712, 1080]);
    for (const t of ['Eco', 'Win', 'BANK SAMPAH DIGITAL', 'KARTU NASABAH', 'Siti Rahma', 'NSB-000001', 'EcoWin RT 01 · RT 01/RW 05', 'PINDAI OLEH PETUGAS']) {
      expect(teks).toContain(t);
    }
    // QR digambar tanpa penghalusan supaya tetap tajam
    expect(panggilan.some(([k]) => k === 'drawImage')).toBe(true);
  });

  it('memotong nama yang sangat panjang menjadi paling banyak dua baris', async () => {
    pasangKanvasPalsu();
    const panjang = 'Muhammad Abdurrahman Al Fatih bin Sulaiman Hakim Nugroho Pratama Wibisono Santoso';

    await buatKartuPng({ nama: panjang, nomor: 'NSB-000002', bank: 'EcoWin', qr: 'data:image/png;base64,AAAA', emblem: '/emblem.png' });

    const baris = teks.filter((t) => panjang.startsWith(t.replace('…', '').slice(0, 8)) && t.length > 8);
    expect(baris.length).toBeLessThanOrEqual(2);
    expect(teks.some((t) => t.endsWith('…'))).toBe(true);
  });

  it('memotong satu kata yang lebih lebar dari kartu supaya tidak keluar dari kartu', async () => {
    pasangKanvasPalsu();
    const satuKata = 'A'.repeat(70); // 70 x 20 = 1400 satuan, lebih lebar dari area teks (771)

    await buatKartuPng({ nama: satuKata, nomor: 'NSB-000003', bank: 'EcoWin', qr: 'data:image/png;base64,AAAA', emblem: '/emblem.png' });

    const nama = teks.find((t) => t.startsWith('AAAA'));
    expect(nama.endsWith('…')).toBe(true);
    expect(nama.length * 20).toBeLessThanOrEqual(771);
  });

  it('gagal dengan pesan jelas bila gambar tidak bisa dimuat', async () => {
    pasangKanvasPalsu();

    await expect(buatKartuPng({ nama: 'A', nomor: 'N', bank: 'B', qr: 'gagal.png', emblem: '/emblem.png' })).rejects.toThrow('Gambar kartu gagal dimuat.');
  });
});

describe('namaBerkasKartu dan ringkasBank', () => {
  it('nama berkas hanya memuat huruf, angka, dan strip', () => {
    expect(namaBerkasKartu('NSB-000001')).toBe('Kartu-EcoWin-NSB-000001.png');
    expect(namaBerkasKartu('../../etc/passwd<>')).toBe('Kartu-EcoWin-etcpasswd.png');
    expect(namaBerkasKartu(null)).toBe('Kartu-EcoWin-nasabah.png');
  });

  it('ringkasan bank memuat nama dan RT/RW bila ada', () => {
    expect(ringkasBank({ bank_sampah: { nama: 'EcoWin RT 01', rt: '01', rw: '05' } })).toBe('EcoWin RT 01 · RT 01/RW 05');
    expect(ringkasBank({ bank_sampah: { nama: 'EcoWin RT 01' } })).toBe('EcoWin RT 01');
    expect(ringkasBank(undefined)).toBe('');
  });
});

describe('unduhBlob', () => {
  it('membuat tautan unduhan lalu melepas alamat sementara', () => {
    vi.useFakeTimers();
    const buat = vi.fn(() => 'blob:abc');
    const lepas = vi.fn();
    URL.createObjectURL = buat;
    URL.revokeObjectURL = lepas;
    const klik = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function klikPalsu() { expect(this.download).toBe('k.png'); });

    unduhBlob(new Blob(['x']), 'k.png');

    expect(klik).toHaveBeenCalled();
    vi.advanceTimersByTime(2100);
    expect(lepas).toHaveBeenCalledWith('blob:abc');
    vi.useRealTimers();
  });
});
