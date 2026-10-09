import { render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import App from './App';
import { saveSession } from './api/session';
import { createPkce } from './utils/pkce';

vi.mock('./utils/navigate', () => ({
  LOGIN_URL: 'http://localhost:8000/masuk',
  REGISTER_URL: 'http://localhost:8000/daftar',
  goExternal: vi.fn(),
}));
import { goExternal } from './utils/navigate';

const future = (ms) => new Date(Date.now() + ms).toISOString();
const json = (status, body) => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });

const nasabah = {
  id: 1, nama: 'Siti Rahma', email: 'siti@gmail.com', role: 'nasabah',
  nasabah: { nomor_nasabah: 'NSB-1', saldo: 5000, status_verifikasi: 'verified', bank_sampah: { nama: 'EcoWin RT 01', rt: '01', rw: '05' } },
};

function mockApi(user) {
  vi.spyOn(globalThis, 'fetch').mockImplementation(async (url) => {
    const p = new URL(url).pathname.replace('/api', '');
    if (p === '/auth/me') return json(200, { data: user });
    if (p === '/dashboard/summary') return json(200, { saldo: 5000, anorganik: { total_berat_kg: 1, jumlah_transaksi: 1 }, organik: { total_berat_kg: 0, estimasi_kompos_kg: 0, jumlah_aktivitas_biopori: 0, biopori_terverifikasi: 0, biopori_pending: 0 }, transaksi_terakhir: [], biopori_terakhir: [] });
    if (p === '/me/saldo') return json(200, { saldo: 5000, saldo_tersedia: 5000, saldo_ditahan: 0, total_pemasukan: 5000, total_penarikan: 0 });
    if (p === '/dashboard/statistics') return json(200, { labels: ['Okt 2026'], berat: [1], nilai: [] });
    return json(404, {});
  });
}

beforeEach(() => {
  localStorage.clear();
  sessionStorage.clear();
  vi.restoreAllMocks();
  goExternal.mockClear();
  window.history.pushState({}, '', '/');
});

