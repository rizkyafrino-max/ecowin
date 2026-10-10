import { ShieldAlert } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

/** Tampil bila akun nasabah (daftar mandiri) belum diverifikasi petugas Bank Sampah. */
export default function VerifikasiBanner({ compact = false }) {
  const { user } = useAuth();
  if (user?.nasabah?.status_verifikasi !== 'pending') return null;
  return (
    <div className="flex items-start gap-3 rounded-2xl border border-amber-200 bg-warn-tint p-4" role="status">
      <ShieldAlert className="mt-0.5 h-5 w-5 flex-none text-warn" aria-hidden />
      <div className="text-sm">
        <p className="font-semibold text-amber-900">Akun menunggu verifikasi petugas</p>
        {!compact && <p className="mt-0.5 text-amber-800/90">Petugas Bank Sampah Anda akan memeriksa data pendaftaran. Penarikan saldo aktif setelah akun diverifikasi.</p>}
      </div>
    </div>
  );
}
