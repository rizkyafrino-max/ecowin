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
// Pengecilan memakai canvas (tidak ada di jsdom): diganti blob kecil. Perilaku aslinya diuji terpisah di foto.test.js.
vi.mock('../utils/foto', () => ({ perkecilFoto: vi.fn(async () => new Blob(['jpeg'], { type: 'image/jpeg' })) }));

const future = (ms) => new Date(Date.now() + ms).toISOString();
const json = (status, body) => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
const nasabah = { id: 1, nama: 'Siti Rahma', email: 'siti@gmail.com', role: 'nasabah', avatar: null, jenis_kelamin: 'P', punya_foto: false, nasabah: { nomor_nasabah: 'NSB-1', no_hp: '0812', alamat_rt_rw: 'RT 1', saldo: 0, status_verifikasi: 'verified', bank_sampah: { nama: 'EcoWin RT 01', rt: '01', rw: '05' } } };

let kiriman;
let balasan;

function mockApi() {
  vi.spyOn(globalThis, 'fetch').mockImplementation(async (url, init) => {
    const p = new URL(url).pathname.replace('/api', '');
    if (p === '/auth/me') return json(200, { data: nasabah });
    if (p === '/qr' || p === '/me/qr') return json(200, { qr_png_base64: '', nama: nasabah.nama, nomor_nasabah: 'NSB-1' });
    if (p === '/notifikasi/jumlah') return json(200, { belum_dibaca: 0 });
    if (p === '/laporan-kendala' && init?.method === 'POST') {
      kiriman.push(init.body);
      return balasan();
    }
    return json(404, {});
  });
}

beforeEach(() => {
  localStorage.clear();
  sessionStorage.clear();
  vi.restoreAllMocks();
  kiriman = [];
  balasan = () => json(201, { id: 9, punya_foto: false });
  saveSession({ access_token: 'a', refresh_token: 'r', access_expires_at: future(3_600_000), refresh_expires_at: future(86_400_000) });
  window.history.pushState({}, '', '/lapor-bug');
});

const tulis = async (teks) => fireEvent.change(await screen.findByLabelText('Apa masalahnya?'), { target: { value: teks } });

describe('Lapor bug (web)', () => {
  it('tombol kirim baru aktif setelah minimal 10 karakter', async () => {
    mockApi();
    render(<App />);

    const kirim = await screen.findByRole('button', { name: /Kirim laporan/ });
    expect(kirim).toBeDisabled();
    await tulis('pendek');
    expect(kirim).toBeDisabled();
    await tulis('Layar putih saat Tarik Saldo');
    expect(kirim).toBeEnabled();
  });

  it('mengirim kategori bug, teks, dan info teknis tanpa foto', async () => {
    mockApi();
    render(<App />);

    await tulis('Layar putih saat Tarik Saldo');
    fireEvent.click(screen.getByRole('button', { name: /Kirim laporan/ }));

    expect(await screen.findByText('Terima kasih, laporan terkirim')).toBeInTheDocument();
    const fd = kiriman[0];
    expect(fd).toBeInstanceOf(FormData);
    expect(fd.get('kategori')).toBe('bug');
    expect(fd.get('deskripsi')).toMatch(/^Layar putih saat Tarik Saldo\n\n— Info otomatis —\nLayar: Web\nEcoWin Web/);
    expect(fd.get('deskripsi').length).toBeLessThanOrEqual(2000);
    expect(fd.has('foto')).toBe(false);
  });

  it('menyertakan foto bukti sebagai JPEG dan bisa dihapus sebelum dikirim', async () => {
    mockApi();
    render(<App />);

    await tulis('Layar putih saat Tarik Saldo');
    fireEvent.change(await screen.findByTestId('input-foto-bug'), { target: { files: [new File(['x'], 'layar.png', { type: 'image/png' })] } });
    const hapus = await screen.findByRole('button', { name: 'Hapus foto' });

    fireEvent.click(hapus);
    expect(screen.queryByRole('button', { name: 'Hapus foto' })).toBeNull();

    fireEvent.change(screen.getByTestId('input-foto-bug'), { target: { files: [new File(['x'], 'layar.png', { type: 'image/png' })] } });
    await screen.findByRole('button', { name: 'Hapus foto' });
    fireEvent.click(screen.getByRole('button', { name: /Kirim laporan/ }));

    await screen.findByText('Terima kasih, laporan terkirim');
    const foto = kiriman[0].get('foto');
    expect(foto).toBeInstanceOf(Blob);
    expect(foto.type).toBe('image/jpeg');
  });

  it('menampilkan galat dari server (foto ditolak) dan tetap di formulir', async () => {
    balasan = () => json(422, { message: 'Foto tidak valid.', errors: { foto: ['Berkas harus berupa gambar JPEG, PNG, atau WebP.'] } });
    mockApi();
    render(<App />);

    await tulis('Layar putih saat Tarik Saldo');
    fireEvent.click(screen.getByRole('button', { name: /Kirim laporan/ }));

    expect(await screen.findByRole('alert')).toHaveTextContent('Berkas harus berupa gambar');
    expect(screen.queryByText('Terima kasih, laporan terkirim')).toBeNull();
    expect(screen.getByLabelText('Apa masalahnya?')).toHaveValue('Layar putih saat Tarik Saldo');
  });

  it('menampilkan pesan saat terlalu sering melapor (429)', async () => {
    balasan = () => json(429, { message: 'Too Many Attempts.' });
    mockApi();
    render(<App />);

    await tulis('Layar putih saat Tarik Saldo');
    fireEvent.click(screen.getByRole('button', { name: /Kirim laporan/ }));

    await waitFor(() => expect(screen.getByRole('alert')).toBeInTheDocument());
  });

  it('Profil menautkan ke Lapor bug dan ke situs pembuat (tab baru, aman)', async () => {
    mockApi();
    window.history.pushState({}, '', '/profil');
    render(<App />);

    const lapor = await screen.findByRole('link', { name: /Lapor bug/ });
    expect(lapor).toHaveAttribute('href', '/lapor-bug');
    const pembuat = screen.getByRole('link', { name: /Dibuat oleh Rizky/ });
    expect(pembuat).toHaveAttribute('href', 'https://rizky.pplgku.my.id/');
    expect(pembuat).toHaveAttribute('target', '_blank');
    expect(pembuat).toHaveAttribute('rel', expect.stringContaining('noopener'));
    expect(screen.getByLabelText('Kartu Nasabah')).toBeInTheDocument();
  });
});
