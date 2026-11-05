import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import App from '../App';
import { KONTAK_EMAIL } from '../utils/kontak';

vi.mock('../utils/navigate', () => ({
  LOGIN_URL: 'http://localhost:8000/masuk',
  REGISTER_URL: 'http://localhost:8000/daftar',
  GOOGLE_URL: 'http://localhost:8000/auth/google/redirect',
  goExternal: vi.fn(),
}));

beforeEach(() => {
  localStorage.clear();
  sessionStorage.clear();
  vi.restoreAllMocks();
  vi.spyOn(globalThis, 'fetch').mockImplementation(async () => new Response('{}', { status: 401 }));
});

describe('halaman publik', () => {
  it('/privasi dapat dibuka tanpa login dan memuat bagian wajib', async () => {
    window.history.pushState({}, '', '/privasi');
    render(<App />);
    expect(await screen.findByRole('heading', { level: 1, name: 'Kebijakan Privasi' })).toBeInTheDocument();
    for (const judul of [/Data yang kami kumpulkan/, /Siapa yang dapat melihat data Anda/, /Berapa lama data disimpan/, /Hak Anda/]) {
      expect(screen.getByRole('heading', { level: 2, name: judul })).toBeInTheDocument();
    }
    for (const tautan of screen.getAllByRole('link', { name: KONTAK_EMAIL })) expect(tautan).toHaveAttribute('href', `mailto:${KONTAK_EMAIL}`);
    expect(window.location.pathname).toBe('/privasi'); // tidak dialihkan ke halaman masuk
  });

  it('/hapus-akun dapat dibuka tanpa login dan menjelaskan langkah serta syaratnya', async () => {
    window.history.pushState({}, '', '/hapus-akun');
    render(<App />);
    expect(await screen.findByRole('heading', { level: 1, name: 'Hapus Akun EcoWin' })).toBeInTheDocument();
    expect(screen.getAllByText(/Hapus akun saya/).length).toBeGreaterThan(0);
    expect(screen.getByText(/Saldo Anda harus/)).toBeInTheDocument();
    for (const tautan of screen.getAllByRole('link', { name: 'Kebijakan Privasi' })) expect(tautan).toHaveAttribute('href', '/privasi');
    expect(window.location.pathname).toBe('/hapus-akun');
  });

  it('halaman masuk menautkan ke kebijakan privasi dan hapus akun', async () => {
    window.history.pushState({}, '', '/masuk');
    render(<App />);
    const privasi = await screen.findByRole('link', { name: 'Kebijakan Privasi' });
    expect(privasi).toHaveAttribute('href', '/privasi');
    expect(screen.getByRole('link', { name: 'Hapus akun' })).toHaveAttribute('href', '/hapus-akun');
    fireEvent.click(privasi);
    expect(await screen.findByRole('heading', { level: 1, name: 'Kebijakan Privasi' })).toBeInTheDocument();
  });
});
