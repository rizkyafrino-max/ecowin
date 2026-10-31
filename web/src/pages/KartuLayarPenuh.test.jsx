import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import App from '../App';
import { saveSession } from '../api/session';

vi.mock('../utils/navigate', () => ({
  LOGIN_URL: 'http://localhost:8000/masuk',
  REGISTER_URL: 'http://localhost:8000/daftar',
  GOOGLE_URL: 'http://localhost:8000/auth/google/redirect',
  goExternal: vi.fn(),
}));

const future = (ms) => new Date(Date.now() + ms).toISOString();
const json = (status, body) => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
const nasabah = { id: 1, nama: 'Siti Rahma', email: 'siti@gmail.com', role: 'nasabah', avatar: null, jenis_kelamin: 'P', punya_foto: false, nasabah: { nomor_nasabah: 'NSB-000001', no_hp: '0812', alamat_rt_rw: 'RT 1', saldo: 0, status_verifikasi: 'verified', bank_sampah: { nama: 'EcoWin RT 01', rt: '01', rw: '05' } } };

let qrGalat;

function mockApi() {
  vi.spyOn(globalThis, 'fetch').mockImplementation(async (url) => {
    const p = new URL(url).pathname.replace('/api', '');
    if (p === '/auth/me') return json(200, { data: nasabah });
    if (p === '/me/qr') return qrGalat ? json(500, { message: 'Gagal memuat kartu.' }) : json(200, { qr_png_base64: 'AAAA', nama: nasabah.nama, nomor_nasabah: 'NSB-000001' });
    if (p === '/notifikasi/jumlah') return json(200, { belum_dibaca: 0 });
    return json(404, {});
  });
}

const sesi = () => saveSession({ access_token: 'a', refresh_token: 'r', access_expires_at: future(3_600_000), refresh_expires_at: future(86_400_000) });

beforeEach(() => {
  localStorage.clear();
  sessionStorage.clear();
  vi.restoreAllMocks();
  qrGalat = false;
  sesi();
  window.history.pushState({}, '', '/kartu');
});

afterEach(() => {
  delete document.fullscreenEnabled;
  delete document.documentElement.requestFullscreen;
  delete navigator.wakeLock;
  delete window.screen.orientation;
});

describe('Kartu Nasabah layar penuh (web)', () => {
  it('menampilkan kartu berisi nama, nomor, bank, dan QR tanpa menu navigasi', async () => {
    mockApi();
    render(<App />);

    const kartu = await screen.findByLabelText('Kartu Nasabah');
    expect(kartu).toHaveTextContent('Siti Rahma');
    expect(kartu).toHaveTextContent('NSB-000001');
    expect(kartu).toHaveTextContent('EcoWin RT 01 · RT 01/RW 05');
    expect(screen.getByAltText('QR nasabah NSB-000001')).toBeInTheDocument();
    // layar penuh: tanpa sidebar/menu bawah
    expect(screen.queryByRole('link', { name: 'Beranda' })).toBeNull();
    expect(screen.queryByRole('navigation')).toBeNull();
    expect(screen.getByText(/Naikkan kecerahan layar/)).toBeInTheDocument();
  });

  it('tombol tutup kembali ke beranda bila tidak ada riwayat sebelumnya', async () => {
    mockApi();
    render(<App />);

    fireEvent.click(await screen.findByRole('button', { name: 'Tutup kartu' }));
    await waitFor(() => expect(window.location.pathname).toBe('/'));
  });

  it('menampilkan galat dengan tombol coba lagi bila kartu gagal dimuat', async () => {
    qrGalat = true;
    mockApi();
    render(<App />);

    expect(await screen.findByText(/Gagal memuat kartu/)).toBeInTheDocument();
    expect(screen.queryByLabelText('Kartu Nasabah')).toBeNull();
  });

  it('tombol layar penuh hanya muncul bila peramban mendukung, lalu mencoba mengunci tampilan melintang', async () => {
    mockApi();
    const { unmount } = render(<App />);
    await screen.findByLabelText('Kartu Nasabah');
    expect(screen.queryByRole('button', { name: 'Layar penuh' })).toBeNull();
    unmount();

    const minta = vi.fn(async () => {});
    const kunci = vi.fn(async () => {});
    Object.defineProperty(document, 'fullscreenEnabled', { value: true, configurable: true });
    document.documentElement.requestFullscreen = minta;
    Object.defineProperty(window.screen, 'orientation', { value: { lock: kunci, unlock: vi.fn() }, configurable: true });

    render(<App />);
    fireEvent.click(await screen.findByRole('button', { name: 'Layar penuh' }));
    await waitFor(() => expect(minta).toHaveBeenCalled());
    await waitFor(() => expect(kunci).toHaveBeenCalledWith('landscape'));
  });

  it('menahan layar tetap menyala selama kartu tampil dan melepasnya saat ditutup', async () => {
    mockApi();
    const lepas = vi.fn(async () => {});
    const minta = vi.fn(async () => ({ release: lepas }));
    Object.defineProperty(navigator, 'wakeLock', { value: { request: minta }, configurable: true });

    const { unmount } = render(<App />);
    await screen.findByLabelText('Kartu Nasabah');
    await waitFor(() => expect(minta).toHaveBeenCalledWith('screen'));

    unmount();
    await waitFor(() => expect(lepas).toHaveBeenCalled());
  });

  it('Profil, Semua Fitur, dan aksi cepat mengarah ke kartu layar penuh', async () => {
    mockApi();
    window.history.pushState({}, '', '/profil');
    const { unmount } = render(<App />);
    expect(await screen.findByRole('link', { name: /Tampilkan layar penuh/ })).toHaveAttribute('href', '/kartu');
    unmount();

    window.history.pushState({}, '', '/fitur');
    render(<App />);
    expect(await screen.findByRole('link', { name: /Kartu Nasabah/ })).toHaveAttribute('href', '/kartu');
  });

  it('tanpa sesi, kartu tidak bisa dibuka dan diarahkan ke halaman masuk', async () => {
    localStorage.clear();
    mockApi();
    render(<App />);

    await waitFor(() => expect(window.location.pathname).toBe('/masuk'));
    expect(screen.queryByLabelText('Kartu Nasabah')).toBeNull();
  });
});
