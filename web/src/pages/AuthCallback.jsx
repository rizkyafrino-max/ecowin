import { Loader2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Button from '../components/Button';
import { BrandMark } from '../components/Nav';
import { useAuth } from '../context/AuthContext';

/** Menukar kode sekali pakai (dari Laravel) + PKCE verifier menjadi sesi. Kode ada di URL hanya sesaat. */
export default function AuthCallback() {
  const { exchange } = useAuth();
  const navigate = useNavigate();
  const [error, setError] = useState(null);
  const ran = useRef(false);

  useEffect(() => {
    if (ran.current) return; // StrictMode: kode hanya boleh dipakai sekali
    ran.current = true;

    const code = new URLSearchParams(window.location.search).get('code');
    window.history.replaceState({}, '', '/auth/callback'); // jangan biarkan kode tertinggal di riwayat

    exchange(code)
      .then(() => navigate('/', { replace: true }))
      .catch((e) => setError(e.message || 'Login gagal.'));
  }, [exchange, navigate]);

  return (
    <main className="flex min-h-dvh flex-col items-center justify-center gap-6 bg-[#050B07] px-6 text-center text-white">
      <BrandMark className="h-28 w-auto" />
      {error ? (
        <>
          <p className="max-w-sm text-sm text-white/80" role="alert">{error}</p>
          <Button size="lg" onClick={() => navigate('/masuk?gagal=1', { replace: true })}>Coba masuk lagi</Button>
        </>
      ) : (
        <p className="flex items-center gap-2 text-sm text-white/80" role="status"><Loader2 className="h-4 w-4 animate-spin" aria-hidden />Menyelesaikan login…</p>
      )}
    </main>
  );
}
