import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import App from '../App';
import { saveSession } from '../api/session';
import { akunTerkunci } from '../utils/verifikasi';

vi.mock('../utils/navigate', () => ({
  LOGIN_URL: 'http://localhost:8000/masuk',
  REGISTER_URL: 'http://localhost:8000/daftar',
  GOOGLE_URL: 'http://localhost:8000/auth/google/redirect',
  goExternal: vi.fn(),
}));

const future = (ms) => new Date(Date.now() + ms).toISOString();
const json = (status, body) => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
const pengguna = (status) => ({
  id: 1, nama: 'Siti Rahma', email: 'siti@gmail.com', role: 'nasabah', avatar: null, jenis_kelamin: 'P', punya_foto: false,
  nasabah: { nomor_nasabah: 'NSB-000001', no_hp: '0812', alamat_rt_rw: 'RT 1', saldo: 0, status_verifikasi: status, bank_sampah: { nama: 'EcoWin RT 01', rt: '01', rw: '05' } },
});

let status; // status verifikasi yang dijawab server
let dipanggil; // jalur API yang diminta aplikasi

function mockApi() {
  vi.spyOn(globalThis, 'fetch').mockImplementation(async (url) => {
    const p = new URL(url).pathname.replace('/api', '');
    dipanggil.push(p);
    if (p === '/auth/me') return json(200, { data: pengguna(status) });
    if (p === '/notifikasi/jumlah') return json(200, { belum_dibaca: 0 });
    if (p === '/me/qr') return json(200, { qr_png_base64: 'AAAA', nama: 'Siti Rahma', nomor_nasabah: 'NSB-000001' });
    if (p === '/dashboard/summary') return json(200, { saldo: 5000, anorganik: { total_berat_kg: 1, jumlah_transaksi: 1 }, organik: { total_berat_kg: 0, estimasi_kompos_kg: 0, jumlah_aktivitas_biopori: 0, biopori_terverifikasi: 0, biopori_pending: 0 }, transaksi_terakhir: [], biopori_terakhir: [] });
    if (p === '/me/saldo') return json(200, { saldo: 5000, saldo_tersedia: 5000, saldo_ditahan: 0, total_pemasukan: 5000, total_penarikan: 0 });
    if (p === '/dashboard/statistics') return json(200, { labels: ['Nov 2026'], berat: [1], nilai: [] });
    return json(404, {});
  });
}

const buka = (jalur) => { window.history.pushState({}, '', jalur); return render(<App />); };

beforeEach(() => {
  localStorage.clear();
  sessionStorage.clear();
  vi.restoreAllMocks();
  status = 'pending';
  dipanggil = [];
  mockApi();
  saveSession({ access_token: 'a', refresh_token: 'r', access_expires_at: future(3_600_000), refresh_expires_at: future(86_400_000) });
});
afterEach(() => vi.useRealTimers());

describe('akunTerkunci', () => {
  it('hanya nasabah yang belum terverifikasi yang terkunci; status kosong dianggap belum diverifikasi', () => {
    expect(akunTerkunci(pengguna('pending'))).toBe(true);
    expect(akunTerkunci(pengguna('verified'))).toBe(false);
    expect(akunTerkunci(pengguna(undefined))).toBe(true);
    expect(akunTerkunci({ role: 'nasabah', nasabah: null })).toBe(true);
    expect(akunTerkunci({ role: 'petugas', nasabah: null })).toBe(false);
    expect(akunTerkunci(null)).toBe(false);
  });
});

describe('gerbang verifikasi nasabah', () => {
  it.each([['/', 'Beranda'], ['/transaksi', 'Transaksi'], ['/organik', 'Organik'], ['/saldo', 'Saldo'], ['/penarikan', 'Tarik saldo'], ['/kartu', 'Kartu']])(
    '%s disensor: layar terkunci tampil dan tidak ada data yang dimuat (%s)',
    async (jalur) => {
      buka(jalur);
      expect(await screen.findByRole('heading', { name: 'Halaman ini terkunci' })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /Periksa status/ })).toBeInTheDocument();
      // Tidak satu pun jalur data diminta: yang boleh hanya status akun dan jumlah notifikasi.
      const terlarang = dipanggil.filter((p) => !['/auth/me', '/notifikasi/jumlah'].includes(p));
      expect(terlarang).toEqual([]);
    },
  );

  it('Profil, Semua Fitur, dan Lapor bug tetap bisa dibuka', async () => {
    buka('/profil');
    expect(await screen.findByText('Data diri')).toBeInTheDocument();
    expect(screen.queryByRole('heading', { name: 'Halaman ini terkunci' })).toBeNull();
  });

  it('Semua Fitur dan Lapor bug juga tidak terkunci', async () => {
    buka('/fitur');
    expect(await screen.findByText('Semua yang bisa kamu lakukan di EcoWin.')).toBeInTheDocument();
    expect(screen.queryByRole('heading', { name: 'Halaman ini terkunci' })).toBeNull();
  });

  it('tombol Periksa status membuka halaman setelah petugas memverifikasi', async () => {
    buka('/');
    await screen.findByRole('heading', { name: 'Halaman ini terkunci' });

    // Masih menunggu: pesan muncul dan halaman tetap terkunci.
    fireEvent.click(screen.getByRole('button', { name: /Periksa status/ }));
    expect(await screen.findByText(/belum diverifikasi/i)).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Halaman ini terkunci' })).toBeInTheDocument();

    // Petugas memverifikasi: tekan lagi, kunci terbuka dan data dimuat.
    status = 'verified';
    fireEvent.click(screen.getByRole('button', { name: /Periksa status/ }));
    await waitFor(() => expect(screen.queryByRole('heading', { name: 'Halaman ini terkunci' })).toBeNull());
    await waitFor(() => expect(dipanggil).toContain('/dashboard/summary'));
  });

  it('nasabah terverifikasi langsung melihat halaman, tanpa layar terkunci', async () => {
    status = 'verified';
    buka('/saldo');
    await waitFor(() => expect(dipanggil).toContain('/me/saldo'));
    expect(screen.queryByRole('heading', { name: 'Halaman ini terkunci' })).toBeNull();
  });
});
