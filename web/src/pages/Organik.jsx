import { Camera, CheckCircle2, ImagePlus, Leaf, MapPin, Plus, Recycle } from 'lucide-react';
import { lazy, Suspense, useRef, useState } from 'react';
import { EcoApi } from '../api/endpoints';
import AuthImage from '../components/AuthImage';
import Button from '../components/Button';
import { ActivityCard, OrganikRow } from '../components/Cards';
import Card from '../components/Card';
import { Input, Select, Textarea } from '../components/Field';
import { BottomSheet } from '../components/Modal';
import OrganikFlow from '../components/OrganikFlow';
import PageHeader from '../components/PageHeader';
import PagedList from '../components/PagedList';
import StatusBadge from '../components/StatusBadge';
import { LoadingState } from '../components/States';
import { useQuery } from '../hooks/useQuery';
import { usePaged } from '../hooks/usePaged';
import { prepareImage } from '../utils/image';

const PetaBiopori = lazy(() => import('../components/PetaBiopori'));
import { kg, tanggal } from '../utils/format';

const pad = (n) => String(n).padStart(2, '0');
const localNow = () => { const d = new Date(Date.now() - 60_000); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`; };

function Lokasi({ lokasi, onLapor }) {
  if (lokasi.loading) return <LoadingState rows={2} />;
  if (lokasi.error || !lokasi.data.data.length) return null;
  const daftar = lokasi.data.data;
  return (
    <section className="space-y-4">
      <Suspense fallback={<div className="h-[320px] ew-skeleton !rounded-[20px]" />}>
        <PetaBiopori titik={daftar} onLapor={onLapor} />
      </Suspense>
      <h3 className="font-bold">Lokasi BioporiPrint</h3>
      <div className="grid gap-3 sm:grid-cols-2">
        {daftar.map((t) => (
          <Card key={t.id} className="!p-4">
            <div className="flex items-start justify-between gap-3">
              <p className="flex items-center gap-2 font-semibold"><MapPin className="h-4 w-4 text-primary" aria-hidden />{t.nama_lokasi}</p>
              <StatusBadge status={t.status_panen} />
            </div>
            <p className="mt-2 text-xs text-muted">Terakhir diisi: {tanggal(t.terakhir_diisi_at)} · Estimasi panen: {tanggal(t.estimasi_panen_at)}</p>
            {t.bioporiprint && <p className="mt-2 inline-block rounded-full bg-info-tint px-2.5 py-1 text-xs font-semibold text-info">BioporiPrint</p>}
          </Card>
        ))}
      </div>
    </section>
  );
}

function Form({ onDone, lokasi, initialId }) {
  const metode = 'BioporiPrint'; // satu-satunya metode pengolahan yang dipakai Nasabah
  const [form, setForm] = useState({ titik_biopori_id: initialId ? String(initialId) : '', tanggal_pemasukan: localNow(), jenis_sampah: 'Sisa sayur', berat_kg: '', catatan: '' });
  const [foto, setFoto] = useState(null);
  const [preview, setPreview] = useState(null);
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState(null);
  const [busy, setBusy] = useState(false);
  const camera = useRef(null);
  const gallery = useRef(null);

  const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));
  const titik = (lokasi.data?.data ?? []).filter((t) => t.bioporiprint);

  const pick = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    try {
      const ready = await prepareImage(file);
      setFoto(ready);
      setPreview((old) => { if (old) URL.revokeObjectURL(old); return URL.createObjectURL(ready); });
      setErrors((x) => ({ ...x, foto_bukti: undefined }));
    } catch (err) { setErrors((x) => ({ ...x, foto_bukti: err.message })); }
  };

  const submit = async (e) => {
    e.preventDefault();
    const local = {};
    if (!form.titik_biopori_id) local.titik_biopori_id = `Pilih lokasi ${metode}.`;
    if (!(Number(form.berat_kg) > 0)) local.berat_kg = 'Isi perkiraan berat lebih dari 0 kg.';
    if (!foto) local.foto_bukti = 'Foto bukti wajib diunggah.';
    setErrors(local);
    if (Object.keys(local).length) return;

    const body = new FormData();
    body.append('titik_biopori_id', form.titik_biopori_id);
    body.append('metode_pengolahan', metode.toLowerCase());
    body.append('tanggal_pemasukan', new Date(form.tanggal_pemasukan).toISOString());
    body.append('jenis_sampah', form.jenis_sampah);
    body.append('berat_kg', form.berat_kg);
    if (form.catatan) body.append('catatan', form.catatan);
    body.append('foto_bukti', foto);

    setBusy(true);
    setMessage(null);
    try {
      await EcoApi.buatBiopori(body);
      onDone();
    } catch (err) {
      const flat = Object.fromEntries(Object.entries(err.errors || {}).map(([k, v]) => [k, [].concat(v)[0]]));
      setErrors(flat);
      setMessage(err.message);
      setBusy(false);
    }
  };

  return (
    <form onSubmit={submit} className="space-y-4 pt-2" noValidate>
      <Input label="Metode pengolahan" value="BioporiPrint" readOnly />
      <Select label={`Lokasi ${metode}`} value={form.titik_biopori_id} onChange={set('titik_biopori_id')} error={errors.titik_biopori_id}>
        <option value="">{lokasi.loading ? 'Memuat…' : 'Pilih lokasi'}</option>
        {titik.map((t) => <option key={t.id} value={t.id}>{t.nama_lokasi}</option>)}
      </Select>
      <Input label="Tanggal & waktu" type="datetime-local" max={localNow()} value={form.tanggal_pemasukan} onChange={set('tanggal_pemasukan')} error={errors.tanggal_pemasukan} />
      <div className="grid gap-4 sm:grid-cols-2">
        <Input label="Jenis sampah organik" value={form.jenis_sampah} onChange={set('jenis_sampah')} error={errors.jenis_sampah} maxLength={100} />
        <Input label="Perkiraan berat (kg)" type="number" inputMode="decimal" step="0.1" min="0.1" max="100" value={form.berat_kg} onChange={set('berat_kg')} error={errors.berat_kg} placeholder="mis. 1.5" />
      </div>
      <Textarea label="Catatan (opsional)" value={form.catatan} onChange={set('catatan')} error={errors.catatan} maxLength={1000} />

      <div>
        <p className="mb-1.5 text-sm font-semibold">Foto bukti <span className="text-danger">*</span></p>
        {preview ? <img src={preview} alt="Pratinjau foto bukti" className="mb-3 h-44 w-full rounded-2xl object-cover" /> : null}
        <div className="grid grid-cols-2 gap-3">
          <Button type="button" variant="soft" icon={Camera} onClick={() => camera.current?.click()}>Ambil foto</Button>
          <Button type="button" variant="outline" icon={ImagePlus} onClick={() => gallery.current?.click()}>Dari galeri</Button>
        </div>
        <input ref={camera} type="file" accept="image/*" capture="environment" className="hidden" onChange={pick} />
        <input ref={gallery} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={pick} />
        {errors.foto_bukti && <p className="mt-1.5 text-xs font-medium text-danger" role="alert">{errors.foto_bukti}</p>}
      </div>

      {message && <p className="rounded-2xl bg-danger-tint p-3 text-sm text-danger" role="alert">{message}</p>}
      <Button type="submit" size="lg" className="w-full" loading={busy}>Kirim aktivitas</Button>
      <p className="text-center text-xs text-muted">Aktivitas akan diperiksa petugas dan berstatus Menunggu sampai disetujui atau ditolak.</p>
    </form>
  );
}

function Detail({ item }) {
  return (
    <div className="space-y-4">
      <AuthImage id={item.id} alt={`Foto bukti ${item.jenis_sampah}`} className="h-56 w-full rounded-2xl" />
      <div className="flex items-center justify-between"><p className="font-bold">{item.jenis_sampah} · {kg(item.berat_kg)}</p><StatusBadge status={item.status} /></div>
      <dl className="divide-y divide-line text-sm">
        {[['Lokasi', item.lokasi], ['Waktu', tanggal(item.tanggal_pemasukan, true)], ['Catatan Anda', item.catatan || '-'], ['Diperiksa', item.waktu_diperiksa ? tanggal(item.waktu_diperiksa, true) : 'Belum diperiksa'], ['Catatan petugas', item.catatan_petugas || '-']]
          .map(([k, v]) => <div key={k} className="flex justify-between gap-4 py-2.5"><dt className="text-muted">{k}</dt><dd className="text-right font-medium">{v}</dd></div>)}
      </dl>
    </div>
  );
}

const tgl = (x) => new Date(x).getTime() || 0;

/** Gabungkan setoran organik (dicatat petugas) dan aktivitas Biopori (dikirim nasabah) menjadi satu daftar. */
function useAktivitas() {
  const setoran = usePaged((p) => EcoApi.organik(p), []);
  const biopori = usePaged((p) => EcoApi.biopori(p), []);
  const items = [
    ...setoran.items.map((x) => ({ id: `s${x.id}`, jenis: 'setoran', tanggal: x.tanggal, raw: x })),
    ...biopori.items.map((x) => ({ id: `b${x.id}`, jenis: 'biopori', tanggal: x.tanggal_pemasukan, raw: x })),
  ].sort((a, b) => tgl(b.tanggal) - tgl(a.tanggal));
  return {
    items,
    loading: setoran.loading || biopori.loading,
    loadingMore: setoran.loadingMore || biopori.loadingMore,
    error: setoran.error || biopori.error,
    hasMore: setoran.hasMore || biopori.hasMore,
    reload: () => { setoran.reload(); biopori.reload(); },
    more: () => { if (setoran.hasMore) setoran.more(); if (biopori.hasMore) biopori.more(); },
    reloadBiopori: biopori.reload,
  };
}

export default function Organik() {
  const list = useAktivitas();
  const lokasi = useQuery(() => EcoApi.lokasiBiopori(), []);
  const ringkasan = useQuery(() => EcoApi.dashboard(), []);
  const [pilihLokasi, setPilihLokasi] = useState(null);
  const [sheet, setSheet] = useState(null); // 'baru' | item
  const [done, setDone] = useState(false);
  const total = ringkasan.data?.organik?.total_berat_kg;
  const tambah = () => { setDone(false); setPilihLokasi(null); setSheet('baru'); };

  return (
    <div className="space-y-6">
      <PageHeader title="Organik" subtitle="Kelola aktivitas organikmu. Organik tidak menjadi saldo rupiah." action={<Button size="sm" icon={Plus} onClick={tambah}>Tambah Aktivitas</Button>} />
      <Card className="flex items-center gap-4">
        <span className="flex h-14 w-14 flex-none items-center justify-center rounded-2xl bg-tint text-primary"><Recycle className="h-7 w-7" aria-hidden /></span>
        <div>
          <p className="text-sm text-muted">Total Organik</p>
          <p className="text-2xl font-extrabold tabular-nums">{total === undefined ? '…' : kg(total)}</p>
          {ringkasan.data?.organik && <p className="text-xs text-muted">Estimasi kompos {kg(ringkasan.data.organik.estimasi_kompos_kg)}</p>}
        </div>
      </Card>
      {done && <p className="flex items-center gap-2 rounded-2xl bg-tint p-3.5 text-sm font-semibold text-primary" role="status"><CheckCircle2 className="h-5 w-5" aria-hidden />Aktivitas terkirim. Menunggu pemeriksaan petugas.</p>}
      <OrganikFlow />
      <h3 className="-mb-3 font-bold">Aktivitas Terbaru</h3>
      <PagedList list={list} empty={{ icon: Leaf, title: 'Belum ada aktivitas', message: 'Tambahkan aktivitas organik pertama Anda dengan foto bukti.', action: <Button size="sm" onClick={tambah}>Tambah Aktivitas</Button> }}
        renderItem={(a) => (a.jenis === 'setoran'
          ? <OrganikRow item={a.raw} />
          : <ActivityCard onClick={() => setSheet(a.raw)} title={a.raw.jenis_sampah} subtitle={`${a.raw.metode ?? 'Biopori'} · ${a.raw.lokasi ?? 'Lokasi'} · ${tanggal(a.raw.tanggal_pemasukan)}`} status={a.raw.status} right={<p className="text-sm font-bold tabular-nums">{kg(a.raw.berat_kg)}</p>} />)} />
      <Lokasi lokasi={lokasi} onLapor={(t) => { setDone(false); setPilihLokasi(t.id); setSheet('baru'); }} />

      <BottomSheet open={sheet === 'baru'} onClose={() => setSheet(null)} title="Tambah aktivitas organik">
        <Form lokasi={lokasi} initialId={pilihLokasi} onDone={() => { setSheet(null); setDone(true); list.reloadBiopori(); }} />
      </BottomSheet>
      <BottomSheet open={!!sheet && sheet !== 'baru'} onClose={() => setSheet(null)} title="Detail aktivitas">{sheet && sheet !== 'baru' && <Detail item={sheet} />}</BottomSheet>
    </div>
  );
}
