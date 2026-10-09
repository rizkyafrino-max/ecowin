import { CheckCircle2, Mail } from 'lucide-react';
import { useState } from 'react';
import { EcoApi } from '../api/endpoints';
import Button from '../components/Button';
import Card from '../components/Card';
import { Input, Textarea } from '../components/Field';
import PageHeader from '../components/PageHeader';
import QRCard from '../components/QRCard';
import { useAuth } from '../context/AuthContext';
import { useQuery } from '../hooks/useQuery';
import { inisial } from '../utils/format';

export default function Profil() {
  const { user, refreshUser } = useAuth();
  const qr = useQuery(() => EcoApi.qr(), []);
  const n = user?.nasabah;
  const [form, setForm] = useState({ nama: user?.nama ?? '', no_hp: n?.no_hp ?? '', alamat_rt_rw: n?.alamat_rt_rw ?? '' });
  const [errors, setErrors] = useState({});
  const [busy, setBusy] = useState(false);
  const [saved, setSaved] = useState(false);
  const set = (k) => (e) => { setSaved(false); setForm((f) => ({ ...f, [k]: e.target.value })); };

  const save = async (e) => {
    e.preventDefault();
    setBusy(true); setErrors({}); setSaved(false);
    try {
      await EcoApi.updateProfile(form);
      await refreshUser();
      setSaved(true);
    } catch (err) {
      const flat = Object.fromEntries(Object.entries(err.errors || {}).map(([k, v]) => [k, [].concat(v)[0]]));
      setErrors(Object.keys(flat).length ? flat : { nama: err.message });
    } finally { setBusy(false); }
  };

  return (
    <div className="space-y-5">
      <PageHeader title="Profil" subtitle="Data akun dan QR Card Anda." />
      <div className="grid gap-5 lg:grid-cols-[1.3fr_1fr]">
        <Card>
          <div className="flex items-center gap-4">
            {user?.avatar
              ? <img src={user.avatar} alt="" referrerPolicy="no-referrer" className="h-16 w-16 rounded-full object-cover" />
              : <span className="flex h-16 w-16 items-center justify-center rounded-full bg-tint text-xl font-bold text-primary">{inisial(user?.nama)}</span>}
            <div className="min-w-0">
              <p className="truncate text-lg font-bold">{user?.nama}</p>
              <p className="flex items-center gap-1.5 truncate text-sm text-muted"><Mail className="h-3.5 w-3.5 flex-none" aria-hidden />{user?.email}</p>
              <p className="mt-0.5 text-xs text-muted">{n?.nomor_nasabah} · {n?.bank_sampah?.nama}</p>
            </div>
          </div>

          <form onSubmit={save} className="mt-6 space-y-4" noValidate>
            <Input label="Nama lengkap" value={form.nama} onChange={set('nama')} error={errors.nama} maxLength={150} />
            <Input label="Nomor HP" inputMode="tel" value={form.no_hp} onChange={set('no_hp')} error={errors.no_hp} placeholder="08xxxxxxxxxx" />
            <Textarea label="Alamat (RT/RW)" value={form.alamat_rt_rw} onChange={set('alamat_rt_rw')} error={errors.alamat_rt_rw} maxLength={255} />
            <Input label="Email Google" value={user?.email ?? ''} disabled readOnly hint="Email tidak dapat diubah. Hubungi petugas Bank Sampah bila perlu." />
            <Button type="submit" size="lg" className="w-full sm:w-auto" loading={busy}>Simpan perubahan</Button>
            {saved && <p className="flex items-center gap-2 text-sm font-semibold text-primary" role="status"><CheckCircle2 className="h-4 w-4" aria-hidden />Profil diperbarui.</p>}
          </form>
        </Card>
        <QRCard data={qr.data} loading={qr.loading} />
      </div>
    </div>
  );
}
