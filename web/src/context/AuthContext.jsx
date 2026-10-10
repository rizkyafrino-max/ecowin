import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { ApiError, exchangeCode, setSessionLostHandler } from '../api/client';
import { EcoApi } from '../api/endpoints';
import { clearSession, isExpired, loadSession, saveSession } from '../api/session';
import { takeVerifier } from '../utils/pkce';

const AuthContext = createContext(null);

/**
 * Status: 'loading' (memeriksa sesi tersimpan) | 'guest' | 'authed'.
 * Sesi valid -> langsung masuk dashboard; sesi dicabut/kedaluwarsa -> kembali ke login Google.
 */
export function AuthProvider({ children }) {
  const [state, setState] = useState({ status: 'loading', user: null });

  const reset = useCallback(() => setState({ status: 'guest', user: null }), []);

  useEffect(() => {
    setSessionLostHandler(reset);

    const s = loadSession();
    if (!s || isExpired(s.refresh_expires_at, 0)) {
      clearSession();
      reset();
      return;
    }

    // Validasi ke server: akun yang dinonaktifkan/dicabut tidak boleh lolos hanya karena token tersimpan.
    EcoApi.me()
      .then((res) => {
        const user = res.data;
        if (user?.role !== 'nasabah') throw new ApiError('Akun ini bukan nasabah.', 403);
        setState({ status: 'authed', user });
      })
      .catch(() => { clearSession(); reset(); });
  }, [reset]);

  const exchange = useCallback(async (code) => {
    const verifier = takeVerifier();
    if (!code || !verifier) throw new ApiError('Sesi login tidak ditemukan. Silakan masuk lagi.', 400);

    const data = await exchangeCode(code, verifier);
    const user = data.user?.data ?? data.user;

    if (user?.role !== 'nasabah') {
      throw new ApiError('Akun Admin/Petugas masuk melalui dashboard EcoWin, bukan aplikasi nasabah.', 403);
    }

    const { user: _u, ...pair } = data;
    saveSession(pair);
    setState({ status: 'authed', user });
  }, []);

  const signOut = useCallback(async () => {
    try { await EcoApi.logout(); } catch { /* token mungkin sudah tidak valid; tetap hapus lokal */ }
    clearSession();
    reset();
  }, [reset]);

  const refreshUser = useCallback(async () => {
    const res = await EcoApi.me();
    setState((s) => ({ ...s, user: res.data }));
  }, []);

  const value = useMemo(() => ({ ...state, exchange, signOut, refreshUser }), [state, exchange, signOut, refreshUser]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth harus dipakai di dalam AuthProvider');
  return ctx;
};
