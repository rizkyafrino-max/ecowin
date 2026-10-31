import { Bug, Camera, CheckCircle2, LogOut, ShieldCheck, Trash2, UserRound } from 'lucide-react';
import { useRef, useState } from 'react';
import { EcoApi } from '../api/endpoints';
import Button from '../components/Button';
import Card from '../components/Card';
import { Input, Textarea } from '../components/Field';
import { Baris, Kelompok } from '../components/Kelompok';
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

const URL_PEMBUAT = 'https://rizky.pplgku.my.id/';

/** Lencana bening di atas kepala hijau profil. */
const Lencana = ({ children, mono = false }) => (
  <span className={`inline-flex max-w-full items-center gap-1.5 truncate rounded-full bg-white/20 px-3 py-1.5 text-xs font-semibold ${mono ? 'font-mono tracking-wide' : ''}`}>{children}</span>
);

/**
 * Profil, sama dengan Android: kepala hijau (foto, nama, lencana), Kartu Nasabah melintang, data diri yang bisa diubah, daftar Akun
 * (lapor bug, pembuat aplikasi), dan tombol keluar. Di layar lebar, kartu dan daftar akun ada di kolom kanan.
 */
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
  const bank = [n?.bank_sampah?.nama, n?.bank_sampah?.rt ? `RT ${n.bank_sampah.rt}${n.bank_sampah.rw ? `/RW ${n.bank_sampah.rw}` : ''}` : null].filter(Boolean).join(' · ');

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
      <PageHeader title="Profil" subtitle="Data akun dan Kartu Nasabah Anda." />
      <div className="grid gap-5 lg:grid-cols-[1.25fr_1fr] lg:items-start">
        <section className="relative overflow-hidden rounded-[28px] bg-gradient-to-br from-[#065F46] via-primary to-secondary px-5 py-7 text-center text-white shadow-soft lg:col-start-1 lg:row-start-1">
          <span aria-hidden className="pointer-events-none absolute -right-12 -top-14 h-44 w-44 rounded-full bg-white/10" />
          <span aria-hidden className="pointer-events-none absolute -bottom-14 -left-10 h-36 w-36 rounded-full bg-white/[0.07]" />
          <div className="relative flex flex-col items-center gap-1.5">
            <div className="relative">
              <span className="flex h-24 w-24 items-center justify-center rounded-full bg-white p-1">
                {user?.avatar && !avatarRusak
                  ? <img src={user.avatar} alt="Foto profil" referrerPolicy="no-referrer" onError={() => setAvatarRusak(true)} className="h-full w-full rounded-full object-cover" />
                  : <span className="flex h-full w-full items-center justify-center rounded-full bg-tint text-3xl font-bold text-primary-deep">{inisial(user?.nama)}</span>}
              </span>
              <button type="button" onClick={() => berkas.current?.click()} disabled={fotoBusy} aria-label="Ganti foto profil"
                className="absolute -bottom-0.5 -right-0.5 flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-primary-deep text-white shadow transition hover:bg-[#065F46] disabled:opacity-60">
                <Camera className="h-4 w-4" aria-hidden />
              </button>
              <input ref={berkas} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={pilihFoto} aria-label="Pilih foto profil" data-testid="input-foto" />
            </div>
            <p className="mt-2 line-clamp-2 max-w-full break-words text-[22px] font-extrabold leading-tight">{user?.nama}</p>
            <p className="max-w-full truncate text-[13px] text-emerald-100">{user?.email}</p>
            <div className="mt-1 flex flex-wrap justify-center gap-2">
              {n?.nomor_nasabah && <Lencana mono>{n.nomor_nasabah}</Lencana>}
              {n?.bank_sampah?.nama && <Lencana>{n.bank_sampah.nama}</Lencana>}
              {n?.status_verifikasi === 'verified' && <Lencana><ShieldCheck className="h-3.5 w-3.5" aria-hidden />Terverifikasi</Lencana>}
            </div>
            <div className="mt-2 flex flex-wrap items-center justify-center gap-2">
              <Button type="button" size="sm" icon={Camera} loading={fotoBusy} onClick={() => berkas.current?.click()} className="!bg-white !text-primary-deep hover:!bg-tint">Ganti foto</Button>
              {user?.punya_foto && <Button type="button" size="sm" icon={Trash2} disabled={fotoBusy} onClick={hapusFoto} className="!bg-white/20 !text-white hover:!bg-white/30">Hapus foto</Button>}
            </div>
            <p className="text-xs text-emerald-100/90">{user?.punya_foto ? 'Foto sendiri' : 'Avatar bawaan'} · JPEG, PNG, atau WebP</p>
            {fotoGalat && <p role="alert" className="rounded-xl bg-white/90 px-3 py-1.5 text-sm font-semibold text-danger">{fotoGalat}</p>}
          </div>
        </section>

        <div className="lg:col-start-2 lg:row-start-1">
          <QRCard data={qr.data} loading={qr.loading} bank={bank} />
        </div>

        <section className="lg:col-start-1 lg:row-span-2 lg:row-start-2">
          <h3 className="mb-2 pl-1.5 text-xs font-bold uppercase tracking-wider text-muted">Data diri</h3>
          <Card>
            <form onSubmit={save} className="space-y-4" noValidate>
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
        </section>

        <Kelompok judul="Akun" className="lg:col-start-2 lg:row-start-2">
          <Baris ikon={Bug} warna="amber" judul="Lapor bug" keterangan="Kirim masalah beserta foto bukti" to="/lapor-bug" state={{ dari: 'Profil' }} />
          <Baris ikon={UserRound} judul="Dibuat oleh Rizky" keterangan="rizky.pplgku.my.id" href={URL_PEMBUAT} />
        </Kelompok>

        <div className="lg:col-start-2 lg:row-start-3">
          <Button type="button" variant="danger" icon={LogOut} className="w-full" onClick={() => setKeluar(true)}>Keluar</Button>
          <p className="mt-2 text-center text-xs text-muted">Setelah keluar, Anda perlu masuk kembali dengan Google.</p>
        </div>
      </div>
      <ConfirmationDialog open={keluar} title="Keluar dari EcoWin?" message="Anda harus masuk lagi dengan akun Google untuk mengakses EcoWin."
        confirmLabel="Keluar" loading={keluarBusy} onConfirm={async () => { setKeluarBusy(true); await signOut(); }} onClose={() => setKeluar(false)} />
    </div>
  );
}
