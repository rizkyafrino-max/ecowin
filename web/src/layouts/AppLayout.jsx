import { useEffect, useRef, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { BottomNav, NAV_ITEMS, Sidebar } from '../components/Nav';
import TopBar from '../components/TopBar';

const TITLES = { '/penarikan': 'Tarik Saldo', '/fitur': 'Semua Fitur' };

export default function AppLayout() {
  const { pathname } = useLocation();
  const [footerHidden, setFooterHidden] = useState(false);
  const lastY = useRef(0);

  // Footer turun saat menggulir ke bawah dan naik lagi saat menggulir ke atas (sama dengan footer admin).
  useEffect(() => {
    const onScroll = () => {
      const y = window.scrollY;
      if (y > lastY.current + 8 && y > 80) setFooterHidden(true);
      else if (y < lastY.current - 8 || y < 80) setFooterHidden(false);
      lastY.current = y;
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);
  useEffect(() => { setFooterHidden(false); lastY.current = 0; }, [pathname]);

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
      <BottomNav pathname={pathname} hidden={footerHidden} />
    </div>
  );
}
