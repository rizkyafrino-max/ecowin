const KEY = 'ecowin.pkce';

const b64url = (bytes) => btoa(String.fromCharCode(...bytes)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

/** Pasangan PKCE: verifier acak (disimpan sementara di tab ini) + challenge SHA-256. */
export async function createPkce() {
  const verifier = b64url(crypto.getRandomValues(new Uint8Array(48)));
  const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier));
  try { sessionStorage.setItem(KEY, verifier); } catch { /* tab privat: login tetap bisa dicoba lagi */ }
  return { verifier, challenge: b64url(new Uint8Array(digest)) };
}

/** Verifier hanya bisa dipakai sekali. */
export function takeVerifier() {
  try {
    const v = sessionStorage.getItem(KEY);
    sessionStorage.removeItem(KEY);
    return v;
  } catch {
    return null;
  }
}
