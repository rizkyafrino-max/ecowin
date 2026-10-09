import { ArrowLeftRight, Home, LayoutGrid, Recycle, UserRound, Wallet } from 'lucide-react';
import { NavLink } from 'react-router-dom';
import logo from '../assets/ecowin-logo.png';
import emblem from '../assets/ecowin-emblem.png';

// Menu utama Nasabah: Beranda, Transaksi, Organik, Saldo, Profil. Biopori & BioporiPrint ada di dalam Organik.
export const NAV_ITEMS = [
  { to: '/', label: 'Beranda', icon: Home, end: true },
  { to: '/transaksi', label: 'Transaksi', icon: ArrowLeftRight },
  { to: '/organik', label: 'Organik', icon: Recycle },
  { to: '/saldo', label: 'Saldo', icon: Wallet },
  { to: '/profil', label: 'Profil', icon: UserRound },
];

// Penarikan adalah bagian dari Saldo: tab Saldo tetap aktif di /penarikan.
const isActive = (item, pathname) => (item.to === '/saldo' ? pathname.startsWith('/saldo') || pathname.startsWith('/penarikan') : undefined);

export function Sidebar({ pathname }) {
  return (
    <aside className="fixed inset-y-0 left-0 z-30 hidden w-20 flex-col border-r border-line bg-surface md:flex lg:w-64" aria-label="Navigasi utama">
      <div className="flex h-20 items-center justify-center px-4 lg:justify-start">
        <span className="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-black">
          <img src={emblem} alt="" className="h-7 w-auto" />
        </span>
        <span className="ml-3 hidden text-xl font-extrabold lg:block">Eco<span className="text-primary">Win</span></span>
      </div>
      <nav className="flex-1 space-y-1 px-3 py-2">
        {[...NAV_ITEMS, { to: '/fitur', label: 'Semua Fitur', icon: LayoutGrid }].map((item) => {
          const forced = isActive(item, pathname);
          return (
            <NavLink key={item.to} to={item.to} end={item.end} title={item.label}
              className={({ isActive: a }) => `flex items-center justify-center gap-3 rounded-2xl px-3 py-3 text-sm font-semibold transition lg:justify-start ${(forced ?? a) ? 'bg-primary text-white shadow-sm' : 'text-muted hover:bg-tint hover:text-primary'}`}>
              <item.icon className="h-5 w-5 flex-none" aria-hidden />
              <span className="hidden lg:inline">{item.label}</span>
            </NavLink>
          );
        })}
      </nav>
      <p className="hidden px-6 pb-6 text-xs text-muted lg:block">Bank Sampah Digital Berkelanjutan</p>
    </aside>
  );
}

const FOOTER_LEFT = [NAV_ITEMS[0], NAV_ITEMS[1]];
const FOOTER_RIGHT = [NAV_ITEMS[2], NAV_ITEMS[3]];

/** Footer melayang, sama dengan footer admin: dua menu, tombol tengah "Semua Fitur", dua menu. Profil lewat foto di atas. */
export function BottomNav({ pathname, hidden = false }) {
  const link = (item) => {
    const forced = isActive(item, pathname);
    return (
      <NavLink key={item.to} to={item.to} end={item.end}
        className={({ isActive: a }) => `flex min-w-[56px] flex-col items-center justify-center gap-[3px] rounded-xl px-2.5 py-[7px] text-[10px] font-bold transition sm:min-w-[62px] ${(forced ?? a) ? 'bg-tint text-primary' : 'text-muted hover:bg-tint hover:text-primary'}`}>
        <item.icon className="h-[21px] w-[21px]" strokeWidth={1.8} aria-hidden />
        {item.label}
      </NavLink>
    );
  };

  return (
    <nav className={`pb-safe fixed bottom-2 left-1/2 z-30 flex min-h-[60px] w-[min(760px,calc(100vw-16px))] items-center justify-around gap-2 rounded-[18px] border border-line bg-white/95 px-2 pt-2 shadow-[0_8px_24px_rgba(15,23,42,0.10)] backdrop-blur transition duration-200 sm:bottom-3.5 sm:min-h-16 sm:rounded-[20px] sm:px-3.5 ${hidden ? 'pointer-events-none -translate-x-1/2 translate-y-[calc(100%+28px)] opacity-0' : '-translate-x-1/2'}`} aria-label="Navigasi bawah" aria-hidden={hidden}>
      {FOOTER_LEFT.map(link)}
      <NavLink to="/fitur" aria-label="Semua Fitur"
        className={({ isActive: a }) => `-mt-6 flex h-[52px] w-[52px] flex-none items-center justify-center rounded-[18px] border-[5px] border-canvas text-white shadow-[0_6px_16px_rgba(5,150,105,0.3)] transition sm:h-14 sm:w-14 ${a ? 'bg-primary-deep' : 'bg-primary hover:bg-primary-deep'}`}>
        <LayoutGrid className="h-6 w-6" strokeWidth={1.8} aria-hidden />
      </NavLink>
      {FOOTER_RIGHT.map(link)}
    </nav>
  );
}

export const BrandMark = ({ className = 'h-24' }) => <img src={logo} alt="EcoWin — since 2026" className={className} />;
