import { Lock, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { akunTerkunci } from '../utils/verifikasi';
import Button from './Button';
import Card from './Card';

/** Bentuk kosong menyerupai halaman (kartu, ubin, baris): hanya hiasan, tidak berisi dan tidak memuat data apa pun. */
function Kerangka() {
  return (
    <div aria-hidden className="space-y-3 opacity-60">
      <div className="h-36 rounded-[24px] bg-line" />
      <div className="grid grid-cols-2 gap-3"><div className="h-20 rounded-[20px] bg-line" /><div className="h-20 rounded-[20px] bg-line" /></div>
      {[0, 1, 2, 3].map((i) => (
        <div key={i} className="flex items-center gap-3">
          <div className="h-11 w-11 flex-none rounded-2xl bg-line" />
          <div className="flex-1 space-y-1.5"><div className="h-3.5 w-3/4 rounded-full bg-line" /><div className="h-3 w-1/2 rounded-full bg-line" /></div>
          <div className="h-3.5 w-14 rounded-full bg-line" />
        </div>
      ))}
    </div>
  );
}

/**
 * Pengganti halaman data/layanan untuk akun yang belum diverifikasi petugas. Status dicek ulang otomatis tiap menit dan lewat
 * tombol, jadi halaman terbuka sendiri begitu petugas memverifikasi. Tidak ada data yang dimuat selama terkunci.
 */
export default function TerkunciVerifikasi({ polos = false }) {
  const { refreshUser } = useAuth();
  const [memeriksa, setMemeriksa] = useState(false);
  const [pesan, setPesan] = useState('');

  useEffect(() => {
    const t = setInterval(() => { refreshUser().catch(() => {}); }, 60_000);
    return () => clearInterval(t);
  }, [refreshUser]);

  const periksa = async () => {
    setMemeriksa(true); setPesan('');
    try {
      const baru = await refreshUser();
      if (akunTerkunci(baru)) setPesan('Akunmu belum diverifikasi. Coba lagi nanti ya.');
    } catch (err) {
      setPesan(err.message || 'Status belum bisa diperiksa. Coba lagi ya.');
    } finally { setMemeriksa(false); }
  };

  const isi = (
    <div className="mx-auto w-full max-w-xl space-y-5">
      <Card className="flex flex-col items-center gap-3 text-center">
        <span className="flex h-16 w-16 items-center justify-center rounded-full bg-warn-tint text-warn"><Lock className="h-8 w-8" aria-hidden /></span>
        <h2 className="text-xl font-extrabold">Halaman ini terkunci</h2>
        <p className="max-w-sm text-sm leading-relaxed text-muted">
          Akunmu masih menunggu verifikasi petugas Bank Sampah. Setelah diverifikasi, halaman ini terbuka sendiri dan kamu bisa mulai bertransaksi.
        </p>
        {pesan && <p role="status" className="text-sm font-semibold text-muted">{pesan}</p>}
        <Button size="lg" icon={ShieldCheck} loading={memeriksa} onClick={periksa}>Periksa status</Button>
        {polos && <Link to="/" className="text-sm font-semibold text-primary-deep hover:underline">Kembali ke beranda</Link>}
      </Card>
      <Kerangka />
    </div>
  );

  return polos ? <main className="flex min-h-dvh items-start justify-center bg-canvas px-4 py-10">{isi}</main> : isi;
}
