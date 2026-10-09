import { ArrowUpFromLine, CheckCircle2 } from 'lucide-react';
import { useState } from 'react';
import { EcoApi } from '../api/endpoints';
import Button from '../components/Button';
import { TransactionCard } from '../components/Cards';
import Card from '../components/Card';
import { Input, Textarea } from '../components/Field';
import { ConfirmationDialog } from '../components/Modal';
import PageHeader from '../components/PageHeader';
import VerifikasiBanner from '../components/VerifikasiBanner';
import { useAuth } from '../context/AuthContext';
import PagedList from '../components/PagedList';
import { useQuery } from '../hooks/useQuery';
import { usePaged } from '../hooks/usePaged';
import { rupiah } from '../utils/format';

export default function Penarikan() {
  const { user } = useAuth();
  const belumVerifikasi = user?.nasabah?.status_verifikasi === 'pending';
  const saldo = useQuery(() => EcoApi.saldo(), []);
  const list = usePaged((p) => EcoApi.penarikan(p), []);
  const [jumlah, setJumlah] = useState('');
  const [catatan, setCatatan] = useState('');
  const [errors, setErrors] = useState({});
  const [confirm, setConfirm] = useState(false);
  const [busy, setBusy] = useState(false);
  const [ok, setOk] = useState(false);

  const tersedia = saldo.data?.saldo_tersedia;
  const nominal = Math.floor(Number(jumlah));

  const validate = () => {
    if (!(nominal > 0)) return 'Isi nominal penarikan.';
    if (tersedia !== undefined && nominal > tersedia) return `Melebihi saldo tersedia (${rupiah(tersedia)}).`;
    return null;
  };

  const ask = (e) => {
    e.preventDefault();
    const err = validate();
    setErrors(err ? { jumlah: err } : {});
    setOk(false);
    if (!err) setConfirm(true);
  };

  const send = async () => {
    setBusy(true);
    try {
      await EcoApi.ajukanPenarikan({ jumlah: nominal, ...(catatan ? { catatan } : {}) });
      setJumlah(''); setCatatan(''); setOk(true); setConfirm(false);
      saldo.reload(); list.reload();
    } catch (err) {
      setConfirm(false);
      const first = Object.values(err.errors || {}).flat()[0];
      setErrors({ jumlah: first || err.message });
    } finally { setBusy(false); }
  };

  return (
    <div className="space-y-5">
      <PageHeader title="Tarik saldo" subtitle="Ajukan penarikan. Petugas akan memeriksa dan menyetujuinya." />
      <VerifikasiBanner compact />
      <div className="grid gap-5 lg:grid-cols-[1fr_1.2fr]">
        <Card>
          <p className="text-sm text-muted">Saldo tersedia</p>
          <p className="mt-1 text-3xl font-extrabold tabular-nums text-primary">{saldo.loading ? '…' : rupiah(tersedia)}</p>
          <form onSubmit={ask} className="mt-5 space-y-4" noValidate>
            <Input label="Nominal penarikan (Rp)" type="number" inputMode="numeric" min="1" step="1" value={jumlah} onChange={(e) => setJumlah(e.target.value)} error={errors.jumlah} placeholder="mis. 20000" />
            <div className="flex flex-wrap gap-2">
              {[10000, 20000, 50000].map((v) => <button key={v} type="button" onClick={() => setJumlah(String(v))} className="rounded-full bg-tint px-3.5 py-1.5 text-xs font-semibold text-primary hover:bg-emerald-100">{rupiah(v)}</button>)}
              {tersedia > 0 && <button type="button" onClick={() => setJumlah(String(Math.floor(tersedia)))} className="rounded-full border border-line px-3.5 py-1.5 text-xs font-semibold text-muted hover:bg-canvas">Semua saldo</button>}
            </div>
            <Textarea label="Catatan (opsional)" value={catatan} onChange={(e) => setCatatan(e.target.value)} maxLength={500} />
            <Button type="submit" size="lg" className="w-full" icon={ArrowUpFromLine} disabled={belumVerifikasi}>Ajukan penarikan</Button>
          </form>
          {ok && <p className="mt-4 flex items-center gap-2 rounded-2xl bg-tint p-3.5 text-sm font-semibold text-primary" role="status"><CheckCircle2 className="h-5 w-5" aria-hidden />Pengajuan terkirim, menunggu petugas.</p>}
        </Card>

        <div>
          <h3 className="mb-3 font-bold">Riwayat penarikan</h3>
          <PagedList list={list} empty={{ icon: ArrowUpFromLine, title: 'Belum ada penarikan', message: 'Pengajuan penarikan Anda akan tampil di sini.' }}
            renderItem={(p) => <TransactionCard title="Penarikan saldo" subtitle={p.catatan} amount={p.jumlah} kind="debit" date={p.created_at} status={p.status} />} />
        </div>
      </div>

      <ConfirmationDialog open={confirm} title="Ajukan penarikan?" message={`Anda akan mengajukan penarikan ${rupiah(nominal)}. Saldo dikurangi setelah petugas menyetujui.`}
        confirmLabel="Ajukan" loading={busy} onConfirm={send} onClose={() => setConfirm(false)} />
    </div>
  );
}
