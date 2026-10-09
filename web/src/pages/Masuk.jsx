import { Loader2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import Button from '../components/Button';
import { BrandMark } from '../components/Nav';
import { useAuth } from '../context/AuthContext';
import { goExternal, LOGIN_URL, REGISTER_URL } from '../utils/navigate';
import { createPkce } from '../utils/pkce';

/**
 * Tidak ada halaman login di aplikasi ini: login & pendaftaran adalah satu halaman Laravel
 * (sama untuk Admin, Petugas, Nasabah). Halaman ini hanya membuat PKCE lalu meneruskan ke sana.
 */
export default function Masuk({ daftar = false }) {
  const { status } = useAuth();
  const { search } = useLocation();
  const failed = new URLSearchParams(search).has('gagal');
  const [error, setError] = useState(failed ? 'Login belum berhasil. Silakan coba lagi.' : null);
  const started = useRef(false);

  const start = async () => {
    setError(null);
    try {
      const { challenge } = await createPkce();
      goExternal(`${daftar ? REGISTER_URL : LOGIN_URL}?${new URLSearchParams({ app: 'web', c: challenge })}`);
    } catch {
      setError('Browser Anda tidak mendukung login aman. Perbarui browser lalu coba lagi.');
    }
  };

  useEffect(() => {
    if (status !== 'guest' || started.current || failed) return;
    started.current = true;
    start();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [status]);

  if (status === 'authed') return <Navigate to="/" replace />;

  return (
    <main className="flex min-h-dvh flex-col items-center justify-center gap-6 bg-[#050B07] px-6 text-center text-white">
      <BrandMark className="h-28 w-auto" />
      {error ? (
        <>
          <p className="max-w-sm text-sm text-white/80" role="alert">{error}</p>
          <Button size="lg" onClick={start}>{daftar ? 'Lanjut daftar' : 'Masuk dengan Google'}</Button>
        </>
      ) : (
        <p className="flex items-center gap-2 text-sm text-white/80" role="status"><Loader2 className="h-4 w-4 animate-spin" aria-hidden />Mengalihkan ke halaman {daftar ? 'pendaftaran' : 'masuk'} EcoWin…</p>
      )}
    </main>
  );
}
