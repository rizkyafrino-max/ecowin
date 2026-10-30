import { Loader2, QrCode, ShieldCheck, Sprout, Wallet } from 'lucide-react';
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

const FITUR = [
  { icon: QrCode, judul: 'Setor dengan QR Card', isi: 'Petugas mencatat setoran Anda dalam hitungan detik.' },
  { icon: Wallet, judul: 'Saldo transparan', isi: 'Pantau saldo dan riwayat setoran kapan saja.' },
  { icon: Sprout, judul: 'Organik dan Biopori', isi: 'Catat aktivitas organik dan temukan titik biopori terdekat.' },
];

const Brand = () => (
  <div className="flex items-center gap-3">
    <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white shadow-[0_6px_16px_rgba(0,0,0,0.14)]"><img src={emblem} alt="" className="h-7 w-auto" /></span>
    <span className="text-2xl font-extrabold tracking-tight text-white">Eco<span className="text-emerald-200">Win</span></span>
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
    <main className="min-h-dvh bg-canvas lg:grid lg:grid-cols-[1.1fr_1fr] lg:grid-rows-[1fr_auto] lg:bg-surface">
      {/* Panel hijau desktop (latar kolom kiri) */}
      <span aria-hidden className="relative hidden overflow-hidden rounded-r-[56px] bg-gradient-to-br from-[#065F46] via-primary to-secondary lg:col-start-1 lg:row-span-2 lg:row-start-1 lg:block">
        <span className="absolute -right-28 -top-36 h-[460px] w-[460px] rounded-full bg-white/10" />
        <span className="absolute -bottom-32 -left-28 h-[380px] w-[380px] rounded-full bg-white/[0.07]" />
      </span>

      {/* Kepala: merek dan pesan */}
      <section className="relative z-10 flex flex-col gap-4 overflow-hidden rounded-b-[36px] bg-gradient-to-br from-[#065F46] via-primary to-secondary px-6 pb-24 pt-6 text-white sm:px-10 sm:pb-28 lg:col-start-1 lg:row-start-1 lg:justify-center lg:gap-6 lg:overflow-visible lg:rounded-none lg:bg-none lg:px-16 lg:pb-0 lg:pt-12">
        <span aria-hidden className="absolute -right-24 -top-28 h-[300px] w-[300px] rounded-full bg-white/10 lg:hidden" />
        <span aria-hidden className="absolute -bottom-24 -left-20 h-[220px] w-[220px] rounded-full bg-white/[0.07] lg:hidden" />
        <div className="relative"><Brand /></div>
        <div className="relative">
          <h1 className="text-[1.85rem] font-extrabold leading-[1.12] tracking-tight sm:text-4xl lg:text-5xl">
            Kelola Sampah.<span className="block text-emerald-200">Jaga Masa Depan.</span>
          </h1>
          <p className="mt-2.5 max-w-[30ch] text-[15px] leading-relaxed text-emerald-50/85 lg:mt-4 lg:max-w-[34ch] lg:text-lg">Bank sampah digital untuk lingkungan yang lebih baik.</p>
        </div>
        <p className="hidden"><span />Langkah kecil, dampak besar.</p>
      </section>

      {/* Kartu masuk: menimpa tepi bawah kepala di HP */}
      <section className="relative z-20 -mt-[3.9rem] px-4 pb-6 sm:px-10 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:mt-0 lg:flex lg:p-12">
        <div className="mx-auto w-full max-w-[480px] rounded-[28px] border border-line/90 bg-surface p-[1.35rem] shadow-[0_24px_50px_-22px_rgba(6,95,70,0.45),0_2px_6px_rgba(15,23,42,0.05)] sm:p-8 lg:m-auto lg:max-w-[440px] lg:rounded-none lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none">
          <span className="inline-block rounded-full bg-tint px-3 py-1 text-[11px] font-bold tracking-wider text-primary-deep">
            {daftar ? 'DAFTAR NASABAH' : 'MASUK KE ECOWIN'}
          </span>
          <h2 className="mt-3.5 text-[1.65rem] font-extrabold leading-tight tracking-tight lg:text-[2rem]">{daftar ? 'Buat akun EcoWin' : 'Selamat datang'}</h2>
          <p className="mt-2 text-[15px] leading-relaxed text-muted">
            {daftar ? 'Daftar sebagai nasabah Bank Sampah RT/RW Anda dengan akun Google.' : 'Masuk dengan akun Google Anda untuk melihat saldo, setoran, dan aktivitas organik.'}
          </p>

          {error && <p className="mt-5 rounded-2xl border border-red-200 bg-danger-tint p-3.5 text-sm text-danger" role="alert">{error}</p>}

          <button type="button" onClick={start} disabled={busy}
            className="mt-6 flex min-h-[56px] w-full items-center justify-center gap-3 rounded-full border-[1.5px] border-slate-300 bg-surface text-base font-semibold text-ink transition hover:border-primary hover:bg-tint hover:ring-4 hover:ring-primary/10 focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-[3px] focus-visible:outline-primary active:translate-y-px disabled:opacity-70">
            {busy ? <Loader2 className="h-5 w-5 animate-spin text-primary-deep" aria-hidden /> : <GoogleG />}
            <span>{busy ? 'Mengalihkan ke Google…' : daftar ? 'Daftar dengan Google' : 'Lanjutkan dengan Google'}</span>
          </button>

          <p className="mt-5 flex items-start gap-2.5 text-sm leading-relaxed text-muted">
            <ShieldCheck className="mt-0.5 h-[18px] w-[18px] flex-none text-primary" aria-hidden />
            <span>Akun Google harus memiliki email terverifikasi dan terdaftar di EcoWin.</span>
          </p>

          <p className="mt-6 border-t border-line pt-5 text-center text-sm text-muted">
            {daftar
              ? <>Sudah punya akun? <Link className="font-bold text-primary-deep hover:underline" to="/masuk">Masuk</Link></>
              : <>Belum punya akun? <Link className="font-bold text-primary-deep hover:underline" to="/daftar">Daftar sebagai nasabah</Link></>}
          </p>
          <p className="mt-4 text-center text-xs text-slate-400">© {new Date().getFullYear()} EcoWin · Bank Sampah Digital Berkelanjutan</p>
        </div>
      </section>

      {/* Keunggulan: ikon kecil di HP, daftar di panel hijau pada desktop */}
      <ul className="relative z-10 mx-auto hidden w-full max-w-none grid-cols-1 gap-3.5 px-16 pb-12 lg:col-start-1 lg:row-start-2 lg:grid" aria-label="Keunggulan EcoWin">
        {FITUR.map(({ icon: Icon, judul, isi }) => (
          <li key={judul} className="flex flex-col items-center gap-2 text-center lg:flex-row lg:items-center lg:gap-3.5 lg:text-left lg:text-white">
            <span className="flex h-11 w-11 flex-none items-center justify-center rounded-[14px] bg-tint text-primary-deep lg:border lg:border-white/25 lg:bg-white/15 lg:text-white"><Icon className="h-[21px] w-[21px]" aria-hidden /></span>
            <div><b className="block text-[13px] font-bold leading-tight lg:text-[15px]">{judul}</b><span className="mt-0.5 hidden text-[13px] leading-snug text-emerald-50/80 lg:block">{isi}</span></div>
          </li>
        ))}
      </ul>
    </main>
  );
}
