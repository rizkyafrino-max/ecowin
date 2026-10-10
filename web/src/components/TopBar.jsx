import { LogOut } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { inisial } from '../utils/format';
import { ConfirmationDialog } from './Modal';

export default function TopBar({ title }) {
  const { user, signOut } = useAuth();
  const [confirm, setConfirm] = useState(false);
  const [busy, setBusy] = useState(false);

  const doLogout = async () => { setBusy(true); await signOut(); };

  return (
    <header className="sticky top-0 z-20 border-b border-line bg-canvas/90 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
        <div className="flex items-center gap-2.5 md:hidden">
          <span className="text-lg font-extrabold">Eco<span className="text-primary">Win</span></span>
        </div>
        <h1 className="hidden text-lg font-bold md:block">{title}</h1>
        <div className="flex items-center gap-2">
          <div className="hidden text-right sm:block">
            <p className="text-sm font-semibold leading-tight">{user?.nama}</p>
            <p className="text-xs text-muted">{user?.nasabah?.nomor_nasabah}</p>
          </div>
          <Link to="/profil" aria-label="Profil saya">
            {user?.avatar
              ? <img src={user.avatar} alt="" referrerPolicy="no-referrer" className="h-10 w-10 rounded-full object-cover ring-2 ring-white" />
              : <span className="flex h-10 w-10 items-center justify-center rounded-full bg-tint text-sm font-bold text-primary">{inisial(user?.nama)}</span>}
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
