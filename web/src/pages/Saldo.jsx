import { ArrowDownToLine, ArrowUpFromLine, Wallet } from 'lucide-react';
import { Link } from 'react-router-dom';
import { EcoApi } from '../api/endpoints';
import Button from '../components/Button';
import { TransactionCard } from '../components/Cards';
import Card, { BalanceCard, StatCard } from '../components/Card';
import PageHeader from '../components/PageHeader';
import PagedList from '../components/PagedList';
import { ErrorState } from '../components/States';
import { useQuery } from '../hooks/useQuery';
import { usePaged } from '../hooks/usePaged';
import { rupiah } from '../utils/format';

export default function Saldo() {
  const saldo = useQuery(() => EcoApi.saldo(), []);
  const mutasi = usePaged((p) => EcoApi.mutasi(p), []);
  const s = saldo.data;

  return (
    <div className="space-y-5">
      <PageHeader title="Saldo" subtitle="Riwayat perubahan saldo EcoWin Anda." />
      {saldo.error ? <Card><ErrorState error={saldo.error} onRetry={saldo.reload} /></Card> : (
        <div className="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
          {saldo.loading ? <div className="h-44 ew-skeleton !rounded-[24px]" /> : (
            <BalanceCard saldo={s.saldo} tersedia={s.saldo_tersedia} ditahan={s.saldo_ditahan}
              actions={<Link to="/penarikan"><Button variant="outline" size="sm" className="!border-white/30 !bg-white/15 !text-white hover:!bg-white/25" icon={ArrowDownToLine}>Tarik saldo</Button></Link>} />
          )}
          <div className="grid grid-cols-2 gap-3 lg:grid-cols-1">
            <StatCard icon={ArrowDownToLine} label="Total pemasukan" value={saldo.loading ? '…' : rupiah(s.total_pemasukan)} />
            <StatCard icon={ArrowUpFromLine} label="Total penarikan" value={saldo.loading ? '…' : rupiah(s.total_penarikan)} tone="warn" />
          </div>
        </div>
      )}
      <h3 className="pt-1 font-bold">Riwayat saldo</h3>
      <PagedList list={mutasi} empty={{ icon: Wallet, title: 'Belum ada mutasi saldo', message: 'Setoran dan penarikan akan tercatat di sini.' }}
        renderItem={(m) => <TransactionCard title={m.keterangan || (m.tipe === 'kredit' ? 'Saldo masuk' : 'Saldo keluar')} subtitle={`Saldo ${rupiah(m.saldo_sesudah)}`} amount={m.jumlah} kind={m.tipe} date={m.created_at} />} />
    </div>
  );
}
