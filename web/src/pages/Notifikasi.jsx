import { ArrowDownLeft, ArrowUpRight, BadgeCheck, Bell, CheckCheck, Leaf, MapPin, Megaphone, Recycle, Trash2, XCircle } from 'lucide-react';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { EcoApi } from '../api/endpoints';
import Button from '../components/Button';
import Card from '../components/Card';
import PageHeader from '../components/PageHeader';
import { EmptyState, ErrorState, LoadingState } from '../components/States';
import { useNotif } from '../context/NotifContext';
import { useQuery } from '../hooks/useQuery';
import { tanggal } from '../utils/format';

const TUJUAN = { saldo: '/saldo', transaksi: '/transaksi', organik: '/organik', beranda: '/' };

/** Ikon dan warna per jenis notifikasi (teks selalu ada, tidak hanya warna). */
function gaya(tipe = '') {
  if (tipe === 'pengumuman') return { Icon: Megaphone, kelas: 'bg-warn-tint text-warn' };
  if (tipe.endsWith('ditolak')) return { Icon: XCircle, kelas: 'bg-danger-tint text-danger' };
  if (tipe.startsWith('penarikan')) return { Icon: ArrowUpRight, kelas: 'bg-info-tint text-info' };
  if (tipe.startsWith('setoran_organik') || tipe.startsWith('biopori')) return { Icon: Leaf, kelas: 'bg-tint text-primary-deep' };
  if (tipe.startsWith('setoran')) return { Icon: ArrowDownLeft, kelas: 'bg-tint text-primary-deep' };
  if (tipe.startsWith('titik')) return { Icon: MapPin, kelas: 'bg-tint text-primary-deep' };
  if (tipe.startsWith('akun')) return { Icon: BadgeCheck, kelas: 'bg-tint text-primary-deep' };
  return { Icon: Recycle, kelas: 'bg-canvas text-muted' };
}

export default function Notifikasi() {
  const navigate = useNavigate();
  const { refresh, setBelumDibaca } = useNotif();
  const daftar = useQuery(() => EcoApi.notifikasi(), []);
  const items = daftar.data?.data ?? [];
  const adaBelum = items.some((n) => !n.dibaca);
  const adaDibaca = items.some((n) => n.dibaca);
  const [konfirmasi, setKonfirmasi] = useState(null); // 'dibaca' | 'semua' | null
  const [galatHapus, setGalatHapus] = useState('');

  const buka = async (n) => {
    if (!n.dibaca) {
      try { await EcoApi.bacaNotif(n.id); } catch { /* abaikan */ }
      refresh();
    }
    const ke = TUJUAN[n.aksi];
    if (ke) navigate(ke);
    else daftar.reload();
  };

  const bacaSemua = async () => {
    try { await EcoApi.bacaSemuaNotif(); setBelumDibaca(0); } catch { /* abaikan */ }
    daftar.reload();
  };

  const hapusSatu = async (n) => {
    setGalatHapus('');
    try { await EcoApi.hapusNotif(n.id); } catch { setGalatHapus('Notifikasi gagal dihapus. Coba lagi.'); }
    refresh();
    daftar.reload();
  };

  const hapusBanyak = async () => {
    const hanyaDibaca = konfirmasi === 'dibaca';
    setKonfirmasi(null);
    setGalatHapus('');
    try { await EcoApi.hapusNotifSemua(hanyaDibaca); } catch { setGalatHapus('Notifikasi gagal dihapus. Coba lagi.'); }
    refresh();
    daftar.reload();
  };

  return (
    <div className="space-y-5">
      <PageHeader title="Notifikasi" subtitle="Kabar terbaru tentang setoran, penarikan, dan aktivitas Anda."
        action={adaBelum ? <Button size="sm" variant="soft" icon={CheckCheck} onClick={bacaSemua}>Tandai dibaca</Button> : null} />

      {daftar.loading && !daftar.data ? <LoadingState rows={4} /> : null}
      {daftar.error && !daftar.data ? <ErrorState error={daftar.error} onRetry={daftar.reload} /> : null}
      {daftar.data && items.length === 0 ? (
        <Card><EmptyState icon={Bell} title="Belum ada notifikasi" message="Kabar penting seperti penarikan disetujui atau setoran tercatat akan muncul di sini." /></Card>
      ) : null}

      {items.length > 0 && (
        <div className="flex flex-wrap items-center gap-2">
          {adaDibaca && <Button size="sm" variant="outline" icon={Trash2} onClick={() => setKonfirmasi('dibaca')}>Hapus yang sudah dibaca</Button>}
          <Button size="sm" variant="ghost" icon={Trash2} onClick={() => setKonfirmasi('semua')}>Hapus semua</Button>
        </div>
      )}
      {konfirmasi && (
        <div role="alertdialog" aria-label="Konfirmasi hapus notifikasi" className="flex flex-wrap items-center gap-3 rounded-2xl border border-danger/30 bg-danger-tint p-4 text-sm">
          <span className="flex-1 font-semibold text-danger">{konfirmasi === 'semua' ? 'Hapus semua notifikasi? Tindakan ini tidak bisa dibatalkan.' : 'Hapus semua notifikasi yang sudah dibaca?'}</span>
          <Button size="sm" variant="danger" onClick={hapusBanyak}>Ya, hapus</Button>
          <Button size="sm" variant="outline" onClick={() => setKonfirmasi(null)}>Batal</Button>
        </div>
      )}
      {galatHapus && <p role="alert" className="text-sm font-semibold text-danger">{galatHapus}</p>}

      {items.length > 0 && (
        <ul className="space-y-3" aria-label="Daftar notifikasi">
          {items.map((n) => {
            const { Icon, kelas } = gaya(n.tipe);
            return (
              <li key={n.id} className="relative">
                <button type="button" onClick={() => buka(n)}
                  className={`flex w-full items-start gap-3 rounded-3xl border bg-surface p-4 pr-14 text-left transition hover:shadow-md ${n.dibaca ? 'border-line' : 'border-primary/40 shadow-sm'}`}>
                  <span className={`flex h-11 w-11 flex-none items-center justify-center rounded-2xl ${kelas}`}><Icon className="h-5 w-5" aria-hidden /></span>
                  <span className="min-w-0 flex-1">
                    <span className="flex items-center gap-2">
                      <span className={`truncate text-sm ${n.dibaca ? 'font-semibold' : 'font-extrabold'}`}>{n.judul}</span>
                      {!n.dibaca && <span className="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white">Baru</span>}
                    </span>
                    <span className="mt-0.5 block text-sm text-muted">{n.isi}</span>
                    <span className="mt-1 block text-xs text-muted">{tanggal(n.created_at, true)}</span>
                  </span>
                </button>
                <button type="button" onClick={() => hapusSatu(n)} aria-label={`Hapus notifikasi: ${n.judul}`}
                  className="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full text-muted transition hover:bg-danger-tint hover:text-danger">
                  <Trash2 className="h-4 w-4" aria-hidden />
                </button>
              </li>
            );
          })}
        </ul>
      )}
      {items.length > 0 && <p className="text-center text-xs text-muted">Notifikasi dihapus otomatis setelah 30 hari.</p>}
    </div>
  );
}
