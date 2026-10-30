import { Camera, CheckCircle2, LogOut, Mail, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import { EcoApi } from '../api/endpoints';
import Button from '../components/Button';
import Card from '../components/Card';
import { Input, Textarea } from '../components/Field';
import PageHeader from '../components/PageHeader';
import { ConfirmationDialog } from '../components/Modal';
import QRCard from '../components/QRCard';
import { useAuth } from '../context/AuthContext';
import { useQuery } from '../hooks/useQuery';
import { inisial } from '../utils/format';
import { perkecilFoto } from '../utils/foto';

const JENIS_KELAMIN = [
  { nilai: 'L', label: 'Laki-laki', gambar: '/avatar-laki.jpg' },
  { nilai: 'P', label: 'Perempuan', gambar: '/avatar-perempuan.jpg' },
];

export default function Profil() {
  const { user, refreshUser, signOut } = useAuth();
  const [keluar, setKeluar] = useState(false);
  const [keluarBusy, setKeluarBusy] = useState(false);
  const qr = useQuery(() => EcoApi.qr(), []);
  const n = user?.nasabah;
  const [form, setForm] = useState({ nama: user?.nama ?? '', no_hp: n?.no_hp ?? '', alamat_rt_rw: n?.alamat_rt_rw ?? '', jenis_kelamin: user?.jenis_kelamin ?? '' });
  const [errors, setErrors] = useState({});
  const [busy, setBusy] = useState(false);
  const [saved, setSaved] = useState(false);
  const [fotoBusy, setFotoBusy] = useState(false);
  const [fotoGalat, setFotoGalat] = useState('');
  const [avatarRusak, setAvatarRusak] = useState(false);
  const berkas = useRef(null);
  const set = (k) => (e) => { setSaved(false); setForm((f) => ({ ...f, [k]: e.target.value })); };

  const save = async (e) => {
    e.preventDefault();
    setBusy(true); setErrors({}); setSaved(false);
    try {
      await EcoApi.updateProfile({ ...form, jenis_kelamin: form.jenis_kelamin || null });
      await refreshUser();
      setAvatarRusak(false);
      setSaved(true);
    } catch (err) {
      const flat = Object.fromEntries(Object.entries(err.errors || {}).map(([k, v]) => [k, [].concat(v)[0]]));
      setErrors(Object.keys(flat).length ? flat : { nama: err.message });
    } finally { setBusy(false); }
  };

  const pilihFoto = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = ''; // boleh memilih berkas yang sama lagi
    if (!file) return;
    setFotoGalat(''); setFotoBusy(true);
    try {
      await EcoApi.gantiFoto(await perkecilFoto(file));
      await refreshUser();
      setAvatarRusak(false);
    } catch (err) {
      const pesan = err.errors?.foto ? [].concat(err.errors.foto)[0] : err.message;
      setFotoGalat(pesan || 'Foto gagal diunggah. Coba lagi.');
    } finally { setFotoBusy(false); }
  };

  const hapusFoto = async () => {
    setFotoGalat(''); setFotoBusy(true);
    try { await EcoApi.hapusFoto(); await refreshUser(); setAvatarRusak(false); }
    catch (err) { setFotoGalat(err.message || 'Foto gagal dihapus.'); }
    finally { setFotoBusy(false); }
  };

  return (
    <div className="space-y-5">
      <PageHeader title="Profil" subtitle="Data akun dan QR Card Anda." />
      <div className="grid gap-5 lg:grid-cols-[1.3fr_1fr]">
        <Card>
          <div className="flex items-center gap-4">
            <div className="relative flex-none">
              {user?.avatar && !avatarRusak
                ? <img src={user.avatar} alt="Foto profil" referrerPolicy="no-referrer" onError={() => setAvatarRusak(true)} className="h-20 w-20 rounded-full object-cover ring-4 ring-white shadow" />
                : <span className="flex h-20 w-20 items-center justify-center rounded-full bg-tint text-2xl font-bold text-primary-deep">{inisial(user?.nama)}</span>}
              <button type="button" onClick={() => berkas.current?.click()} disabled={fotoBusy} aria-label="Ganti foto profil"
                className="absolute -bottom-1 -right-1 flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-primary text-white shadow transition hover:bg-primary-deep disabled:opacity-60">
                <Camera className="h-4 w-4" aria-hidden />
              </button>
              <input ref={berkas} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={pilihFoto} aria-label="Pilih foto profil" data-testid="input-foto" />
            </div>
            <div className="min-w-0">
              <p className="truncate text-lg font-bold">{user?.nama}</p>
              <p className="flex items-center gap-1.5 truncate text-sm text-muted"><Mail className="h-3.5 w-3.5 flex-none" aria-hidden />{user?.email}</p>
              <p className="mt-0.5 text-xs text-muted">{n?.nomor_nasabah} · {n?.bank_sampah?.nama}</p>
            </div>
          </div>
          <div className="mt-3 flex flex-wrap items-center gap-2">
            <Button type="button" size="sm" variant="soft" icon={Camera} loading={fotoBusy} onClick={() => berkas.current?.click()}>Ganti foto</Button>
            {user?.punya_foto && <Button type="button" size="sm" variant="ghost" icon={Trash2} disabled={fotoBusy} onClick={hapusFoto}>Hapus foto</Button>}
            <span className="text-xs text-muted">{user?.punya_foto ? 'Foto sendiri' : 'Avatar bawaan'} · JPEG, PNG, atau WebP</span>
          </div>
          {fotoGalat && <p role="alert" className="mt-2 text-sm font-semibold text-danger">{fotoGalat}</p>}

          <form onSubmit={save} className="mt-6 space-y-4" noValidate>
            <Input label="Nama lengkap" value={form.nama} onChange={set('nama')} error={errors.nama} maxLength={150} />
            <fieldset>
              <legend className="mb-1.5 text-sm font-semibold">Jenis kelamin</legend>
              <div role="radiogroup" aria-label="Jenis kelamin" className="grid grid-cols-2 gap-3">
                {JENIS_KELAMIN.map((j) => {
                  const aktif = form.jenis_kelamin === j.nilai;
                  return (
                    <label key={j.nilai} className={`flex min-w-0 cursor-pointer items-center gap-2 rounded-2xl border p-2 transition sm:gap-3 sm:p-3 ${aktif ? 'border-primary bg-tint ring-2 ring-primary/20' : 'border-field hover:border-primary/50'}`}>
                      <input type="radio" name="jenis_kelamin" value={j.nilai} checked={aktif} onChange={set('jenis_kelamin')} className="sr-only" />
                      <img src={j.gambar} alt="" className="h-9 w-9 flex-none rounded-full object-cover sm:h-10 sm:w-10" />
                      <span className="text-sm font-semibold">{j.label}</span>
                    </label>
                  );
                })}
              </div>
              <p className="mt-1.5 text-xs text-muted">Menentukan avatar bawaan bila Anda belum memasang foto sendiri.</p>
              {errors.jenis_kelamin && <p role="alert" className="mt-1 text-xs font-semibold text-danger">{errors.jenis_kelamin}</p>}
            </fieldset>
            <Input label="Nomor HP" inputMode="tel" value={form.no_hp} onChange={set('no_hp')} error={errors.no_hp} placeholder="08xxxxxxxxxx" />
            <Textarea label="Alamat (RT/RW)" value={form.alamat_rt_rw} onChange={set('alamat_rt_rw')} error={errors.alamat_rt_rw} maxLength={255} />
            <Input label="Email Google" value={user?.email ?? ''} disabled readOnly hint="Email tidak dapat diubah. Hubungi petugas Bank Sampah bila perlu." />
            <Button type="submit" size="lg" className="w-full sm:w-auto" loading={busy}>Simpan perubahan</Button>
            {saved && <p className="flex items-center gap-2 text-sm font-semibold text-primary-deep" role="status"><CheckCircle2 className="h-4 w-4" aria-hidden />Profil diperbarui.</p>}
          </form>
        </Card>
        <div className="space-y-5">
          <QRCard data={qr.data} loading={qr.loading} />
          <Button type="button" variant="danger" icon={LogOut} className="w-full" onClick={() => setKeluar(true)}>Keluar</Button>
        </div>
      </div>
      <ConfirmationDialog open={keluar} title="Keluar dari EcoWin?" message="Anda harus masuk lagi dengan akun Google untuk mengakses EcoWin."
        confirmLabel="Keluar" loading={keluarBusy} onConfirm={async () => { setKeluarBusy(true); await signOut(); }} onClose={() => setKeluar(false)} />
    </div>
  );
}
