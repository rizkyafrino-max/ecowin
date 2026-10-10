import { beforeEach, describe, expect, it, vi } from 'vitest';
import { api, setSessionLostHandler } from './client';
import { loadSession, saveSession } from './session';

const future = (ms) => new Date(Date.now() + ms).toISOString();
const json = (status, body) => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });

function seed(overrides = {}) {
  saveSession({
    access_token: 'A1', access_expires_at: future(3_600_000),
    refresh_token: 'R1', refresh_expires_at: future(86_400_000), ...overrides,
  });
}

beforeEach(() => {
  localStorage.clear();
  vi.restoreAllMocks();
  setSessionLostHandler(() => {});
});

describe('api client', () => {
  it('mengirim Bearer access token', async () => {
    seed();
    const f = vi.spyOn(globalThis, 'fetch').mockResolvedValue(json(200, { ok: true }));
    await api('/auth/me');
    expect(f.mock.calls[0][1].headers.Authorization).toBe('Bearer A1');
  });

  it('refresh otomatis saat access token kedaluwarsa, hanya sekali untuk banyak request', async () => {
    seed({ access_expires_at: future(-1000) });
    const f = vi.spyOn(globalThis, 'fetch').mockImplementation(async (url, init) => {
      if (String(url).endsWith('/auth/refresh')) {
        expect(init.headers.Authorization).toBe('Bearer R1');
        return json(200, { access_token: 'A2', access_expires_at: future(3_600_000), refresh_token: 'R2', refresh_expires_at: future(86_400_000) });
      }
      return json(200, { token: init.headers.Authorization });
    });

    const [a, b] = await Promise.all([api('/x'), api('/y')]);
    expect(a.token).toBe('Bearer A2');
    expect(b.token).toBe('Bearer A2');
    expect(f.mock.calls.filter(([u]) => String(u).endsWith('/auth/refresh'))).toHaveLength(1);
    expect(loadSession().refresh_token).toBe('R2'); // refresh token dirotasi
  });

  it('401 dari server memicu refresh lalu mengulang request satu kali', async () => {
    seed();
    let first = true;
    vi.spyOn(globalThis, 'fetch').mockImplementation(async (url, init) => {
      if (String(url).endsWith('/auth/refresh')) return json(200, { access_token: 'A2', access_expires_at: future(3_600_000), refresh_token: 'R2', refresh_expires_at: future(86_400_000) });
      if (first) { first = false; return json(401, { message: 'Unauthenticated.' }); }
      return json(200, { auth: init.headers.Authorization });
    });
    expect((await api('/me')).auth).toBe('Bearer A2');
  });

  it('sesi dihapus dan handler dipanggil bila refresh ditolak (akun dicabut/logout)', async () => {
    seed({ access_expires_at: future(-1000) });
    const lost = vi.fn();
    setSessionLostHandler(lost);
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(json(401, { message: 'Unauthenticated.' }));

    await expect(api('/me')).rejects.toMatchObject({ status: 401 });
    expect(loadSession()).toBeNull();
    expect(lost).toHaveBeenCalled();
  });

  it('galat validasi 422 membawa pesan per field', async () => {
    seed();
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(json(422, { message: 'Data tidak valid.', errors: { jumlah: ['Saldo tidak cukup.'] } }));
    await expect(api('/penarikan', { method: 'POST', body: { jumlah: 1 } })).rejects.toMatchObject({ status: 422, errors: { jumlah: ['Saldo tidak cukup.'] } });
  });
});
