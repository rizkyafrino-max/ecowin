import { clearSession, isExpired, loadSession, saveSession } from './session';

export const API_BASE = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api').replace(/\/+$/, '');

export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

let onSessionLost = () => {};
export const setSessionLostHandler = (fn) => { onSessionLost = fn; };

let refreshing = null;

/** Tukar refresh token (satu penerbangan: banyak request 401 hanya memicu satu refresh). */
function refreshSession() {
  if (refreshing) return refreshing;

  refreshing = (async () => {
    const current = loadSession();
    if (!current || isExpired(current.refresh_expires_at, 0)) throw new ApiError('Sesi berakhir.', 401);

    const res = await fetch(`${API_BASE}/auth/refresh`, {
      method: 'POST',
      headers: { Accept: 'application/json', Authorization: `Bearer ${current.refresh_token}` },
    });
    if (!res.ok) throw new ApiError('Sesi berakhir.', 401);

    const pair = await res.json();
    const next = { ...current, ...pair };
    saveSession(next);
    return next;
  })().finally(() => { refreshing = null; });

  return refreshing;
}

async function accessToken() {
  let s = loadSession();
  if (!s) throw new ApiError('Belum login.', 401);
  if (isExpired(s.access_expires_at)) s = await refreshSession();
  return s.access_token;
}

async function parse(res) {
  const type = res.headers.get('content-type') || '';
  return type.includes('application/json') ? res.json().catch(() => ({})) : {};
}

/**
 * Request ke API. Otomatis menambahkan Bearer token, refresh bila kedaluwarsa/401 (sekali),
 * dan mengubah galat Laravel (422/403/dst.) menjadi ApiError.
 */
export async function api(path, { method = 'GET', body, query, raw = false } = {}) {
  const url = new URL(`${API_BASE}${path}`);
  Object.entries(query || {}).forEach(([k, v]) => v !== undefined && v !== null && v !== '' && url.searchParams.set(k, v));

  const send = async (token) => fetch(url, {
    method,
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
      ...(body && !(body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
    },
    body: body ? (body instanceof FormData ? body : JSON.stringify(body)) : undefined,
  });

  let res;
  try {
    res = await send(await accessToken());
    if (res.status === 401) res = await send((await refreshSession()).access_token);
  } catch (e) {
    if (e instanceof ApiError && e.status === 401) {
      clearSession();
      onSessionLost();
      throw e;
    }
    throw new ApiError('Tidak dapat terhubung ke server. Periksa koneksi Anda.', 0);
  }

  if (res.status === 401) {
    clearSession();
    onSessionLost();
    throw new ApiError('Sesi berakhir. Silakan masuk kembali.', 401);
  }

  if (raw) {
    if (!res.ok) throw new ApiError('Gagal memuat berkas.', res.status);
    return res;
  }

  const data = await parse(res);
  if (!res.ok) throw new ApiError(data.message || 'Terjadi kesalahan.', res.status, data.errors || {});
  return data;
}

/** Tukar kode sekali pakai (dari halaman login Laravel) + PKCE verifier menjadi sesi. */
export async function exchangeCode(code, verifier) {
  let res;
  try {
    res = await fetch(`${API_BASE}/auth/exchange`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ code, verifier }),
    });
  } catch {
    throw new ApiError('Tidak dapat terhubung ke server. Pastikan backend EcoWin berjalan.', 0);
  }

  const data = await parse(res);
  if (!res.ok) {
    const first = Object.values(data.errors || {}).flat()[0];
    throw new ApiError(first || data.message || 'Login gagal.', res.status, data.errors || {});
  }
  return data;
}
