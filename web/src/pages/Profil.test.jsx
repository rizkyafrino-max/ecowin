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
const dasar = { id: 1, nama: 'Siti Rahma', email: 'siti@gmail.com', role: 'nasabah', avatar: 'http://localhost:8000/images/avatar-perempuan.jpg', jenis_kelamin: 'P', punya_foto: false, nasabah: { nomor_nasabah: 'NSB-1', no_hp: '081234567890', alamat_rt_rw: 'RT 1', saldo: 5000, status_verifikasi: 'verified', bank_sampah: { nama: 'EcoWin RT 01', rt: '01', rw: '05' } } };

let user;
let panggilan;
let galatFoto;

function mockApi() {
  vi.spyOn(globalThis, 'fetch').mockImplementation(async (url, init) => {
    const p = new URL(url).pathname.replace('/api', '');
    const metode = init?.method || 'GET';
    if (p === '/auth/me') return json(200, { data: user });
    if (p === '/qr') return json(200, { qr_png_base64: '', nama: user.nama, nomor_nasabah: 'NSB-1' });
    if (p === '/profile' && metode === 'PATCH') {
      const body = JSON.parse(init.body);
      panggilan.push(['PATCH', body]);
      user = { ...user, jenis_kelamin: body.jenis_kelamin, avatar: body.jenis_kelamin === 'L' ? 'http://localhost:8000/images/avatar-laki.jpg' : user.avatar };
      return json(200, { data: user });
    }
    if (p === '/profile/foto' && metode === 'POST') {
      panggilan.push(['POST-foto', init.body instanceof FormData ? 'FormData' : typeof init.body, init.body.get?.('foto')?.type]);
      if (galatFoto) return json(422, { message: 'Foto tidak valid.', errors: { foto: ['Foto terlalu kecil (minimal 64 x 64 piksel).'] } });
      user = { ...user, punya_foto: true, avatar: 'http://localhost:8000/foto/abc' };
      return json(200, { data: user });
    }
    if (p === '/profile/foto' && metode === 'DELETE') {
      panggilan.push(['DELETE-foto']);
      user = { ...user, punya_foto: false, avatar: 'http://localhost:8000/images/avatar-perempuan.jpg' };
      return json(200, { data: user });
    }
    if (p === '/notifikasi/jumlah') return json(200, { belum_dibaca: 0 });
    return json(404, {});
  });
}

beforeEach(() => {
  localStorage.clear();
  sessionStorage.clear();
  vi.restoreAllMocks();
  user = { ...dasar };
  panggilan = [];
  galatFoto = false;
  saveSession({ access_token: 'a', refresh_token: 'r', access_expires_at: future(3_600_000), refresh_expires_at: future(86_400_000) });
  window.history.pushState({}, '', '/profil');
});

describe('Profil: foto dan jenis kelamin', () => {
  it('menampilkan avatar dari API dan pilihan jenis kelamin sesuai akun', async () => {
    mockApi();
    render(<App />);

    const foto = await screen.findByAltText('Foto profil');
    expect(foto).toHaveAttribute('src', 'http://localhost:8000/images/avatar-perempuan.jpg');
    expect(screen.getByRole('radio', { name: /Perempuan/ })).toBeChecked();
    expect(screen.getByRole('radio', { name: /Laki-laki/ })).not.toBeChecked();
    expect(screen.getByText(/Avatar bawaan/)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /Hapus foto/ })).toBeNull();
  });

  it('mengganti jenis kelamin menyimpannya dan avatar bawaan ikut berubah', async () => {
    mockApi();
    render(<App />);

    fireEvent.click(await screen.findByRole('radio', { name: /Laki-laki/ }));
    fireEvent.click(screen.getByRole('button', { name: /Simpan perubahan/ }));

    await waitFor(() => expect(panggilan.find((c) => c[0] === 'PATCH')?.[1].jenis_kelamin).toBe('L'));
    await waitFor(() => expect(screen.getByAltText('Foto profil')).toHaveAttribute('src', 'http://localhost:8000/images/avatar-laki.jpg'));
  });

  it('mengunggah foto sendiri (FormData JPEG) lalu bisa menghapusnya kembali ke avatar bawaan', async () => {
    mockApi();
    render(<App />);

    const input = await screen.findByTestId('input-foto');
    fireEvent.change(input, { target: { files: [new File(['x'], 'saya.png', { type: 'image/png' })] } });

    await waitFor(() => expect(panggilan).toContainEqual(['POST-foto', 'FormData', 'image/jpeg']));
    await waitFor(() => expect(screen.getByAltText('Foto profil')).toHaveAttribute('src', 'http://localhost:8000/foto/abc'));
    expect(screen.getByText(/Foto sendiri/)).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: /Hapus foto/ }));
    await waitFor(() => expect(panggilan).toContainEqual(['DELETE-foto']));
    await waitFor(() => expect(screen.getByAltText('Foto profil')).toHaveAttribute('src', 'http://localhost:8000/images/avatar-perempuan.jpg'));
  });

  it('menampilkan pesan galat dari server saat foto ditolak dan tidak mengubah foto', async () => {
    galatFoto = true;
    mockApi();
    render(<App />);

    fireEvent.change(await screen.findByTestId('input-foto'), { target: { files: [new File(['x'], 'kecil.jpg', { type: 'image/jpeg' })] } });

    expect(await screen.findByRole('alert')).toHaveTextContent('Foto terlalu kecil');
    expect(screen.getByAltText('Foto profil')).toHaveAttribute('src', 'http://localhost:8000/images/avatar-perempuan.jpg');
  });
});
