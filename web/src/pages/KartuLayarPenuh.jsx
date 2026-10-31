import { Maximize, Minimize, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { EcoApi } from '../api/endpoints';
import QRCard from '../components/QRCard';
import { ErrorState } from '../components/States';
import { useAuth } from '../context/AuthContext';
import { useQuery } from '../hooks/useQuery';

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
  const n = user?.nasabah;
  const bank = [n?.bank_sampah?.nama, n?.bank_sampah?.rt ? `RT ${n.bank_sampah.rt}${n.bank_sampah.rw ? `/RW ${n.bank_sampah.rw}` : ''}` : null].filter(Boolean).join(' · ');
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

  const tutup = () => { if ((window.history.state?.idx ?? 0) > 0) navigate(-1); else navigate('/', { replace: true }); };
  const tombol = 'absolute top-3 flex h-11 w-11 items-center justify-center rounded-full border border-line bg-white text-ink shadow-soft transition hover:bg-canvas';

  return (
    <main className="relative flex min-h-dvh flex-col items-center justify-center gap-3 bg-gradient-to-b from-tint to-canvas px-4 py-6">
      <button type="button" onClick={tutup} aria-label="Tutup kartu" className={`${tombol} left-3`}><X className="h-5 w-5" aria-hidden /></button>
      {bisaLayarPenuh && (
        <button type="button" onClick={alihLayarPenuh} aria-label={penuh ? 'Keluar dari layar penuh' : 'Layar penuh'} className={`${tombol} right-3`}>
          {penuh ? <Minimize className="h-5 w-5" aria-hidden /> : <Maximize className="h-5 w-5" aria-hidden />}
        </button>
      )}

      {/* Lebar kartu: mengisi lebar layar, tetapi tidak lebih tinggi dari layar (rasio kartu 1,586). */}
      <div className="w-full" style={{ maxWidth: 'min(94vw, calc((100dvh - 110px) * 1.586))' }}>
        {qr.error && !qr.data ? <ErrorState error={qr.error} onRetry={qr.reload} /> : <QRCard data={qr.data} loading={qr.loading} bank={bank} tanpaCatatan />}
      </div>

      <p className="max-w-sm text-center text-xs text-muted">Tunjukkan kartu ini kepada petugas Bank Sampah. Naikkan kecerahan layar agar QR mudah dipindai.</p>
      <p className="hidden text-center text-xs font-semibold text-primary-deep portrait:block">Putar HP ke samping agar kartu tampil lebih besar.</p>
    </main>
  );
}
