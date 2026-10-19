import { Bell, LogOut } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useNotif } from '../context/NotifContext';
import { inisial } from '../utils/format';
import { ConfirmationDialog } from './Modal';

export default function TopBar({ title }) {
  const { user, signOut } = useAuth();
  const { belumDibaca } = useNotif();
  const [confirm, setConfirm] = useState(false);
  const [busy, setBusy] = useState(false);

  const doLogout = async () => { setBusy(true); await signOut(); };

  return (
    <header className="sticky top-0 z-20 border-b border-line bg-canvas/90 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
        <div className="flex items-center gap-2.5 md:hidden">
          <span className="text-lg font-extrabold">Eco<span className="text-primary-deep">Win</span></span>
        </div>
        <h1 className="hidden text-lg font-bold md:block">{title}</h1>
        <div className="flex items-center gap-2">
          <div className="hidden text-right sm:block">
            <p className="text-sm font-semibold leading-tight">{user?.nama}</p>
            <p className="text-xs text-muted">{user?.nasabah?.nomor_nasabah}</p>
          </div>
          <Link to="/notifikasi" className="relative flex h-10 w-10 items-center justify-center rounded-full text-muted hover:bg-white hover:text-ink" aria-label={belumDibaca > 0 ? `Notifikasi, ${belumDibaca} belum dibaca` : 'Notifikasi'}>
            <Bell className="h-5 w-5" />
            {belumDibaca > 0 && <span className="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full border-2 border-canvas bg-danger px-1 text-[10px] font-bold text-white">{belumDibaca > 99 ? '99+' : belumDibaca}</span>}
          </Link>
          <Link to="/profil" aria-label="Profil saya">
            {user?.avatar
              ? <img src={user.avatar} alt="" referrerPolicy="no-referrer" className="h-10 w-10 rounded-full object-cover ring-2 ring-white" />
              : <span className="flex h-10 w-10 items-center justify-center rounded-full bg-tint text-sm font-bold text-primary-deep">{inisial(user?.nama)}</span>}
          </Link>
          <button onClick={() => setConfirm(true)} className="flex h-10 w-10 items-center justify-center rounded-full text-muted hover:bg-white hover:text-ink" aria-label="Keluar">
            <LogOut className="h-5 w-5" />
          </button>
        </div>
      </div>
      <ConfirmationDialog open={confirm} title="Keluar dari EcoWin?" message="Anda harus masuk lagi dengan akun Google untuk mengakses EcoWin."
        confirmLabel="Keluar" loading={busy} onConfirm={doLogout} onClose={() => setConfirm(false)} />
    </header>
  );
}
