import { ArrowDownLeft, ArrowUpRight, BadgeCheck, Bell, CheckCheck, Leaf, MapPin, Megaphone, Recycle, XCircle } from 'lucide-react';
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
        <ul className="space-y-3" aria-label="Daftar notifikasi">
          {items.map((n) => {
            const { Icon, kelas } = gaya(n.tipe);
            return (
              <li key={n.id}>
                <button type="button" onClick={() => buka(n)}
                  className={`flex w-full items-start gap-3 rounded-3xl border bg-surface p-4 text-left transition hover:shadow-md ${n.dibaca ? 'border-line' : 'border-primary/40 shadow-sm'}`}>
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
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}
