import { ArrowDownToLine, ArrowLeftRight, Home, MapPin, QrCode, Recycle, UserRound, Wallet } from 'lucide-react';
import { Link } from 'react-router-dom';
import PageHeader from '../components/PageHeader';

const GROUPS = [
  {
    title: 'Utama',
    items: [
      { to: '/', icon: Home, label: 'Beranda', text: 'Ringkasan saldo, setoran, dan aktivitas terbaru.' },
      { to: '/transaksi', icon: ArrowLeftRight, label: 'Transaksi', text: 'Riwayat setoran sampah anorganik dan nilainya.' },
      { to: '/organik', icon: Recycle, label: 'Organik', text: 'Setoran dan aktivitas organik: Biopori, BioporiPrint, estimasi kompos.' },
    ],
  },
  {
    title: 'Keuangan',
    items: [
      { to: '/saldo', icon: Wallet, label: 'Saldo', text: 'Saldo, saldo tersedia, dan riwayat mutasi.' },
      { to: '/penarikan', icon: ArrowDownToLine, label: 'Tarik Saldo', text: 'Ajukan penarikan dan pantau statusnya.' },
    ],
  },
  {
    title: 'Akun',
    items: [
      { to: '/profil', icon: QrCode, label: 'QR Saya', text: 'Tunjukkan QR ke petugas saat setor sampah.' },
      { to: '/organik', icon: MapPin, label: 'Peta Biopori', text: 'Lihat titik Biopori dan BioporiPrint di sekitar Anda.' },
      { to: '/profil', icon: UserRound, label: 'Profil', text: 'Data diri, Bank Sampah, dan keluar dari akun.' },
    ],
  },
];

export default function Fitur() {
  return (
    <div className="space-y-6">
      <PageHeader title="Semua Fitur" subtitle="Semua yang bisa Anda lakukan di EcoWin." />
      {GROUPS.map((g) => (
        <section key={g.title}>
          <h2 className="mb-2.5 text-sm font-bold uppercase tracking-wider text-muted">{g.title}</h2>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {g.items.map((f) => (
              <Link key={f.label} to={f.to} className="flex items-start gap-3.5 rounded-[20px] border border-line bg-surface p-4 transition hover:border-primary/40 hover:shadow-md">
                <span className="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-tint text-primary"><f.icon className="h-6 w-6" aria-hidden /></span>
                <span className="min-w-0">
                  <span className="block font-bold">{f.label}</span>
                  <span className="mt-0.5 block text-xs leading-relaxed text-muted">{f.text}</span>
                </span>
              </Link>
            ))}
          </div>
        </section>
      ))}
    </div>
  );
}
