import { ArrowDownToLine, Leaf, QrCode, Recycle, Scale, Sprout } from 'lucide-react';
import { Link } from 'react-router-dom';
import { EcoApi } from '../api/endpoints';
import { ActivityCard, TransactionCard } from '../components/Cards';
import Button from '../components/Button';
import Card, { BalanceCard, StatCard } from '../components/Card';
import { EmptyState, ErrorState, LoadingState } from '../components/States';
import VerifikasiBanner from '../components/VerifikasiBanner';
import { useAuth } from '../context/AuthContext';
import { useQuery } from '../hooks/useQuery';
import { inisial, kg, sapaan, tanggal } from '../utils/format';

function MonthlyChart({ stat }) {
  const max = Math.max(1, ...stat.berat);
  const kosong = stat.berat.every((v) => v === 0);
  return (
    <div>
      <div className="flex h-44 items-stretch gap-2 sm:gap-3" role="img" aria-label="Grafik berat setoran anorganik per bulan">
        {stat.berat.map((v, i) => (
          <div key={stat.labels[i]} className="flex min-w-0 flex-1 flex-col items-center gap-1.5">
            <span className="h-4 text-[10px] font-semibold tabular-nums text-muted">{v > 0 ? kg(v) : ''}</span>
            <div className="flex w-full flex-1 items-end">
              <div className={`w-full rounded-t-xl ${v > 0 ? 'bg-secondary' : 'bg-line'}`} style={{ height: `${v > 0 ? Math.max(6, (v / max) * 100) : 3}%` }} title={`${stat.labels[i]}: ${kg(v)}`} />
            </div>
            <span className="text-[10px] text-muted">{stat.labels[i].split(' ')[0]}</span>
          </div>
        ))}
      </div>
      {kosong && <p className="mt-3 text-center text-xs text-muted">Belum ada setoran dalam 6 bulan terakhir.</p>}
    </div>
  );
}