describe('login satu pintu (halaman Laravel)', () => {
  it('tanpa sesi: diarahkan ke halaman login Laravel membawa PKCE challenge', async () => {
    render(<App />);
    await waitFor(() => expect(goExternal).toHaveBeenCalledTimes(1));

    const url = new URL(goExternal.mock.calls[0][0]);
    expect(url.origin + url.pathname).toBe('http://localhost:8000/masuk');
    expect(url.searchParams.get('app')).toBe('web');
    expect(url.searchParams.get('c')).toMatch(/^[A-Za-z0-9_-]{43}$/); // SHA-256 base64url
    expect(sessionStorage.getItem('ecowin.pkce')).toBeTruthy(); // verifier disimpan lokal, tidak dikirim
  });

  it('/daftar mengarahkan ke halaman pendaftaran Laravel', async () => {
    window.history.pushState({}, '', '/daftar');
    render(<App />);
    await waitFor(() => expect(goExternal).toHaveBeenCalled());
    expect(goExternal.mock.calls[0][0]).toMatch(/^http:\/\/localhost:8000\/daftar\?/);
  });

  it('callback: menukar kode + verifier menjadi sesi lalu masuk dashboard', async () => {
    const { verifier } = await createPkce();
    window.history.pushState({}, '', '/auth/callback?code=' + 'A'.repeat(64));
    const f = vi.spyOn(globalThis, 'fetch').mockImplementation(async (url, init) => {
      const p = new URL(url).pathname.replace('/api', '');
      if (p === '/auth/exchange') {
        expect(JSON.parse(init.body)).toEqual({ code: 'A'.repeat(64), verifier });
        return json(200, { access_token: 'A', access_expires_at: future(3_600_000), refresh_token: 'R', refresh_expires_at: future(86_400_000), user: nasabah });
      }
      if (p === '/auth/me') return json(200, { data: nasabah });
      if (p === '/dashboard/summary') return json(200, { saldo: 5000, anorganik: { total_berat_kg: 1, jumlah_transaksi: 1 }, organik: { total_berat_kg: 0, estimasi_kompos_kg: 0, jumlah_aktivitas_biopori: 0, biopori_terverifikasi: 0, biopori_pending: 0 }, transaksi_terakhir: [], biopori_terakhir: [] });
      if (p === '/me/saldo') return json(200, { saldo: 5000, saldo_tersedia: 5000, saldo_ditahan: 0, total_pemasukan: 5000, total_penarikan: 0 });
      if (p === '/dashboard/statistics') return json(200, { labels: ['Okt 2026'], berat: [1], nilai: [] });
      return json(404, {});
    });

    render(<App />);
    expect(await screen.findByRole('heading', { name: 'Siti Rahma' })).toBeInTheDocument();
    expect(window.location.pathname).toBe('/');
    expect(window.location.search).toBe(''); // kode tidak tertinggal di URL
    expect(f.mock.calls.filter(([u]) => String(u).endsWith('/auth/exchange'))).toHaveLength(1);
    expect(JSON.parse(localStorage.getItem('ecowin.session')).refresh_token).toBe('R');
  });

  it('callback tanpa verifier lokal (mis. tab lain) ditolak tanpa memanggil server', async () => {
    window.history.pushState({}, '', '/auth/callback?code=' + 'A'.repeat(64));
    const f = vi.spyOn(globalThis, 'fetch');
    render(<App />);
    expect(await screen.findByRole('alert')).toHaveTextContent('Sesi login tidak ditemukan');
    expect(f).not.toHaveBeenCalled();
  });

  it('callback dengan kode ditolak server: tampil pesan dan tidak ada sesi', async () => {
    await createPkce();
    window.history.pushState({}, '', '/auth/callback?code=' + 'B'.repeat(64));
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(json(401, { message: 'Kode login tidak valid atau sudah kedaluwarsa. Silakan masuk lagi.' }));
    render(<App />);
    expect(await screen.findByRole('alert')).toHaveTextContent('Kode login tidak valid');
    expect(localStorage.getItem('ecowin.session')).toBeNull();
  });

  it('akun non-nasabah tidak boleh dipakai di aplikasi nasabah', async () => {
    await createPkce();
    window.history.pushState({}, '', '/auth/callback?code=' + 'C'.repeat(64));
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(json(200, { access_token: 'A', access_expires_at: future(1e6), refresh_token: 'R', refresh_expires_at: future(1e8), user: { ...nasabah, role: 'petugas' } }));
    render(<App />);
    expect(await screen.findByRole('alert')).toHaveTextContent('masuk melalui dashboard');
    expect(localStorage.getItem('ecowin.session')).toBeNull();
  });
});

describe('sesi tersimpan', () => {
  it('sesi valid: langsung masuk dashboard tanpa login ulang', async () => {
    saveSession({ access_token: 'A', access_expires_at: future(3_600_000), refresh_token: 'R', refresh_expires_at: future(86_400_000) });
    mockApi(nasabah);
    render(<App />);
    expect(await screen.findByRole('heading', { name: 'Siti Rahma' })).toBeInTheDocument();
    expect(goExternal).not.toHaveBeenCalled();
  });

  it('akun non-nasabah dengan sesi tersimpan dibuang', async () => {
    saveSession({ access_token: 'A', access_expires_at: future(3_600_000), refresh_token: 'R', refresh_expires_at: future(86_400_000) });
    mockApi({ ...nasabah, role: 'petugas' });
    render(<App />);
    await waitFor(() => expect(localStorage.getItem('ecowin.session')).toBeNull());
  });

  it('refresh token kedaluwarsa: sesi dibuang dan diarahkan login', async () => {
    saveSession({ access_token: 'A', access_expires_at: future(-1000), refresh_token: 'R', refresh_expires_at: future(-1000) });
    render(<App />);
    await waitFor(() => expect(goExternal).toHaveBeenCalled());
    expect(localStorage.getItem('ecowin.session')).toBeNull();
  });

  it('akun menunggu verifikasi melihat banner dan tombol penarikan terkunci', async () => {
    saveSession({ access_token: 'A', access_expires_at: future(3_600_000), refresh_token: 'R', refresh_expires_at: future(86_400_000) });
    mockApi({ ...nasabah, nasabah: { ...nasabah.nasabah, status_verifikasi: 'pending' } });
    render(<App />);
    expect(await screen.findByText('Akun menunggu verifikasi petugas')).toBeInTheDocument();
  });
});
