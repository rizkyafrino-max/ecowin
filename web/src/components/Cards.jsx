import { ArrowDownLeft, ArrowUpRight, Leaf, Recycle } from 'lucide-react';
import { kg, rupiah, tanggal } from '../utils/format';
import StatusBadge from './StatusBadge';

/** Baris transaksi: anorganik/mutasi masuk (kredit) atau penarikan/keluar (debit). */
export function TransactionCard({ title, subtitle, amount, kind = 'kredit', date, status }) {
  const credit = kind === 'kredit';
  const Icon = credit ? ArrowDownLeft : ArrowUpRight;
  return (
    <div className="flex items-center gap-3.5 py-3">
      <span className={`flex h-11 w-11 flex-none items-center justify-center rounded-2xl ${credit ? 'bg-tint text-primary' : 'bg-warn-tint text-warn'}`}>
        <Icon className="h-5 w-5" aria-hidden />
      </span>
      <div className="min-w-0 flex-1">
        <p className="truncate font-semibold">{title}</p>
        <p className="truncate text-xs text-muted">{[subtitle, tanggal(date, true)].filter(Boolean).join(' · ')}</p>
      </div>
      <div className="flex flex-col items-end gap-1">
        <p className={`font-bold tabular-nums ${credit ? 'text-primary' : 'text-ink'}`}>{credit ? '+' : '−'}{rupiah(amount)}</p>
        {status && <StatusBadge status={status} />}
      </div>
    </div>
  );
}

/** Baris aktivitas organik / Biopori. */
export function ActivityCard({ icon: Icon = Leaf, title, subtitle, status, right, onClick }) {
  const Tag = onClick ? 'button' : 'div';
  return (
    <Tag onClick={onClick} className="flex w-full items-center gap-3.5 py-3 text-left">
      <span className="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-tint text-primary"><Icon className="h-5 w-5" aria-hidden /></span>
      <div className="min-w-0 flex-1">
        <p className="truncate font-semibold">{title}</p>
        <p className="truncate text-xs text-muted">{subtitle}</p>
      </div>
      <div className="flex flex-col items-end gap-1">
        {right}
        {status && <StatusBadge status={status} />}
      </div>
    </Tag>
  );
}

export const OrganikRow = ({ item }) => (
  <ActivityCard icon={Recycle} title={item.jenis_organik} subtitle={`${tanggal(item.tanggal)} · ${item.lokasi || 'Tanpa lokasi'}`}
    status={item.status_pengolahan} right={<p className="text-sm font-bold tabular-nums">{kg(item.berat_kg)}</p>} />
);
