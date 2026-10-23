import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
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
const nasabah = { id: 1, nama: 'Siti Rahma', email: 'siti@gmail.com', role: 'nasabah', nasabah: { nomor_nasabah: 'NSB-1', saldo: 5000, status_verifikasi: 'verified', bank_sampah: { nama: 'EcoWin RT 01', rt: '01', rw: '05' } } };

let notif;
let dibacaId;
let bacaSemua;
let panggilanHapus;

function mockApi() {
  vi.spyOn(globalThis, 'fetch').mockImplementation(async (url, init) => {
    const p = new URL(url).pathname.replace('/api', '');
    if (p === '/auth/me') return json(200, { data: nasabah });
    if (p === '/notifikasi/jumlah') return json(200, { belum_dibaca: notif.filter((n) => !n.dibaca).length });
    if (p === '/notifikasi' && init?.method !== 'DELETE') return json(200, { data: notif, belum_dibaca: notif.filter((n) => !n.dibaca).length });
    if (p === '/notifikasi/baca-semua' && init?.method === 'POST') { bacaSemua = true; notif = notif.map((n) => ({ ...n, dibaca: true })); return json(200, { belum_dibaca: 0 }); }
    if (p === '/notifikasi' && init?.method === 'DELETE') {
      const hanyaDibaca = new URL(url).searchParams.get('hanya_dibaca') === '1';
      panggilanHapus.push(hanyaDibaca ? 'dibaca' : 'semua');
      notif = hanyaDibaca ? notif.filter((n) => !n.dibaca) : [];
      return json(200, { dihapus: 1, belum_dibaca: notif.filter((n) => !n.dibaca).length });
    }
    const hp = p.match(/^\/notifikasi\/(\d+)$/);
    if (hp && init?.method === 'DELETE') { panggilanHapus.push(Number(hp[1])); notif = notif.filter((n) => n.id !== Number(hp[1])); return json(200, { belum_dibaca: notif.filter((n) => !n.dibaca).length }); }
    const m = p.match(/^\/notifikasi\/(\d+)\/baca$/);
    if (m && init?.method === 'POST') { dibacaId = Number(m[1]); notif = notif.map((n) => (n.id === dibacaId ? { ...n, dibaca: true } : n)); return json(200, { belum_dibaca: notif.filter((n) => !n.dibaca).length }); }
    if (p === '/me/saldo') return json(200, { saldo: 5000, saldo_tersedia: 5000, saldo_ditahan: 0, total_pemasukan: 5000, total_penarikan: 0 });
    if (p === '/penarikan') return json(200, { data: [], links: {}, meta: { current_page: 1, last_page: 1 } });
    if (p === '/me/mutasi-saldo') return json(200, { data: [], links: {}, meta: { current_page: 1, last_page: 1 } });
    return json(404, {});
  });
}

beforeEach(() => {
  localStorage.clear();
  sessionStorage.clear();
  vi.restoreAllMocks();
  dibacaId = null;
  bacaSemua = false;
  panggilanHapus = [];
  notif = [
    { id: 2, tipe: 'penarikan_disetujui', judul: 'Penarikan disetujui', isi: 'Penarikan Rp 5.000 disetujui.', aksi: 'saldo', dibaca: false, created_at: new Date().toISOString() },
    { id: 1, tipe: 'setoran_anorganik', judul: 'Setoran tercatat', isi: '3,00 kg Kardus bernilai Rp 6.000.', aksi: 'transaksi', dibaca: true, created_at: new Date().toISOString() },
  ];
  saveSession({ access_token: 'a', refresh_token: 'r', access_expires_at: future(3_600_000), refresh_expires_at: future(86_400_000) });
  window.history.pushState({}, '', '/notifikasi');
});

describe('Notifikasi nasabah', () => {
  it('menampilkan daftar, lencana belum dibaca di bel, dan menandai dibaca saat dibuka', async () => {
    mockApi();
    render(<App />);

    expect(await screen.findByText('Penarikan disetujui')).toBeInTheDocument();
    expect(screen.getByText('Setoran tercatat')).toBeInTheDocument();
    expect(screen.getByText('Baru')).toBeInTheDocument();
    await waitFor(() => expect(screen.getByLabelText('Notifikasi, 1 belum dibaca')).toBeInTheDocument());

    fireEvent.click(screen.getByText('Penarikan disetujui'));
    await waitFor(() => expect(dibacaId).toBe(2));
    // aksi "saldo" mengarahkan ke halaman Saldo
    await waitFor(() => expect(window.location.pathname).toBe('/saldo'));
  });

  it('tombol "Tandai dibaca" membaca semua', async () => {
    mockApi();
    render(<App />);

    fireEvent.click(await screen.findByRole('button', { name: /Tandai dibaca/ }));
    await waitFor(() => expect(bacaSemua).toBe(true));
    await waitFor(() => expect(screen.queryByText('Baru')).toBeNull());
  });

  it('menghapus satu notifikasi lewat tombol hapus', async () => {
    mockApi();
    render(<App />);

    fireEvent.click(await screen.findByRole('button', { name: /Hapus notifikasi: Setoran tercatat/ }));
    await waitFor(() => expect(panggilanHapus).toEqual([1]));
    await waitFor(() => expect(screen.queryByText('Setoran tercatat')).toBeNull());
    expect(screen.getByText('Penarikan disetujui')).toBeInTheDocument();
  });

  it('hapus yang sudah dibaca meminta konfirmasi dan hanya menghapus yang sudah dibaca', async () => {
    mockApi();
    render(<App />);

    fireEvent.click(await screen.findByRole('button', { name: /Hapus yang sudah dibaca/ }));
    expect(screen.getByRole('alertdialog')).toBeInTheDocument();
    expect(panggilanHapus).toEqual([]); // belum dihapus sebelum dikonfirmasi
    fireEvent.click(screen.getByRole('button', { name: 'Ya, hapus' }));
    await waitFor(() => expect(panggilanHapus).toEqual(['dibaca']));
    await waitFor(() => expect(screen.queryByText('Setoran tercatat')).toBeNull());
    expect(screen.getByText('Penarikan disetujui')).toBeInTheDocument();
  });

  it('hapus semua bisa dibatalkan, dan bila dikonfirmasi mengosongkan daftar', async () => {
    mockApi();
    render(<App />);

    fireEvent.click(await screen.findByRole('button', { name: 'Hapus semua' }));
    fireEvent.click(screen.getByRole('button', { name: 'Batal' }));
    expect(screen.queryByRole('alertdialog')).toBeNull();
    expect(panggilanHapus).toEqual([]);

    fireEvent.click(screen.getByRole('button', { name: 'Hapus semua' }));
    fireEvent.click(screen.getByRole('button', { name: 'Ya, hapus' }));
    await waitFor(() => expect(panggilanHapus).toEqual(['semua']));
    expect(await screen.findByText('Belum ada notifikasi')).toBeInTheDocument();
  });

  it('keadaan kosong menampilkan pesan yang jelas', async () => {
    notif = [];
    mockApi();
    render(<App />);

    expect(await screen.findByText('Belum ada notifikasi')).toBeInTheDocument();
  });
});
