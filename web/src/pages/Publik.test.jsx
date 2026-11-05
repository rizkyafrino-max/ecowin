import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import App from '../App';
import { KONTAK_EMAIL, NAMA_PENGEMBANG, URL_PORTOFOLIO } from '../utils/kontak';

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
    for (const judul of [/Siapa pengelola datamu/, /Data yang kami kumpulkan/, /Izin yang diminta aplikasi Android/, /Siapa saja yang bisa melihat datamu/, /Berapa lama data disimpan/, /Keamanan data/, /Hak kamu/, /Kontak/]) {
      expect(screen.getByRole('heading', { level: 2, name: judul })).toBeInTheDocument();
    }
    // Daftar isi menaut ke setiap bagian.
    expect(screen.getByRole('navigation', { name: 'Daftar isi' }).querySelectorAll('a[href^="#"]').length).toBeGreaterThanOrEqual(12);
    for (const tautan of screen.getAllByRole('link', { name: KONTAK_EMAIL })) expect(tautan).toHaveAttribute('href', `mailto:${KONTAK_EMAIL}`);
    expect(window.location.pathname).toBe('/privasi'); // tidak dialihkan ke halaman masuk
  });

  it('nama pengembang menaut ke portofolio dengan aman (tab baru, tanpa referrer)', async () => {
    window.history.pushState({}, '', '/privasi');
    render(<App />);
    await screen.findByRole('heading', { level: 1, name: 'Kebijakan Privasi' });
    const tautan = screen.getAllByRole('link', { name: NAMA_PENGEMBANG });
    expect(tautan.length).toBeGreaterThanOrEqual(2); // bagian pengelola, kontak, dan kaki halaman
    for (const a of tautan) {
      expect(a).toHaveAttribute('href', URL_PORTOFOLIO);
      expect(a).toHaveAttribute('target', '_blank');
      expect(a.getAttribute('rel')).toContain('noopener');
      expect(a.getAttribute('rel')).toContain('noreferrer');
    }
    // Setiap tautan keluar lain (kebijakan pihak ketiga) juga aman.
    for (const a of document.querySelectorAll('a[target="_blank"]')) expect(a.getAttribute('rel')).toContain('noopener');
  });

  it('/hapus-akun dapat dibuka tanpa login dan menjelaskan langkah serta syaratnya', async () => {
    window.history.pushState({}, '', '/hapus-akun');
    render(<App />);
    expect(await screen.findByRole('heading', { level: 1, name: 'Hapus Akun EcoWin' })).toBeInTheDocument();
    expect(screen.getAllByText(/Hapus akun saya/).length).toBeGreaterThan(0);
    expect(screen.getByText(/Saldomu harus/)).toBeInTheDocument();
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
