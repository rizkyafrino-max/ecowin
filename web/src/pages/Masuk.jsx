import { Loader2, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { Link, Navigate, useLocation } from 'react-router-dom';
import emblem from '../assets/ecowin-emblem.png';
import LaunchScreen from '../components/LaunchScreen';
import { useAuth } from '../context/AuthContext';
import { goExternal, GOOGLE_URL, REGISTER_URL } from '../utils/navigate';
import { createPkce } from '../utils/pkce';

const GoogleG = () => (
  <svg viewBox="0 0 48 48" className="h-5 w-5 flex-none" aria-hidden="true">
    <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z" />
    <path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z" />
    <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z" />
    <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z" />
  </svg>
);

const Brand = ({ light = false }) => (
  <div className="flex items-center gap-3">
    <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-black"><img src={emblem} alt="" className="h-7 w-auto" /></span>
    <span className={`text-2xl font-extrabold tracking-tight ${light ? 'text-ink' : ''}`}>Eco<span className="text-primary">Win</span></span>
  </div>
);

/**
 * Halaman login Nasabah (React). Autentikasi tetap Google + PKCE ke backend Laravel yang sama dengan Admin/Petugas;
 * role ditentukan server, bukan oleh halaman ini. Tidak ada password, PIN, atau pilihan role.
 */
export default function Masuk({ daftar = false }) {
  const { status } = useAuth();
  const { search } = useLocation();
  const failed = new URLSearchParams(search).has('gagal');
  const [error, setError] = useState(failed ? 'Login belum berhasil. Silakan coba lagi.' : null);
  const [busy, setBusy] = useState(false);

  if (status === 'loading') return <LaunchScreen />;
  if (status === 'authed') return <Navigate to="/" replace />;

  const start = async () => {
    setError(null);
    setBusy(true);
    try {
      const { challenge } = await createPkce();
      const base = daftar ? REGISTER_URL : GOOGLE_URL;
      goExternal(`${base}?${new URLSearchParams({ app: 'web', c: challenge })}`);
    } catch {
      setBusy(false);
      setError('Browser Anda tidak mendukung login aman. Perbarui browser lalu coba lagi.');
    }
  };

  return (
    <main className="min-h-dvh bg-tint lg:grid lg:grid-cols-[1.05fr_1fr] lg:bg-surface">
      {/* Panel ilustrasi: desktop penuh, mobile ringkas */}
      <section className="flex flex-col gap-5 px-6 pb-2 pt-6 sm:px-10 lg:justify-between lg:bg-tint lg:p-14">
        <Brand light />
        <div>
          <h1 className="text-[1.75rem] font-extrabold leading-[1.1] tracking-tight sm:text-4xl lg:text-5xl">
            Kelola Sampah.<br /><span className="text-primary">Jaga Masa Depan.</span>
          </h1>
          <p className="mt-3 max-w-[34ch] text-[15px] leading-relaxed text-slate-600 lg:mt-4 lg:text-lg">Bank sampah digital untuk lingkungan yang lebih baik.</p>
        </div>
        <img src="/ecowin-illustration.svg" alt="" width="480" height="360" className="mx-auto h-auto w-[min(70%,300px)] lg:w-[min(100%,520px)]" />
        <p className="hidden items-center gap-3 text-[15px] font-semibold text-primary-deep lg:flex"><span className="h-0.5 w-7 rounded bg-secondary" />Langkah kecil, dampak besar.</p>
      </section>

      {/* Panel autentikasi */}
      <section className="flex items-center justify-center px-4 pb-8 pt-4 sm:px-10 lg:p-12">
        <div className="w-full max-w-[440px] rounded-[24px] border border-line bg-surface p-6 shadow-soft sm:p-8 lg:border-0 lg:p-0 lg:shadow-none">
          <span className="inline-block rounded-full border border-emerald-200 bg-tint px-3 py-1 text-[11px] font-bold tracking-wider text-primary-deep">
            {daftar ? 'DAFTAR NASABAH' : 'MASUK KE ECOWIN'}
          </span>
          <h2 className="mt-4 text-3xl font-extrabold tracking-tight">{daftar ? 'Buat akun EcoWin' : 'Selamat datang'}</h2>
          <p className="mt-2 text-[15px] leading-relaxed text-muted">
            {daftar ? 'Daftar sebagai nasabah Bank Sampah RT/RW Anda dengan akun Google.' : 'Masuk dengan akun Google Anda untuk melihat saldo, setoran, dan aktivitas organik.'}
          </p>

          {error && <p className="mt-5 rounded-2xl border border-red-200 bg-danger-tint p-3.5 text-sm text-danger" role="alert">{error}</p>}

          <button type="button" onClick={start} disabled={busy}
            className="mt-7 flex min-h-[56px] w-full items-center justify-center gap-3 rounded-2xl border border-slate-300 bg-surface text-base font-semibold text-ink transition hover:border-primary hover:bg-tint hover:shadow-[0_0_0_3px_rgba(5,150,105,0.14)] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary/30 active:translate-y-px disabled:cursor-wait disabled:opacity-70">
            {busy ? <Loader2 className="h-5 w-5 animate-spin text-primary" aria-hidden /> : <GoogleG />}
            <span>{busy ? 'Mengalihkan ke Google…' : daftar ? 'Daftar dengan Google' : 'Lanjutkan dengan Google'}</span>
          </button>

          <p className="mt-5 flex items-start gap-2.5 text-sm leading-relaxed text-muted">
            <ShieldCheck className="mt-0.5 h-[18px] w-[18px] flex-none text-primary" aria-hidden />
            <span>Akun Google harus memiliki email terverifikasi dan terdaftar di EcoWin.</span>
          </p>

          <p className="mt-6 text-center text-sm text-muted">
            {daftar
              ? <>Sudah punya akun? <Link className="font-bold text-primary-deep hover:underline" to="/masuk">Masuk</Link></>
              : <>Belum punya akun? <Link className="font-bold text-primary-deep hover:underline" to="/daftar">Daftar sebagai nasabah</Link></>}
          </p>
          <p className="mt-6 text-xs text-slate-400">© {new Date().getFullYear()} EcoWin · Bank Sampah Digital Berkelanjutan</p>
        </div>
      </section>
    </main>
  );
}
