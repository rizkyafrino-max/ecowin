import { Scale } from 'lucide-react';
import { useState } from 'react';
import { EcoApi } from '../api/endpoints';
import { TransactionCard } from '../components/Cards';
import Card from '../components/Card';
import { BottomSheet } from '../components/Modal';
import PageHeader from '../components/PageHeader';
import PagedList from '../components/PagedList';
import { ErrorState, LoadingState } from '../components/States';
import { useQuery } from '../hooks/useQuery';
import { usePaged } from '../hooks/usePaged';
import { kg, rupiah, tanggal } from '../utils/format';

function Detail({ id }) {
  const q = useQuery(() => EcoApi.transaksiDetail(id), [id]);
  if (q.loading) return <LoadingState rows={3} />;
  if (q.error) return <ErrorState error={q.error} onRetry={q.reload} />;
  const t = q.data.data ?? q.data;
  const rows = [
    ['Jenis sampah', t.jenis_sampah], ['Berat', kg(t.berat_kg)], ['Harga per kg', rupiah(t.harga_per_kg)],
    ['Total', rupiah(t.nilai_rupiah)], ['Waktu', tanggal(t.created_at, true)],
  ];
  return (
    <dl className="divide-y divide-line">
      {rows.map(([k, v]) => <div key={k} className="flex justify-between gap-4 py-3 text-sm"><dt className="text-muted">{k}</dt><dd className="text-right font-semibold">{v}</dd></div>)}
    </dl>
  );
}

function Harga() {
  const q = useQuery(() => EcoApi.harga(), []);
  if (q.loading || q.error) return null;
  const jenis = q.data.data.flatMap((k) => k.jenis_sampah).filter((j) => j.harga.length);
  if (!jenis.length) return null;
  return (
    <Card>
      <h3 className="font-bold">Harga sampah anorganik saat ini</h3>
      <p className="mt-0.5 text-xs text-muted">Harga bertingkat mengikuti berat setoran dan dapat berubah sewaktu-waktu.</p>
      <ul className="mt-3 grid gap-3 sm:grid-cols-2">
        {jenis.map((j) => (
          <li key={j.id} className="rounded-2xl bg-canvas p-3.5">
            <p className="font-semibold">{j.nama_jenis}</p>
            <ul className="mt-1.5 space-y-0.5 text-xs text-muted">
              {j.harga.map((h) => (
                <li key={h.id} className="flex justify-between"><span>{h.maksimal_berat ? `${h.minimal_berat}–${h.maksimal_berat} kg` : `≥ ${h.minimal_berat} kg`}</span><b className="text-ink">{rupiah(h.harga_per_kg)}/kg</b></li>
              ))}
            </ul>
          </li>
        ))}
      </ul>
    </Card>
  );
}

export default function Transaksi() {
  const list = usePaged((p) => EcoApi.transaksi(p), []);
  const [open, setOpen] = useState(null);

  return (
    <div className="space-y-5">
      <PageHeader title="Transaksi" subtitle="Riwayat setoran sampah anorganik Anda." />
      <PagedList list={list} empty={{ icon: Scale, title: 'Belum ada transaksi', message: 'Setoran anorganik yang dicatat petugas akan muncul di sini.' }}
        renderItem={(t) => (
          <button className="block w-full text-left" onClick={() => setOpen(t.id)}>
            <TransactionCard title={t.jenis_sampah ?? 'Setoran anorganik'} subtitle={kg(t.berat_kg)} amount={t.nilai_rupiah} date={t.created_at} />
          </button>
        )} />
      <Harga />
      <BottomSheet open={!!open} onClose={() => setOpen(null)} title="Detail transaksi">{open && <Detail id={open} />}</BottomSheet>
    </div>
  );
}
