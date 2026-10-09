// Penyimpanan sesi di browser. Token hanya dipakai lewat header Authorization (bukan cookie),
// sehingga tidak ada risiko CSRF. Sesi dihapus total saat logout atau token tidak valid.
const KEY = 'ecowin.session';

export function loadSession() {
  try {
    const raw = localStorage.getItem(KEY);
    if (!raw) return null;
    const s = JSON.parse(raw);
    if (!s?.refresh_token || !s?.access_token) return null;
    return s;
  } catch {
    return null;
  }
}

export function saveSession(session) {
  try {
    localStorage.setItem(KEY, JSON.stringify(session));
  } catch {
    /* penyimpanan diblokir: sesi hanya hidup di memori */
  }
}

export function clearSession() {
  try {
    localStorage.removeItem(KEY);
  } catch {
    /* abaikan */
  }
}

export const isExpired = (iso, skewMs = 30_000) => !iso || new Date(iso).getTime() - skewMs <= Date.now();
