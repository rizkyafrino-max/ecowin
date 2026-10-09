import { Outlet, useLocation } from 'react-router-dom';
import { BottomNav, NAV_ITEMS, Sidebar } from '../components/Nav';
import TopBar from '../components/TopBar';

const TITLES = { '/penarikan': 'Tarik Saldo', '/fitur': 'Semua Fitur' };

export default function AppLayout() {
  const { pathname } = useLocation();
  const title = TITLES[pathname] ?? NAV_ITEMS.find((i) => (i.end ? pathname === i.to : pathname.startsWith(i.to)))?.label ?? 'EcoWin';

  return (
    <div className="min-h-dvh">
      <Sidebar pathname={pathname} />
      <div className="md:pl-20 lg:pl-64">
        <TopBar title={title} />
        <main className="mx-auto max-w-6xl px-4 pb-32 pt-5 sm:px-6 md:pt-7">
          <div key={pathname} className="ew-page"><Outlet /></div>
        </main>
      </div>
      <BottomNav pathname={pathname} />
    </div>
  );
}