export default function Dashboard() {
  const { user } = useAuth();
  const dash = useQuery(() => EcoApi.dashboard(), []);
  const saldo = useQuery(() => EcoApi.saldo(), []);
  const stat = useQuery(() => EcoApi.statistik(6), []);

  const d = dash.data;
  const s = saldo.data;

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3.5">
        {user?.avatar
          ? <img src={user.avatar} alt="" referrerPolicy="no-referrer" className="h-12 w-12 rounded-full object-cover md:hidden" />
          : <span className="flex h-12 w-12 items-center justify-center rounded-full bg-tint font-bold text-primary md:hidden">{inisial(user?.nama)}</span>}
        <div>
          <p className="text-sm text-muted">{sapaan()},</p>
          <h2 className="text-2xl font-extrabold leading-tight">{user?.nama}</h2>
          {user?.nasabah?.bank_sampah && <p className="text-xs text-muted">{user.nasabah.bank_sampah.nama} · RT {user.nasabah.bank_sampah.rt}/RW {user.nasabah.bank_sampah.rw}</p>}
        </div>
      </div>

      <VerifikasiBanner />

      {dash.error ? <Card><ErrorState error={dash.error} onRetry={dash.reload} /></Card> : (
        <>
          <div className="grid grid-cols-[minmax(0,1fr)] gap-4 lg:grid-cols-[1.4fr_1fr]">
            {dash.loading || saldo.loading ? <div className="h-44 ew-skeleton !rounded-[24px]" /> : (
              <BalanceCard saldo={s?.saldo ?? d?.saldo} tersedia={s?.saldo_tersedia} ditahan={s?.saldo_ditahan}
                actions={<Link to="/penarikan"><Button variant="outline" size="sm" className="!border-white/30 !bg-white/15 !text-white hover:!bg-white/25" icon={ArrowDownToLine}>Tarik saldo</Button></Link>} />
            )}
            <Card className="flex min-w-0 flex-col justify-center">
              <div className="flex items-center justify-between">
                <p className="text-sm font-bold">Aksi cepat</p>
                <Link to="/fitur" className="text-xs font-semibold text-muted hover:text-primary">Lihat semua</Link>
              </div>
              <div className="-mx-5 mt-3 flex gap-2.5 overflow-x-auto px-5 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                {[
                  { to: '/profil', icon: QrCode, label: 'Setor sampah' },
                  { to: '/organik', icon: Recycle, label: 'Organik' },
                  { to: '/penarikan', icon: ArrowDownToLine, label: 'Tarik saldo' },
                  { to: '/transaksi', icon: Scale, label: 'Riwayat' },
                ].map((a, i) => (
                  <Link key={a.label} to={a.to}
                    className={`flex flex-none items-center gap-2 rounded-full py-1 pl-1 pr-4 text-sm font-semibold transition ${i === 0 ? 'bg-gradient-to-r from-tint via-accent/40 to-accent/70 text-ink' : 'bg-line/50 text-ink hover:bg-tint'}`}>
                    <span className="flex h-11 w-11 items-center justify-center rounded-full border border-line bg-surface text-primary"><a.icon className="h-5 w-5" aria-hidden /></span>
                    {a.label}
                  </Link>
                ))}
              </div>
            </Card>
          </div>

          <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            {dash.loading ? Array.from({ length: 4 }, (_, i) => <div key={i} className="h-24 ew-skeleton !rounded-[20px]" />) : (
              <>
                <StatCard icon={Scale} label="Total anorganik" value={kg(d.anorganik.total_berat_kg)} hint={`${d.anorganik.jumlah_transaksi} transaksi`} />
                <StatCard icon={Recycle} label="Total organik" value={kg(d.organik.total_berat_kg)} hint={`Estimasi kompos ${kg(d.organik.estimasi_kompos_kg)}`} tone="info" />
                <StatCard icon={Leaf} label="Aktivitas organik" value={d.organik.jumlah_aktivitas_biopori} hint={`${d.organik.biopori_terverifikasi} terverifikasi`} />
                <StatCard icon={Sprout} label="Menunggu verifikasi" value={d.organik.biopori_pending} hint="Aktivitas organik" tone="warn" />
              </>
            )}
          </div>

          <div className="grid gap-4 lg:grid-cols-2">
            <Card>
              <div className="mb-1 flex items-center justify-between"><h3 className="font-bold">Transaksi terbaru</h3><Link to="/transaksi" className="text-sm font-semibold text-primary">Lihat semua</Link></div>
              {dash.loading ? <LoadingState rows={3} /> : d.transaksi_terakhir.length === 0
                ? <EmptyState title="Belum ada transaksi" message="Setoran sampah anorganik Anda akan tampil di sini." />
                : <ul className="divide-y divide-line">{d.transaksi_terakhir.map((t) => (
                  <li key={t.id}><TransactionCard title={t.jenis_sampah ?? 'Setoran anorganik'} subtitle={kg(t.berat_kg)} amount={t.nilai_rupiah} date={t.created_at} /></li>
                ))}</ul>}
            </Card>

            <Card>
              <div className="mb-1 flex items-center justify-between"><h3 className="font-bold">Aktivitas Organik</h3><Link to="/organik" className="text-sm font-semibold text-primary">Lihat semua</Link></div>
              {dash.loading ? <LoadingState rows={3} /> : d.biopori_terakhir.length === 0
                ? <EmptyState icon={Leaf} title="Belum ada aktivitas" message="Tambahkan aktivitas saat Anda mengolah sampah organik."
                  action={<Link to="/organik"><Button size="sm">Tambah Aktivitas</Button></Link>} />
                : <ul className="divide-y divide-line">{d.biopori_terakhir.map((a) => (
                  <li key={a.id}><ActivityCard title={a.jenis_sampah} subtitle={`${a.lokasi ?? 'Lokasi'} · ${tanggal(a.tanggal_pemasukan)}`} status={a.status} right={<p className="text-sm font-bold">{kg(a.berat_kg)}</p>} /></li>
                ))}</ul>}
            </Card>
          </div>

          <Card>
            <h3 className="mb-4 font-bold">Setoran anorganik 6 bulan terakhir</h3>
            {stat.loading ? <LoadingState rows={1} /> : stat.error ? <ErrorState error={stat.error} onRetry={stat.reload} /> : <MonthlyChart stat={stat.data} />}
          </Card>
        </>
      )}
    </div>
  );
}
