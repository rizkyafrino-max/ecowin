import { Download, Maximize, Minimize, Printer, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { EcoApi } from '../api/endpoints';
import QRCard from '../components/QRCard';
import { ErrorState } from '../components/States';
import { useAuth } from '../context/AuthContext';
import { useQuery } from '../hooks/useQuery';
import { ringkasBank, unduhKartuNasabah } from '../utils/kartu';

/**
 * Kartu Nasabah layar penuh untuk dipindai petugas saat setor sampah. Kartu mengisi layar dengan rasio kartu asli (lebih besar
 * bila HP diputar ke samping), layar ditahan tetap menyala selama kartu tampil, dan tombol "Layar penuh" menyembunyikan bilah
 * peramban serta mencoba mengunci tampilan melintang. Web tidak bisa mengatur kecerahan atau memutar layar sendiri seperti
 * aplikasi Android, jadi pengguna diingatkan menaikkan kecerahan dan memutar HP.
 */
export default function KartuLayarPenuh() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const qr = useQuery(() => EcoApi.qr(), []);
  const [penuh, setPenuh] = useState(false);
  const [mengunduh, setMengunduh] = useState(false);
  const [galatUnduh, setGalatUnduh] = useState('');
  const bank = ringkasBank(user?.nasabah);
  const bisaLayarPenuh = typeof document !== 'undefined' && document.fullscreenEnabled === true;

  // Layar tetap menyala selama kartu tampil (bila peramban mendukung); dipasang lagi saat halaman kembali terlihat.
  useEffect(() => {
    let kunci = null;
    const minta = async () => {
      try { kunci = (await navigator.wakeLock?.request('screen')) ?? null; } catch { /* tidak didukung atau ditolak */ }
    };
    const ulang = () => { if (document.visibilityState === 'visible') minta(); };
    minta();
    document.addEventListener('visibilitychange', ulang);
    return () => {
      document.removeEventListener('visibilitychange', ulang);
      Promise.resolve(kunci?.release?.()).catch(() => {});
    };
  }, []);

  // Menjaga tombol tetap sesuai bila pengguna keluar dari layar penuh lewat gestur peramban; dan merapikan saat halaman ditutup.
  useEffect(() => {
    const ubah = () => setPenuh(!!document.fullscreenElement);
    document.addEventListener('fullscreenchange', ubah);
    return () => {
      document.removeEventListener('fullscreenchange', ubah);
      if (document.fullscreenElement) document.exitFullscreen?.().catch(() => {});
      try { screen.orientation?.unlock?.(); } catch { /* tidak didukung */ }
    };
  }, []);

  const alihLayarPenuh = async () => {
    try {
      if (document.fullscreenElement) {
        await document.exitFullscreen();
        try { screen.orientation?.unlock?.(); } catch { /* tidak didukung */ }
      } else {
        await document.documentElement.requestFullscreen();
        try { await screen.orientation?.lock?.('landscape'); } catch { /* hanya sebagian peramban HP yang mengizinkan */ }
      }
    } catch { /* ditolak peramban: tidak apa-apa, kartu tetap tampil */ }
  };

  const unduh = async () => {
    if (!qr.data || mengunduh) return;
    setMengunduh(true); setGalatUnduh('');
    try { await unduhKartuNasabah(qr.data, bank); }
    catch { setGalatUnduh('Kartu gagal dibuat. Coba lagi atau pakai tombol cetak.'); }
    finally { setMengunduh(false); }
  };

  const tutup = () => { if ((window.history.state?.idx ?? 0) > 0) navigate(-1); else navigate('/', { replace: true }); };
  const tombol = 'flex h-11 w-11 items-center justify-center rounded-full border border-line bg-white text-ink shadow-soft transition hover:bg-canvas disabled:opacity-60';

  return (
    <main className="relative flex min-h-dvh flex-col items-center justify-center gap-3 bg-gradient-to-b from-tint to-canvas px-4 py-6 print:block print:min-h-0 print:bg-none print:bg-white print:p-0">
      <button type="button" onClick={tutup} aria-label="Tutup kartu" className={`${tombol} absolute left-3 top-3 print:hidden`}><X className="h-5 w-5" aria-hidden /></button>
      {/* Unduh dan cetak selalu ada; layar penuh hanya bila peramban mendukung. */}
      <div className="absolute right-3 top-3 flex gap-2 print:hidden">
        <button type="button" onClick={unduh} disabled={!qr.data || mengunduh} aria-label="Unduh kartu (PNG)" className={tombol}><Download className="h-5 w-5" aria-hidden /></button>
        <button type="button" onClick={() => window.print()} disabled={!qr.data} aria-label="Cetak kartu" className={tombol}><Printer className="h-5 w-5" aria-hidden /></button>
        {bisaLayarPenuh && (
          <button type="button" onClick={alihLayarPenuh} aria-label={penuh ? 'Keluar dari layar penuh' : 'Layar penuh'} className={tombol}>
            {penuh ? <Minimize className="h-5 w-5" aria-hidden /> : <Maximize className="h-5 w-5" aria-hidden />}
          </button>
        )}
      </div>

      {/* Lebar kartu: mengisi lebar layar, tetapi tidak lebih tinggi dari layar (rasio kartu 1,586). */}
      <div className="kartu-cetak w-full" style={{ maxWidth: 'min(94vw, calc((100dvh - 110px) * 1.586))' }}>
        {qr.error && !qr.data ? <ErrorState error={qr.error} onRetry={qr.reload} /> : <QRCard data={qr.data} loading={qr.loading} bank={bank} tanpaCatatan />}
      </div>

      {galatUnduh && <p role="alert" className="max-w-sm text-center text-xs font-semibold text-danger print:hidden">{galatUnduh}</p>}
      <p className="max-w-sm text-center text-xs text-muted print:hidden">Tunjukkan kartu ini ke petugas Bank Sampah ya. Naikkan kecerahan layar supaya QR mudah dipindai.</p>
      <p className="hidden text-center text-xs font-semibold text-primary-deep portrait:block print:!hidden">Putar HP ke samping supaya kartunya tampil lebih besar.</p>
    </main>
  );
}
